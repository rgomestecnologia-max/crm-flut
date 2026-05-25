<?php

namespace App\Jobs;

use App\Events\MessageReceived;
use App\Models\AiBotConfig;
use App\Models\ChatbotMenuConfig;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\EvolutionApiConfig;
use App\Models\Message;
use App\Services\CurrentCompany;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\MediaStorage;
use Illuminate\Support\Facades\Log;

class ProcessEvolutionMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public array $payload) {}

    public function handle(): void
    {
        // Resolve a empresa pelo nome da instância no payload do webhook.
        // Cada empresa tem sua própria EvolutionApiConfig com instance_name único.
        $instanceName = $this->payload['instance'] ?? null;
        $companyId    = $this->resolveCompanyId($instanceName);

        if (!$companyId) {
            Log::warning('ProcessEvolutionMessage: instância sem empresa correspondente — descartando', [
                'instance' => $instanceName,
                'event'    => $this->payload['event'] ?? null,
            ]);
            return;
        }

        app(CurrentCompany::class)->set($companyId, persist: false);

        try {
            $event = $this->payload['event'] ?? '';
            $data  = $this->payload['data'] ?? [];

            // Ignora eventos que não são de mensagem recebida
            if ($event !== 'messages.upsert') {
                return;
            }

            $key         = $data['key'] ?? [];
            $fromMe      = (bool) ($key['fromMe'] ?? false);
            $remoteJid   = $key['remoteJid'] ?? '';
            $messageId   = $key['id'] ?? null;
            $messageType = $data['messageType'] ?? 'conversation';

            // Ignora status do WhatsApp
            if (str_contains($remoteJid, '@broadcast') || str_contains($remoteJid, 'status@broadcast')) {
                return;
            }

            // ── Reações ─────────────────────────────────────────────────────
            if ($messageType === 'reactionMessage' || isset($data['message']['reactionMessage'])) {
                $this->processReaction($data, $remoteJid);
                return;
            }

            // ── Grupo ou individual ──────────────────────────────────────────
            $isGroup = str_ends_with($remoteJid, '@g.us');

            // Telefone do chat (grupo ou contato)
            $chatPhone = preg_replace('/\D/', '', preg_replace('/@.+/', '', $remoteJid));

            // Remetente real
            $participantJid = $data['participant'] ?? $key['participant'] ?? null;
            $senderPhone    = $participantJid
                ? preg_replace('/\D/', '', preg_replace('/@.+/', '', $participantJid))
                : $chatPhone;

            $senderName = $data['pushName'] ?? null;

            // Foto do perfil
            $senderPhoto = $data['message']['imageMessage']['url'] ?? null; // não vem na mensagem
            // (fotos de perfil chegam via CONTACTS_UPSERT separadamente)

            // Ignora se não tem telefone válido
            if (!$chatPhone || !$senderPhone) {
                Log::info('ProcessEvolutionMessage: sem telefone', ['remoteJid' => $remoteJid]);
                return;
            }

            // ── Contato ──────────────────────────────────────────────────────
            if ($isGroup) {
                // Para grupos, contato é o GRUPO (não o remetente individual)
                $groupName = $data['chatName'] ?? null;
                $groupPhone = substr($chatPhone, 0, 20); // trunca pra caber no campo

                $contact = Contact::where('chat_lid', $remoteJid)->first()
                    ?? Contact::where('phone', $groupPhone)->first();

                if (!$contact) {
                    $contact = Contact::create([
                        'phone'    => $groupPhone,
                        'name'     => $groupName,
                        'chat_lid' => $remoteJid,
                    ]);
                }

                // Atualiza nome se veio no webhook e o contato não tem nome ou tem nome numérico (ID do grupo)
                $needsName = !$contact->name || preg_match('/^\d{10,}$/', $contact->name);
                if ($groupName && $needsName) {
                    $contact->update(['name' => $groupName]);
                } elseif ($needsName && $remoteJid) {
                    // Nome não veio no webhook — busca via Evolution API
                    try {
                        $evoConfig = \App\Models\EvolutionApiConfig::withoutGlobalScopes()
                            ->where('company_id', $companyId)
                            ->where('is_active', true)
                            ->first();
                        if ($evoConfig) {
                            $http = \Illuminate\Support\Facades\Http::withHeaders([
                                'apikey' => $evoConfig->instance_api_key ?: $evoConfig->global_api_key,
                            ])->timeout(5);
                            $result = $http->get($evoConfig->serverUrl() . "/group/findGroupInfos/{$evoConfig->instance_name}?groupJid={$remoteJid}")->json();
                            $fetchedName = $result['subject'] ?? null;
                            if ($fetchedName) {
                                $contact->update(['name' => $fetchedName]);
                                Log::info('Grupo: nome buscado via API', ['name' => $fetchedName, 'jid' => $remoteJid]);
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Grupo: falha ao buscar nome', ['jid' => $remoteJid, 'error' => $e->getMessage()]);
                    }
                }

                if ($remoteJid && !$contact->chat_lid) {
                    $contact->update(['chat_lid' => $remoteJid]);
                }
            } else {
                if ($fromMe) {
                    // Busca por JID (chat_lid) ou phone
                    $contact = Contact::where('chat_lid', $remoteJid)->first()
                        ?? Contact::where('phone', $chatPhone)->first();

                    // fromMe com LID: tenta vincular ao contato que tem phone real
                    if (!$contact && str_contains($remoteJid, '@lid')) {
                        // Busca por zapi_message_id da mensagem enviada pelo CRM
                        $sentMsg = Message::where('zapi_message_id', $key['id'] ?? '')->first();
                        if ($sentMsg) {
                            $conv = Conversation::find($sentMsg->conversation_id);
                            $contact = $conv ? Contact::find($conv->contact_id) : null;
                            if ($contact && !$contact->chat_lid) {
                                $contact->update(['chat_lid' => $remoteJid]);
                            }
                        }
                    }

                    if (!$contact) {
                        Log::info('ProcessEvolutionMessage: fromMe sem contato encontrado', [
                            'remoteJid' => $remoteJid,
                            'chatPhone' => $chatPhone,
                            'instance'  => $instanceName,
                        ]);
                        return;
                    }
                } else {
                    $contact = Contact::where('chat_lid', $remoteJid)->first()
                        ?? Contact::where('phone', $chatPhone)->first();

                    // Busca variação com/sem 9° dígito (55+DDD+8dig ↔ 55+DDD+9dig)
                    if (!$contact && str_starts_with($chatPhone, '55') && strlen($chatPhone) >= 12) {
                        $ddd = substr($chatPhone, 2, 2);
                        $num = substr($chatPhone, 4);
                        if (strlen($num) === 8) {
                            // Sem 9° dígito → tenta com 9
                            $contact = Contact::where('phone', '55' . $ddd . '9' . $num)->first();
                        } elseif (strlen($num) === 9 && $num[0] === '9') {
                            // Com 9° dígito → tenta sem 9
                            $contact = Contact::where('phone', '55' . $ddd . substr($num, 1))->first();
                        }
                        if ($contact) {
                            Log::info('Contato encontrado por variação 9° dígito', [
                                'chatPhone' => $chatPhone, 'matched' => $contact->phone,
                            ]);
                        }
                    }

                    // Se não encontrou e o chatPhone é real (55...), tenta achar
                    // contato que tenha LID como phone (resolve duplicação LID ↔ número real)
                    if (!$contact && str_starts_with($chatPhone, '55') && $senderName) {
                        // Contatos com LID como phone (não começa com 55, phone longo)
                        $lidContacts = Contact::whereRaw("LENGTH(phone) > 14 AND phone NOT LIKE '55%'");

                        // 1) Match exato de nome
                        $contact = (clone $lidContacts)->where('name', $senderName)->first();

                        // 2) Match parcial — todas as palavras do pushName contidas no nome do contato
                        if (!$contact) {
                            $nameParts = preg_split('/\s+/', trim($senderName));
                            // Filtra palavras curtas (preposições, emojis)
                            $searchWords = array_filter($nameParts, fn($w) => mb_strlen($w) >= 3);

                            if (count($searchWords) >= 2) {
                                // Todas as palavras do pushName devem existir no nome do contato
                                $q = clone $lidContacts;
                                foreach ($searchWords as $word) {
                                    $q->where('name', 'like', "%{$word}%");
                                }
                                $contact = $q->first();
                            }

                            // Se não achou com todas, tenta com primeiro nome (>= 4 chars)
                            if (!$contact && !empty($nameParts[0]) && mb_strlen($nameParts[0]) >= 4) {
                                $contact = (clone $lidContacts)
                                    ->where('name', 'like', "{$nameParts[0]}%")
                                    ->first();
                            }
                        }

                        // 3) Busca contato com LID que tem conversa aberta recente
                        if (!$contact) {
                            $contact = Contact::whereRaw("LENGTH(phone) > 14 AND phone NOT LIKE '55%'")
                                ->whereHas('conversations', fn($q) => $q->whereIn('status', ['open', 'pending']))
                                ->where('name', 'like', explode(' ', $senderName)[0] . '%')
                                ->latest('updated_at')
                                ->first();
                        }

                        if ($contact) {
                            $oldPhone = $contact->phone;
                            $contact->update([
                                'phone'    => $chatPhone,
                                'chat_lid' => $contact->chat_lid ?: $oldPhone . '@lid',
                            ]);
                            Log::info('Contact LID→real unificado', [
                                'contact' => $contact->id,
                                'name'    => $contact->name,
                                'pushName' => $senderName,
                                'old_phone' => $oldPhone,
                                'new_phone' => $chatPhone,
                            ]);
                        }
                    }

                    if (!$contact) {
                        $contact = Contact::create([
                            'phone' => $chatPhone,
                            'name'  => $senderName,
                        ]);
                    }

                    if ($senderName && !$contact->name) {
                        $contact->update(['name' => $senderName]);
                    }
                    // Salva o JID completo para poder responder corretamente
                    if (!$contact->chat_lid) {
                        $contact->update(['chat_lid' => $remoteJid]);
                    }
                    // Se o contato tinha LID como phone e agora temos o real, atualiza
                    if ($contact->phone && !str_starts_with($contact->phone, '55')
                        && strlen($contact->phone) > 14 && str_starts_with($chatPhone, '55')) {
                        $oldLid = $contact->phone;
                        $contact->update([
                            'phone'    => $chatPhone,
                            'chat_lid' => $contact->chat_lid ?: $oldLid . '@lid',
                        ]);
                    }
                }
            }

            // ── Registrar como lead (menu Leads / Disparos) ────────────────
            if (!$isGroup && !$fromMe && $contact->phone) {
                try {
                    \App\Models\BroadcastContact::firstOrCreate(
                        ['company_id' => $companyId, 'phone' => $contact->phone],
                        ['name' => $contact->name, 'is_active' => true, 'tags' => ['atendimento']]
                    );
                } catch (\Throwable) {}
            }

            // ── Conversa ─────────────────────────────────────────────────────
            if ($isGroup) {
                // $groupName já definido na seção de contato acima
                // Grupo sempre reutiliza a mesma conversa (independente do status)
                $conversation = Conversation::where('contact_id', $contact->id)
                    ->where('is_group', true)
                    ->latest()
                    ->first();

                if (!$conversation) {
                    $department = Department::active()->first();
                    if (!$department) return;

                    $conversation = Conversation::create([
                        'contact_id'    => $contact->id,
                        'department_id' => $department->id,
                        'status'        => 'open',
                        'is_group'      => true,
                        'group_name'    => $groupName,
                    ]);
                } else {
                    // Reabre se estava resolvido/transferido
                    if (in_array($conversation->status, ['resolved', 'transferred'])) {
                        $conversation->update(['status' => 'open']);
                    }
                    if ($groupName && !$conversation->group_name) {
                        $conversation->update(['group_name' => $groupName]);
                    }
                }
            } else {
                $conversation = Conversation::where('contact_id', $contact->id)
                    ->where('is_group', false)
                    ->where(function ($q) {
                        $q->whereIn('status', ['open', 'pending', 'resolved'])
                          ->orWhere('is_archived', true);
                    })
                    ->latest()
                    ->first();

                if (!$conversation) {
                    if ($fromMe) return;

                    // Multi-instância: usa departamento padrão da instância se configurado
                    $evoConfig = EvolutionApiConfig::withoutCompanyScope()
                        ->where('instance_name', $instanceName)
                        ->first();
                    $department = ($evoConfig && $evoConfig->default_department_id)
                        ? Department::find($evoConfig->default_department_id)
                        : Department::active()->first();
                    if (!$department) {
                        Log::error('ProcessEvolutionMessage: no active department found');
                        return;
                    }

                    $conversation = Conversation::create([
                        'contact_id'              => $contact->id,
                        'department_id'            => $department->id,
                        'evolution_api_config_id'  => $evoConfig?->id,
                        'status'                   => 'open',
                        'is_group'                 => false,
                    ]);

                    // Roteamento por DDD: redireciona para agente/departamento correto
                    try {
                        $contactPhone = $contact->phone ?? '';
                        if (strlen($contactPhone) >= 12 && str_starts_with($contactPhone, '55')) {
                            $ddd = substr($contactPhone, 2, 2);
                            $dddRule = \App\Models\DddRoutingRule::where('ddd', $ddd)->where('is_active', true)->first();
                            if ($dddRule && $dddRule->department_id) {
                                $conversation->update(['department_id' => $dddRule->department_id]);
                                Log::info('DDD routing na criação', ['conv' => $conversation->id, 'ddd' => $ddd, 'dept' => $dddRule->department_id]);
                            }
                        }
                    } catch (\Throwable) {}

                    // Machinery Prime: criar card no pipeline Comercial quando conversa nova no dept Comercial
                    if ($companyId === 11 && $department->name === 'Comercial') {
                        $this->createCardForDepartment($contact, $department);
                    }

                    // Orangexpress: criar card no pipeline Vendas para toda conversa nova
                    if ($companyId === 3) {
                        try {
                            $vendas = \App\Models\CrmPipeline::where('name', 'Vendas')->first();
                            $novo = $vendas?->stages()->orderBy('sort_order')->first();
                            if ($vendas && $novo && $contact) {
                                $exists = \App\Models\CrmCard::where('contact_id', $contact->id)
                                    ->where('pipeline_id', $vendas->id)->exists();
                                if (!$exists) {
                                    \App\Models\CrmCard::create([
                                        'pipeline_id' => $vendas->id,
                                        'stage_id'    => $novo->id,
                                        'contact_id'  => $contact->id,
                                        'title'       => $contact->display_name ?? $contact->name,
                                    ]);
                                    Log::info('Card criado automaticamente (nova conversa)', ['contact' => $contact->name]);
                                }
                            }
                        } catch (\Throwable) {}
                    }
                } elseif (!$fromMe && $conversation->status === 'resolved') {
                    // Reabre a conversa: volta pra fila, reseta chatbot pra novo atendimento
                    // Se humano estava atendendo via WhatsApp, mantém waiting_human_reason
                    // para evitar que a IA entre na conversa do humano
                    $keepWaiting = $conversation->waiting_human_reason === 'Atendente respondeu pelo WhatsApp';

                    $conversation->update([
                        'status'        => 'open',
                        'assigned_to'   => null,
                        'menu_awaiting' => false,
                        'waiting_human_reason' => $keepWaiting ? $conversation->waiting_human_reason : null,
                    ]);
                    if (!$keepWaiting) {
                        // Remove mensagens de sistema do menu anterior para permitir novo menu
                        Message::where('conversation_id', $conversation->id)
                            ->where('sender_type', 'system')
                            ->where('content', 'like', 'Menu: cliente selecionou%')
                            ->delete();
                    }
                }
            }

            // ── Mensagem editada pelo contato/agente ─────────────────────────
            if ($messageType === 'editedMessage') {
                $editedMsg = $data['message']['editedMessage']['message'] ?? $data['message'] ?? [];
                $editedText = $editedMsg['conversation']
                    ?? $editedMsg['extendedTextMessage']['text']
                    ?? $editedMsg['editedMessage']['message']['conversation']
                    ?? $editedMsg['editedMessage']['message']['extendedTextMessage']['text']
                    ?? null;
                // O messageId da mensagem editada referencia a original via protocolMessage
                $originalId = $data['message']['protocolMessage']['key']['id']
                    ?? $data['message']['editedMessage']['message']['protocolMessage']['key']['id']
                    ?? $messageId;

                if ($editedText && $originalId) {
                    $existingMsg = Message::where('zapi_message_id', $originalId)->first();
                    if ($existingMsg) {
                        $existingMsg->update(['content' => $editedText]);
                        Log::info('Mensagem editada pelo WhatsApp', ['msg_id' => $existingMsg->id, 'zapi_id' => $originalId]);
                        try { broadcast(new \App\Events\MessageReceived($existingMsg)); } catch (\Throwable) {}
                    }
                }
                return;
            }

            // ── Deduplicação ─────────────────────────────────────────────────
            if ($messageId && Message::where('zapi_message_id', $messageId)->exists()) {
                return;
            }

            // ── Conteúdo da mensagem ──────────────────────────────────────────
            [$content, $type, $mediaUrl, $mediaFilename] = $this->extractContent($data);

            // Log detalhado para mídia (rastrear perdas)
            if (in_array($messageType, ['imageMessage', 'videoMessage', 'audioMessage', 'documentMessage', 'documentWithCaptionMessage', 'stickerMessage'])) {
                Log::info('ProcessEvolutionMessage: mídia recebida', [
                    'messageType' => $messageType,
                    'remoteJid'   => $remoteJid,
                    'messageId'   => $messageId,
                    'mediaUrl'    => $mediaUrl ? 'OK' : 'FALHOU',
                    'type'        => $type,
                    'hasBase64'   => !empty($data['message'][$messageType]['base64'] ?? null),
                    'msgKeys'     => array_keys($data['message'] ?? []),
                ]);
            }

            if (!$content && !$mediaUrl) {
                Log::warning('ProcessEvolutionMessage: mensagem sem conteúdo suportado', [
                    'messageType' => $messageType,
                    'remoteJid'   => $remoteJid,
                    'messageId'   => $messageId,
                    'msgKeys'     => array_keys($data['message'] ?? []),
                    'msgData'     => substr(json_encode($data['message'] ?? []), 0, 500),
                ]);
                return;
            }

            $senderType     = $fromMe ? 'agent' : 'contact';
            $deliveryStatus = $fromMe ? 'sent'  : 'delivered';

            // Extrai referência de mensagem citada (reply/quote do WhatsApp)
            $replyToId = null;
            $contextInfo = $data['contextInfo'] ?? $data['message']['extendedTextMessage']['contextInfo'] ?? $data['message']['imageMessage']['contextInfo'] ?? null;
            if (!$contextInfo) {
                // Tenta em qualquer sub-mensagem
                foreach ($data['message'] ?? [] as $v) {
                    if (is_array($v) && isset($v['contextInfo'])) { $contextInfo = $v['contextInfo']; break; }
                }
            }
            $stanzaId = $contextInfo['stanzaId'] ?? $contextInfo['quotedMessage']['stanzaId'] ?? null;
            if ($stanzaId) {
                $quotedMsg = Message::where('zapi_message_id', $stanzaId)->first();
                if ($quotedMsg) $replyToId = $quotedMsg->id;
            }

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_type'     => $senderType,
                'sender_id'       => null,
                'sender_name'     => ($isGroup && !$fromMe) ? $senderName : null,
                'sender_phone'    => ($isGroup && !$fromMe) ? $senderPhone : null,
                'content'         => $content,
                'type'            => $type,
                'media_url'       => $mediaUrl,
                'media_filename'  => $mediaFilename,
                'zapi_message_id' => $messageId,
                'delivery_status' => $deliveryStatus,
                'reply_to_id'     => $replyToId,
            ]);

            // Calcula duração do áudio via ffprobe
            if ($type === 'audio' && $mediaUrl) {
                try {
                    $path = ltrim(str_replace('/storage/', '', parse_url($mediaUrl, PHP_URL_PATH) ?? ''), '/');
                    $dur  = \App\Services\AudioProbe::durationFromStorage($path);
                    if ($dur) $message->update(['media_duration' => round($dur, 1)]);
                } catch (\Throwable) {}
            }

            $conversation->update(['last_message_at' => now(), 'status' => 'open']);

            // ── Orangexpress: mover card de Novo → Em negociação quando contato responde à IA ──
            if (!$fromMe && !$isGroup && $companyId === 3) {
                try {
                    $aiRespondeu = Message::where('conversation_id', $conversation->id)
                        ->where('sender_type', 'agent')->whereNull('sender_id')->exists();
                    if ($aiRespondeu && $contact) {
                        $vendas = \App\Models\CrmPipeline::where('name', 'Vendas')->first();
                        if ($vendas) {
                            $novoStage = $vendas->stages()->where('name', 'Novo')->first();
                            $negStage  = $vendas->stages()->where('name', 'Em negociação')->first();
                            if ($novoStage && $negStage) {
                                $card = \App\Models\CrmCard::where('contact_id', $contact->id)
                                    ->where('pipeline_id', $vendas->id)
                                    ->where('stage_id', $novoStage->id)->first();
                                if ($card) {
                                    $card->update(['stage_id' => $negStage->id]);
                                    \App\Models\CrmCardActivity::create([
                                        'card_id' => $card->id,
                                        'type'    => 'stage_change',
                                        'content' => 'Cliente respondeu à IA: Novo → Em negociação',
                                    ]);
                                }
                            }
                        }
                    }
                } catch (\Throwable) {}
            }

            // ── Humano respondeu pelo WhatsApp direto → para a IA ──
            if ($fromMe && !$isGroup) {
                // Só marca waiting_human_reason se a IA está ativa
                // Chatbot sozinho (URA) não precisa — é só menu de departamento
                $botConfig = AiBotConfig::current();
                $hasAi = $botConfig && $botConfig->is_active && $botConfig->hasKey();

                if ($hasAi && !$conversation->waiting_human_reason) {
                    $conversation->update(['waiting_human_reason' => 'Atendente respondeu pelo WhatsApp']);
                    Log::info('Humano detectado via WhatsApp direto, IA parada', [
                        'conv' => $conversation->id, 'content' => substr($content, 0, 50),
                    ]);
                }
                // Broadcast e return — não processa bot/automação para mensagens fromMe
                try { broadcast(new \App\Events\MessageReceived($message))->toOthers(); } catch (\Throwable) {}
                return;
            }

            // ── Resposta SIM/NÃO com auto-reply e move etapa (configurável por automação) ──
            if (!$fromMe && !$isGroup && $conversation->source_automation_id && $content) {
                $sourceAuto = $conversation->sourceAutomation;
                if ($sourceAuto && ($sourceAuto->reply_yes_message || $sourceAuto->reply_no_message)) {
                    // Ignora se humano já está atendendo (respondeu pelo CRM ou WhatsApp)
                    $humanAttending = $conversation->assigned_to
                        || $conversation->waiting_human_reason === 'Atendente respondeu pelo WhatsApp';

                    // Ignora auto-reply se a última msg de automação tem mais de 48h (agendamento já passou)
                    $lastAutoMsg = Message::where('conversation_id', $conversation->id)
                        ->where('sender_type', 'agent')
                        ->whereNull('sender_id')
                        ->latest()
                        ->first();

                    $autoExpired = !$lastAutoMsg || $lastAutoMsg->created_at->diffInHours(now()) > 48;

                    if ($humanAttending) {
                        Log::info('Auto-reply SIM/NÃO ignorado: humano já atendendo', [
                            'conv' => $conversation->id,
                            'assigned_to' => $conversation->assigned_to,
                        ]);
                    } elseif ($autoExpired) {
                        Log::info('Auto-reply SIM/NÃO ignorado: automação expirada (>48h)', [
                            'conv' => $conversation->id,
                            'last_auto_msg' => $lastAutoMsg?->created_at,
                            'content' => substr($content, 0, 50),
                        ]);
                    } else {
                        $reply = mb_strtolower(trim($content));
                        // Detecta SIM/NÃO em frases maiores (word boundary)
                        // Aceita variações com letras repetidas: simm, simmm, siim, nãoo, etc.
                        $yesWords = 'si+m+|confirmo|confirmado|confirmar|pode|vou|ok+|beleza|perfeito|combinado|certo|bora|yes+';
                        $noWords  = 'não+|nao+|remarcar|cancelar|cancela|desmarcar|reagendar|desmarco';
                        $isYes = (bool) preg_match('/\b(' . $yesWords . ')\b/iu', $reply) || $reply === 's' || $reply === '✅';
                        $isNo  = (bool) preg_match('/\b(' . $noWords . ')\b/iu', $reply) || $reply === 'n';
                        // Se contém palavras de ambos, ignora (ambíguo)
                        if ($isYes && $isNo) { $isYes = false; $isNo = false; }

                        if ($isYes || $isNo) {
                            $this->handleYesNoReply($sourceAuto, $conversation, $contact, $isYes, $reply);
                        }
                    }
                }
            }

            // ── Mover card no CRM quando cliente responde (automação move_on_reply) ──
            if (!$fromMe && !$isGroup && $conversation->source_automation_id) {
                try {
                    $sourceAuto = $conversation->sourceAutomation;
                    if ($sourceAuto && $sourceAuto->move_on_reply_from_stage_id && $sourceAuto->move_on_reply_to_stage_id) {
                        $card = \App\Models\CrmCard::where('contact_id', $contact->id)
                            ->where('stage_id', $sourceAuto->move_on_reply_from_stage_id)
                            ->first();
                        if ($card) {
                            $fromStage = $card->stage?->name ?? '—';
                            $toStage = \App\Models\CrmStage::find($sourceAuto->move_on_reply_to_stage_id);
                            $card->update(['stage_id' => $sourceAuto->move_on_reply_to_stage_id]);
                            \App\Models\CrmCardActivity::create([
                                'card_id' => $card->id,
                                'user_id' => null,
                                'type'    => 'stage_change',
                                'content' => "Cliente respondeu: {$fromStage} → " . ($toStage->name ?? '?'),
                            ]);
                            Log::info('move_on_reply: card movido', [
                                'card' => $card->id, 'from' => $fromStage, 'to' => $toStage->name ?? '?',
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('move_on_reply falhou', ['error' => $e->getMessage()]);
                }
            }

            // ── Bot de atendimento ────────────────────────────────────────────
            // Recarrega conversa do banco para pegar waiting_human_reason atualizado
            $conversation->refresh();
            // Não dispara IA se conversa está aguardando atendente humano
            // ou se agente humano já ENVIOU mensagem NESTA SESSÃO (após último encerramento)
            $lastResolved = Message::where('conversation_id', $conversation->id)
                ->where('sender_type', 'system')
                ->where('content', 'like', 'Atendimento encerrado%')
                ->latest()
                ->value('created_at');
            $humanSent = Message::where('conversation_id', $conversation->id)
                ->where('sender_type', 'agent')
                ->whereNotNull('sender_id')
                ->when($lastResolved, fn($q) => $q->where('created_at', '>', $lastResolved))
                ->exists();
            if (!$fromMe && !$conversation->waiting_human_reason && !$humanSent) {
                try {
                    $menuConfig = ChatbotMenuConfig::current();
                    $botConfig  = AiBotConfig::current();

                    $automationAi = false;
                    $aiOnlyForAutomation = false;
                    if (!$isGroup && $conversation->source_automation_id) {
                        $sourceAutomation = $conversation->sourceAutomation;
                        $automationAi = $sourceAutomation?->enable_ai_on_reply === true;
                    }

                    // Verifica se a empresa tem automação com enable_ai_on_reply
                    // Se sim, IA só atende conversas que vieram da automação
                    // Conversas diretas (sem automação) vão para Aguardando
                    // EXCETO: se a IA já respondeu nesta conversa (ex: lead de anúncio)
                    if (!$isGroup && !$conversation->source_automation_id && $botConfig && $botConfig->is_active) {
                        $hasAiAutomation = \App\Models\Automation::where('is_active', true)
                            ->where('enable_ai_on_reply', true)
                            ->exists();
                        if ($hasAiAutomation) {
                            // Verifica se IA já respondeu nesta sessão
                            $aiAlreadyActive = Message::where('conversation_id', $conversation->id)
                                ->where('sender_type', 'agent')
                                ->whereNull('sender_id')
                                ->when($lastResolved, fn($q) => $q->where('created_at', '>', $lastResolved))
                                ->exists();
                            if (!$aiAlreadyActive) {
                                $aiOnlyForAutomation = true;
                            }
                        }
                    }

                    $skipMenu = $automationAi && $botConfig && $botConfig->is_active && $botConfig->hasKey();

                    // Não envia chatbot/IA em grupos se reply_in_groups está desativado
                    $skipGroups = $isGroup && (!$menuConfig || !$menuConfig->reply_in_groups);

                    // Detecta lead de anúncio WhatsApp (frases padrão de ads)
                    // Trata como lead normal: cria card no CRM e aciona IA
                    $isAdLead = false;
                    $adPhrases = ['vi o an', 'tenho interesse', 'quero mais informa'];
                    $isAdMessage = $aiOnlyForAutomation && $content && collect($adPhrases)->contains(fn($p) => stripos($content, $p) !== false);
                    if ($isAdMessage) {
                        $isAdLead = true;
                        $aiOnlyForAutomation = false; // permite IA entrar

                        // Roteamento por DDD (mesmo do SendAutomationMessage)
                        try {
                            $adPhone = $contact->phone ?? '';
                            if (strlen($adPhone) >= 12 && str_starts_with($adPhone, '55')) {
                                $adDdd = substr($adPhone, 2, 2);
                                $dddRule = \App\Models\DddRoutingRule::where('ddd', $adDdd)->where('is_active', true)->first();
                                if ($dddRule) {
                                    if ($dddRule->department_id) {
                                        $conversation->update(['department_id' => $dddRule->department_id]);
                                        $deptName = Department::find($dddRule->department_id)?->name ?? '';
                                        Message::create([
                                            'conversation_id' => $conversation->id,
                                            'sender_type'     => 'system',
                                            'content'         => "Roteamento DDD: departamento {$deptName}",
                                            'type'            => 'text',
                                            'delivery_status' => 'sent',
                                        ]);
                                        Log::info('Ad lead DDD routing: ' . $adDdd . ' → dept ' . $deptName, ['conv' => $conversation->id]);
                                    }
                                }
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Ad lead DDD routing falhou', ['error' => $e->getMessage()]);
                        }

                        // Cria card no pipeline Vendas → etapa Novo (se não existir)
                        try {
                            $vendas = \App\Models\CrmPipeline::where('name', 'Vendas')->first();
                            $novo   = $vendas?->stages()->orderBy('sort_order')->first();
                            if ($vendas && $novo && $contact) {
                                $existingCard = \App\Models\CrmCard::where('contact_id', $contact->id)
                                    ->where('pipeline_id', $vendas->id)->first();
                                if (!$existingCard) {
                                    \App\Models\CrmCard::create([
                                        'pipeline_id' => $vendas->id,
                                        'stage_id'    => $novo->id,
                                        'contact_id'  => $contact->id,
                                        'title'       => $contact->display_name ?? $contact->name,
                                    ]);
                                    Log::info('ProcessEvolutionMessage: card criado via anúncio', [
                                        'contact' => $contact->name, 'pipeline' => $vendas->name,
                                    ]);
                                }
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Card anúncio falhou', ['error' => $e->getMessage()]);
                        }
                    }

                    if ($skipGroups) {
                        // Grupo sem permissão de bot — ignora
                    } elseif ($aiOnlyForAutomation) {
                        // Conversa direta em empresa com IA restrita → Aguardando
                        $conversation->update(['waiting_human_reason' => 'Atendimento direto - aguardando humano']);
                        Message::create([
                            'conversation_id' => $conversation->id,
                            'sender_type'     => 'system',
                            'content'         => '🔔 Cliente entrou em contato direto (fora da automação) — aguardando atendente',
                            'type'            => 'text',
                            'delivery_status' => 'sent',
                        ]);
                    } elseif ($menuConfig && $menuConfig->is_active && !$skipMenu) {
                        \App\Jobs\ProcessMenuBot::dispatch($conversation, $menuConfig, $botConfig, $message->id);
                    } elseif ($botConfig && $botConfig->is_active && $botConfig->hasKey()) {
                        \App\Jobs\ProcessBotResponse::dispatch($conversation, $botConfig, $message->id);
                    }
                } catch (\Throwable $e) {
                    Log::warning('ProcessEvolutionMessage: bot dispatch falhou', ['error' => $e->getMessage()]);
                }
            }

            // ── Broadcast ────────────────────────────────────────────────────
            try {
                broadcast(new MessageReceived($message))->toOthers();
            } catch (\Throwable $e) {
                Log::warning('ProcessEvolutionMessage: broadcast falhou', ['error' => $e->getMessage()]);
            }

            // ── Push Notification ────────────────────────────────────────────
            if ($message->sender_type === 'contact') {
                try {
                    \App\Jobs\SendPushNotification::dispatch($message->id);
                } catch (\Throwable) {}
            }

        } catch (\Throwable $e) {
            Log::error('ProcessEvolutionMessage falhou', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'payload' => $this->payload,
            ]);
        }
    }

    private function handleYesNoReply($automation, $conversation, $contact, bool $isYes, string $trigger): void
    {
        try {
            $targetStageId = $isYes ? $automation->reply_yes_stage_id : $automation->reply_no_stage_id;

            if ($targetStageId && $automation->move_on_reply_from_stage_id) {
                $card = \App\Models\CrmCard::where('contact_id', $contact->id)
                    ->where('stage_id', $automation->move_on_reply_from_stage_id)
                    ->first();

                // Card já não está na etapa esperada (já foi confirmado/movido antes) — ignora
                if (!$card) {
                    Log::info('handleYesNoReply: card já moveu, ignorando duplicata', [
                        'contact' => $contact->name, 'trigger' => $trigger,
                    ]);
                    return;
                }

                if ($card) {
                    $fromStageName = $card->stage?->name ?? '—';
                    $toStage = \App\Models\CrmStage::find($targetStageId);
                    $card->update(['stage_id' => $targetStageId]);
                    \App\Models\CrmCardActivity::create([
                        'card_id' => $card->id,
                        'user_id' => null,
                        'type'    => 'stage_change',
                        'content' => 'Cliente respondeu ' . ($isYes ? 'SIM' : 'NÃO') . " ({$trigger}): {$fromStageName} → " . ($toStage->name ?? '?'),
                    ]);
                }
            }

            $baseReply = $isYes ? $automation->reply_yes_message : $automation->reply_no_message;
            if ($baseReply) {
                $baseReply = str_replace('{nome}', $contact->name ?? '', $baseReply);
                $replyText = ($automation->ai_greeting)
                    ? ($this->generateAiVariation($baseReply, $contact->name ?? 'cliente') ?? $baseReply)
                    : $baseReply;

                $replyMsg = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_type'     => 'agent',
                    'sender_id'       => null,
                    'content'         => $replyText,
                    'type'            => 'text',
                    'delivery_status' => 'pending',
                ]);
                $conversation->update(['last_message_at' => now()]);
                \App\Jobs\SendWhatsAppMessage::dispatch($replyMsg);
            }

            Log::info('Auto-reply SIM/NÃO', [
                'automation' => $automation->name,
                'contact'    => $contact->name,
                'trigger'    => $trigger,
                'isYes'      => $isYes,
            ]);
        } catch (\Throwable $e) {
            Log::error('handleYesNoReply falhou', ['error' => $e->getMessage()]);
        }
    }

    private function processReaction(array $data, string $remoteJid): void
    {
        try {
            $reaction    = $data['message']['reactionMessage'] ?? [];
            $targetId    = $reaction['key']['id'] ?? null;
            $emoji       = $reaction['text'] ?? null;
            $fromMe      = (bool) ($reaction['key']['fromMe'] ?? false);
            $reactorPhone = preg_replace('/\D/', '', preg_replace('/@.+/', '', $remoteJid));

            if (!$targetId || !$reactorPhone) {
                return;
            }

            $message = Message::where('zapi_message_id', $targetId)->first();
            if (!$message) {
                Log::info('ProcessEvolutionMessage reaction: mensagem não encontrada', ['targetId' => $targetId]);
                return;
            }

            // ── Reação como confirmação SIM/NÃO (automação) ──
            if ($emoji && !$fromMe) {
                $conversation = $message->conversation;
                if ($conversation && $conversation->source_automation_id) {
                    $sourceAuto = $conversation->sourceAutomation;
                    if ($sourceAuto && ($sourceAuto->reply_yes_message || $sourceAuto->reply_no_message)) {
                        $yesEmojis = ['👍', '👍🏻', '👍🏼', '👍🏽', '👍🏾', '👍🏿', '❤️', '✅', '🙏', '🙏🏻', '🙏🏼', '🙏🏽', '🙏🏾', '🙏🏿', '💚', '😍', '🥰', '💪'];
                        $noEmojis  = ['👎', '👎🏻', '👎🏼', '👎🏽', '👎🏾', '👎🏿', '❌', '😢', '😞'];

                        $isYes = in_array($emoji, $yesEmojis);
                        $isNo  = in_array($emoji, $noEmojis);

                        if ($isYes || $isNo) {
                            $contact = $conversation->contact;
                            $this->handleYesNoReply($sourceAuto, $conversation, $contact, $isYes, "reação {$emoji}");
                        }
                    }
                }
            }

            $reactions = $message->reactions ?? [];
            $reactions = array_values(array_filter($reactions, fn($r) => $r['phone'] !== $reactorPhone));

            if ($emoji) {
                $reactions[] = ['emoji' => $emoji, 'phone' => $reactorPhone, 'at' => now()->toISOString()];
            }

            $message->update(['reactions' => $reactions]);

            broadcast(new MessageReceived($message));

            Log::info('ProcessEvolutionMessage reaction processada', [
                'message_id' => $message->id,
                'emoji'      => $emoji,
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessEvolutionMessage processReaction falhou', ['error' => $e->getMessage()]);
        }
    }

    private function extractContent(array $data): array
    {
        $msg       = $data['message'] ?? [];
        $type      = $data['messageType'] ?? 'conversation';
        $messageId = $data['key']['id'] ?? null;

        // Remove chaves auxiliares que não são conteúdo (distribuição de chave de grupo)
        unset($msg['senderKeyDistributionMessage'], $msg['messageContextInfo']);

        // Desembrulha wrappers: ephemeral, viewOnce, viewOnceV2, viewOnceV2Extension
        foreach (['ephemeralMessage', 'viewOnceMessage', 'viewOnceMessageV2', 'viewOnceMessageV2Extension'] as $wrapper) {
            if (!empty($msg[$wrapper]['message'])) {
                $msg  = $msg[$wrapper]['message'];
                $type = array_key_first(array_filter($msg, fn($v) => is_array($v))) ?? $type;
            }
        }

        // Mensagem encaminhada com imagem/caption
        if (!empty($msg['imageWithCaptionMessage']['message']['imageMessage'])) {
            $msg['imageMessage'] = $msg['imageWithCaptionMessage']['message']['imageMessage'];
        }
        if (!empty($msg['videoWithCaptionMessage']['message']['videoMessage'])) {
            $msg['videoMessage'] = $msg['videoWithCaptionMessage']['message']['videoMessage'];
        }

        // Texto puro
        if (!empty($msg['conversation'])) {
            return [$msg['conversation'], 'text', null, null];
        }

        // Texto extendido (links, bold, etc.)
        if (!empty($msg['extendedTextMessage']['text'])) {
            return [$msg['extendedTextMessage']['text'], 'text', null, null];
        }

        // Imagem
        if (!empty($msg['imageMessage'])) {
            $im   = $msg['imageMessage'];
            $mime = $im['mimetype'] ?? 'image/jpeg';
            $thumb = $im['jpegThumbnail'] ?? null;
            if (is_array($thumb)) $thumb = null; // Evolution v2 pode enviar como array de bytes
            $url  = $this->resolveMediaUrl($im, $messageId, $mime, $thumb);
            return [$im['caption'] ?? null, 'image', $url, null];
        }

        // Áudio / PTT
        if (!empty($msg['audioMessage'])) {
            $am  = $msg['audioMessage'];
            $url = $this->resolveMediaUrl($am, $messageId, $am['mimetype'] ?? 'audio/ogg', null, 'audio');
            return [null, 'audio', $url, null];
        }

        // Vídeo
        if (!empty($msg['videoMessage'])) {
            $vm   = $msg['videoMessage'];
            $mime = $vm['mimetype'] ?? 'video/mp4';
            $url  = $this->resolveMediaUrl($vm, $messageId, $mime, null, 'video');

            // Salva thumbnail do vídeo (jpegThumbnail do WhatsApp) como _thumb.jpg
            if ($url && !empty($vm['jpegThumbnail']) && is_string($vm['jpegThumbnail'])) {
                $this->saveVideoThumbnail($url, $vm['jpegThumbnail']);
            }

            return [$vm['caption'] ?? null, 'video', $url, null];
        }

        // Documento
        if (!empty($msg['documentMessage'])) {
            $dm  = $msg['documentMessage'];
            $url = $this->resolveMediaUrl($dm, $messageId, $dm['mimetype'] ?? 'application/octet-stream');
            return [$dm['caption'] ?? null, 'document', $url, $dm['fileName'] ?? 'documento'];
        }

        // Documento com legenda
        if (!empty($msg['documentWithCaptionMessage'])) {
            $dm  = $msg['documentWithCaptionMessage']['message']['documentMessage'] ?? [];
            $url = $this->resolveMediaUrl($dm, $messageId, $dm['mimetype'] ?? 'application/octet-stream');
            return [$dm['caption'] ?? null, 'document', $url, $dm['fileName'] ?? 'documento'];
        }

        // Sticker
        if (!empty($msg['stickerMessage'])) {
            $sm  = $msg['stickerMessage'];
            $url = $this->resolveMediaUrl($sm, $messageId, $sm['mimetype'] ?? 'image/webp', null, 'sticker');
            return [null, 'sticker', $url, null];
        }

        // Contato (vCard)
        if (!empty($msg['contactMessage'])) {
            $name = $msg['contactMessage']['displayName'] ?? 'Contato';
            $vcard = $msg['contactMessage']['vcard'] ?? '';
            $phone = '';
            if (preg_match('/TEL[^:]*:([+\d\s\-]+)/i', $vcard, $tm)) {
                $phone = trim($tm[1]);
            }
            return ["📇 *{$name}*" . ($phone ? "\n📱 {$phone}" : ''), 'text', null, null];
        }

        // Array de contatos
        if (!empty($msg['contactsArrayMessage'])) {
            $contacts = $msg['contactsArrayMessage']['contacts'] ?? [];
            $lines = [];
            foreach ($contacts as $c) {
                $name = $c['displayName'] ?? 'Contato';
                $vcard = $c['vcard'] ?? '';
                $phone = '';
                if (preg_match('/TEL[^:]*:([+\d\s\-]+)/i', $vcard, $tm)) {
                    $phone = trim($tm[1]);
                }
                $lines[] = "📇 *{$name}*" . ($phone ? " — {$phone}" : '');
            }
            return [implode("\n", $lines), 'text', null, null];
        }

        return [null, 'text', null, null];
    }

    /**
     * Resolve URL pública para uma mídia recebida via webhook.
     *
     * Ordem de tentativas:
     *   1. base64 já presente no payload (webhookBase64=true)
     *   2. download decifrado via /chat/getBase64FromMediaMessage
     *   3. jpegThumbnail (último recurso, só serve como preview minúsculo)
     *
     * Nunca devolve a URL .enc do mmg.whatsapp.net porque o browser não
     * consegue descriptografar — ela aparecia como ícone quebrado no chat.
     */
    private function resolveMediaUrl(array $node, ?string $messageId, string $mime, ?string $thumbnail = null, string $type = 'image'): ?string
    {
        // 1) Base64 já no payload
        if (!empty($node['base64'])) {
            $saved = $this->saveMedia($node['base64'], $mime, $type);
            if ($saved) {
                Log::info('Mídia: salva via base64 do payload', ['messageId' => $messageId, 'type' => $type]);
                return $saved;
            }
            Log::warning('Mídia: base64 do payload inválido', ['messageId' => $messageId, 'type' => $type]);
        }

        // 2) Download decifrado via Evolution
        if ($messageId) {
            try {
                $fetched = app(\App\Services\EvolutionApiService::class)
                    ->getBase64FromMediaMessage($messageId);

                if ($fetched) {
                    $saved = $this->saveMedia($fetched['base64'], $fetched['mimetype'] ?? $mime, $type);
                    if ($saved) {
                        Log::info('Mídia: salva via getBase64FromMediaMessage', ['messageId' => $messageId, 'type' => $type]);
                        return $saved;
                    }
                    Log::warning('Mídia: getBase64 retornou mas saveMedia falhou', ['messageId' => $messageId, 'type' => $type]);
                } else {
                    Log::warning('Mídia: getBase64FromMediaMessage retornou vazio', ['messageId' => $messageId, 'type' => $type]);
                }
            } catch (\Throwable $e) {
                Log::warning('Mídia: getBase64FromMediaMessage exception', [
                    'messageId' => $messageId,
                    'type'      => $type,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        // 3) Thumbnail como último recurso (apenas preview minúsculo)
        if ($thumbnail) {
            $saved = $this->saveMedia($thumbnail, $mime, $type);
            if ($saved) {
                Log::info('Mídia: salva via thumbnail (fallback)', ['messageId' => $messageId, 'type' => $type]);
                return $saved;
            }
        }

        Log::error('Mídia: TODAS as tentativas falharam', [
            'messageId'    => $messageId,
            'type'         => $type,
            'mime'         => $mime,
            'hasBase64'    => !empty($node['base64']),
            'hasThumbnail' => !empty($thumbnail),
        ]);

        return null;
    }

    /**
     * Salva base64 de mídia em storage/public/media e retorna URL pública.
     * Retorna null se base64 não informado ou inválido.
     */
    private function saveMedia(?string $base64, string $mime, string $type = 'image'): ?string
    {
        if (!$base64) return null;

        // Remove prefixo data URL se presente (ex: "data:image/jpeg;base64,")
        if (str_contains($base64, ',')) {
            [, $base64] = explode(',', $base64, 2);
        }

        $decoded = base64_decode(trim($base64), strict: false);
        if (!$decoded || strlen($decoded) < 10) return null;

        $ext = match (true) {
            str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') => 'jpg',
            str_contains($mime, 'png')   => 'png',
            str_contains($mime, 'gif')   => 'gif',
            str_contains($mime, 'webp')  => 'webp',
            str_contains($mime, 'ogg')   => 'ogg',
            str_contains($mime, 'mpeg')  => 'mp3',
            str_contains($mime, 'mp4')   => 'mp4',
            str_contains($mime, 'webm')  => 'webm',
            str_contains($mime, 'pdf')   => 'pdf',
            default                      => 'bin',
        };

        $baseName = uniqid('msg_', true);
        $dir      = 'media/' . date('Y/m');

        // Tenta otimizar imagens (comprime + gera thumbnail)
        $optimizer = app(\App\Services\ImageOptimizer::class);
        $result    = $optimizer->tryOptimize($decoded, $mime, $type);

        if ($result) {
            // Salva versão otimizada como WebP
            $path = "{$dir}/{$baseName}.jpg";
            MediaStorage::put($path, $result['optimized']);

            // Salva thumbnail ao lado
            $thumbPath = "{$dir}/{$baseName}_thumb.jpg";
            MediaStorage::put($thumbPath, $result['thumbnail']);
        } else {
            // Não é imagem otimizável — salva original
            $path = "{$dir}/{$baseName}.{$ext}";
            MediaStorage::put($path, $decoded);
        }

        return MediaStorage::url($path);
    }

    /**
     * Salva o thumbnail de vídeo (jpegThumbnail do WhatsApp) ao lado do arquivo de vídeo.
     * Usa convenção _thumb pra que o accessor media_thumb_url no Message encontre.
     */
    private function saveVideoThumbnail(string $videoUrl, string $jpegThumbnail): void
    {
        try {
            $decoded = base64_decode(trim($jpegThumbnail), strict: false);
            if (!$decoded || strlen($decoded) < 10) return;

            // Otimiza o thumbnail pra WebP
            $optimizer = app(\App\Services\ImageOptimizer::class);
            $thumb     = $optimizer->thumbnailOnly($decoded);

            // Deriva o path do thumb a partir da URL do vídeo
            // Ex: media/2026/04/msg_xxx.mp4 → media/2026/04/msg_xxx_thumb.jpg
            // A URL pode ser relativa (/storage/...) ou absoluta (https://r2.dev/...)
            $urlPath = parse_url($videoUrl, PHP_URL_PATH);
            $dotPos  = strrpos($urlPath, '.');
            if (!$dotPos) return;

            $thumbRelative = substr($urlPath, 0, $dotPos) . '_thumb.jpg';

            // Remove /storage/ prefix se for URL local
            $thumbRelative = ltrim(str_replace('/storage/', '', $thumbRelative), '/');

            MediaStorage::put($thumbRelative, $thumb);
        } catch (\Throwable $e) {
            Log::warning('saveVideoThumbnail falhou', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Cria card no pipeline vinculado ao departamento (Machinery Prime: Comercial).
     */
    private function createCardForDepartment($contact, $department): void
    {
        try {
            $pipeline = \App\Models\CrmPipeline::where('name', 'Comercial')->first();
            $firstStage = $pipeline?->stages()->orderBy('sort_order')->first();
            if (!$pipeline || !$firstStage) return;

            $exists = \App\Models\CrmCard::where('contact_id', $contact->id)
                ->where('pipeline_id', $pipeline->id)
                ->exists();

            if (!$exists) {
                $card = \App\Models\CrmCard::create([
                    'pipeline_id' => $pipeline->id,
                    'stage_id'    => $firstStage->id,
                    'contact_id'  => $contact->id,
                    'title'       => $contact->display_name,
                ]);

                \App\Models\CrmCardActivity::create([
                    'card_id' => $card->id,
                    'type'    => 'note',
                    'content' => 'Card criado automaticamente (novo lead via WhatsApp ' . $department->name . ')',
                ]);

                Log::info('Card criado automaticamente', [
                    'card'     => $card->id,
                    'contact'  => $contact->name,
                    'pipeline' => $pipeline->name,
                    'dept'     => $department->name,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('createCardForDepartment falhou', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Mapeia a instância Evolution → company_id consultando EvolutionApiConfig.
     * Usa withoutCompanyScope porque o lookup acontece ANTES de qualquer empresa
     * estar setada como tenant ativa.
     */
    private function resolveCompanyId(?string $instanceName): ?int
    {
        if (!$instanceName) return null;

        $companyId = EvolutionApiConfig::withoutCompanyScope()
            ->where('instance_name', $instanceName)
            ->value('company_id');

        return $companyId ? (int) $companyId : null;
    }

    /**
     * Gera variação de uma mensagem via IA (Gemini) para evitar repetição.
     */
    private function generateAiVariation(string $baseText, string $contactName): ?string
    {
        $apiKey = \App\Models\GlobalSetting::get('gemini_api_key');
        $model  = \App\Models\GlobalSetting::get('gemini_model', 'gemini-2.0-flash');

        if (!$apiKey) return null;

        $charCount = mb_strlen($baseText);

        $prompt = "Você é um assistente que reescreve mensagens para WhatsApp Business com variações naturais.\n\n"
            . "MENSAGEM ORIGINAL ({$charCount} caracteres — sua reescrita DEVE ter tamanho similar):\n"
            . "---\n" . $baseText . "\n---\n\n"
            . "DADOS DO CONTATO:\n"
            . "- Nome: {$contactName}\n\n"
            . "REGRAS OBRIGATÓRIAS:\n"
            . "- Reescreva a mensagem INTEIRA mantendo TODAS as informações, dados, valores, datas, horários e serviços\n"
            . "- A mensagem reescrita deve ter aproximadamente o MESMO TAMANHO ({$charCount} caracteres)\n"
            . "- NÃO resuma, NÃO encurte, NÃO omita nenhuma informação\n"
            . "- Varie apenas a forma de escrever: troque palavras, reorganize frases, mude emojis\n"
            . "- Mantenha dados exatos como valores, datas, horários, nomes de serviços inalterados\n"
            . "- Formato WhatsApp: use *negrito* e emojis\n"
            . "- Responda APENAS com a mensagem reescrita, sem explicações";

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = \Illuminate\Support\Facades\Http::timeout(30)->post($url, [
                'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 1.0, 'maxOutputTokens' => 4096],
            ]);

            $json = $response->json();
            $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
            $finishReason = $json['candidates'][0]['finishReason'] ?? 'unknown';

            if ($text) {
                if ($finishReason === 'MAX_TOKENS' || strlen($text) < ($charCount * 0.5)) {
                    Log::warning('AI variation truncada, usando original', [
                        'finishReason' => $finishReason, 'generated' => strlen($text), 'expected' => $charCount,
                    ]);
                    return null;
                }
                Log::info('AI variation gerada', ['length' => strlen($text), 'finishReason' => $finishReason]);
                return trim($text);
            }
            return null;
        } catch (\Throwable $e) {
            Log::warning('AI variation falhou', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
