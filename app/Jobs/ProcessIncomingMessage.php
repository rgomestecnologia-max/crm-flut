<?php

namespace App\Jobs;

use App\Events\MessageReceived;
use App\Models\AiBotConfig;
use App\Models\ChatbotMenuConfig;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\Message;
use App\Models\ZapiConfig;
use App\Jobs\ProcessMessageReaction;
use App\Services\CurrentCompany;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessIncomingMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public array $payload) {}

    public function handle(): void
    {
        // Resolve a empresa pelo instanceId/connectedPhone do payload Z-API.
        $companyId = $this->resolveCompanyId();

        if (!$companyId) {
            Log::warning('ProcessIncomingMessage: payload Z-API sem empresa correspondente — descartando', [
                'instanceId'     => $this->payload['instanceId'] ?? null,
                'connectedPhone' => $this->payload['connectedPhone'] ?? null,
            ]);
            return;
        }

        app(CurrentCompany::class)->set($companyId, persist: false);

        try {
            // Reações chegam como ReceivedCallback com campo "reaction" no payload
            if (!empty($this->payload['reaction'])) {
                ProcessMessageReaction::dispatch($this->payload);
                return;
            }

            $phone = $this->normalizePhone($this->payload['phone'] ?? '');
            if (!$phone) return;

            $fromMe    = ($this->payload['fromMe'] ?? false) === true;
            $isGroup   = ($this->payload['isGroup'] ?? false) === true;
            $groupName = $isGroup ? ($this->payload['chatName'] ?? $this->payload['groupName'] ?? null) : null;

            // Ignora canais/newsletters — grupos têm JIDs que também começam com 120363, então só filtra se não for grupo
            if (!$isGroup) {
                $phoneRaw     = (string) ($this->payload['phone'] ?? '');
                $isNewsletter = ($this->payload['isNewsletter'] ?? false) === true
                    || str_contains($phoneRaw, '@newsletter')
                    || str_contains((string) ($this->payload['chatId'] ?? ''), '@newsletter')
                    || preg_match('/^1203\d+/', preg_replace('/\D/', '', $phoneRaw));
                if ($isNewsletter) return;
            }

            $chatLid = $this->payload['chatLid'] ?? null;

            if ($isGroup) {
                // ── Mensagem de grupo ────────────────────────────────────
                Log::info('Z-API GROUP payload', [
                    'phone' => $this->payload['phone'] ?? null,
                    'chatId' => $this->payload['chatId'] ?? null,
                    'chat' => $this->payload['chat'] ?? null,
                    'chatName' => $this->payload['chatName'] ?? null,
                    'participantPhone' => $this->payload['participantPhone'] ?? null,
                    'senderName' => $this->payload['senderName'] ?? null,
                ]);

                // O remetente real vem em participantPhone ou phone
                $senderPhone = $this->normalizePhone(
                    $this->payload['participantPhone']
                    ?? $this->payload['senderPhone']
                    ?? $this->payload['phone']
                    ?? ''
                );
                $senderName = $this->payload['senderName']
                    ?? $this->payload['notifyName']
                    ?? $this->payload['pushName']
                    ?? null;

                // ID do grupo: chatId tem o JID correto (@g.us)
                // Z-API pode enviar como "5511970620020-1548262453@g.us" ou "120363xxx@g.us"
                $groupJid = $this->payload['chatId'] ?? $this->payload['chat'] ?? null;

                // Se chatId não tem @g.us, tenta construir do phone
                if (!$groupJid || !str_contains($groupJid, '@g.us')) {
                    // phone pode ter o JID do grupo quando vem com @g.us
                    $rawPhone = $this->payload['phone'] ?? '';
                    if (str_contains($rawPhone, '@g.us')) {
                        $groupJid = $rawPhone;
                    } else {
                        $groupJid = preg_replace('/\D/', '', $rawPhone) . '@g.us';
                    }
                }

                // Preservar hífen no phone do grupo (ex: 5511970620020-1548262453)
                $groupPhone = preg_replace('/@.*/', '', $groupJid);
                // Truncar a 20 chars para caber no campo phone (compatível com Evolution)
                $groupPhone = substr($groupPhone, 0, 20);

                // Busca por chat_lid exato ou por variação sem hífen
                $groupJidNoHyphen = preg_replace('/[^0-9@.a-z]/', '', $groupJid);
                $contact = Contact::where('chat_lid', $groupJid)->first()
                    ?? Contact::where('chat_lid', $groupJidNoHyphen)->first()
                    ?? Contact::where('phone', $groupPhone)->first();

                if (!$contact) {
                    $contact = Contact::create([
                        'phone'    => $groupPhone,
                        'name'     => $groupName ?? 'Grupo',
                        'chat_lid' => $groupJid,
                    ]);
                }

                if ($groupName && (!$contact->name || preg_match('/^\d{10,}$/', $contact->name))) {
                    $contact->update(['name' => $groupName]);
                }
                if ($groupJid && !$contact->chat_lid) {
                    $contact->update(['chat_lid' => $groupJid]);
                }

                // Busca ou cria conversa do grupo
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
                } elseif ($groupName && !$conversation->group_name) {
                    $conversation->update(['group_name' => $groupName]);
                }

            } else {
                // ── Mensagem individual ───────────────────────────────────
                if ($fromMe) {
                    $contact = null;
                    if ($chatLid) {
                        $contact = Contact::where('chat_lid', $chatLid)->first();
                    }
                    if (!$contact && $phone && !str_contains($this->payload['phone'] ?? '', '@')) {
                        $contact = Contact::where('phone', $phone)->first();
                    }
                    if (!$contact) return;
                } else {
                    $contact = Contact::firstOrCreate(
                        ['phone' => $phone],
                        ['name'  => $this->payload['senderName'] ?? null]
                    );
                    if (!empty($this->payload['senderName']) && !$contact->name) {
                        $contact->update(['name' => $this->payload['senderName']]);
                    }
                    if ($chatLid && !$contact->chat_lid) {
                        $contact->update(['chat_lid' => $chatLid]);
                    }
                }

                // Resolve a evolution_api_config que usa Z-API (para multi-instância)
                $zapiEvoConfig = \App\Models\EvolutionApiConfig::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('api_provider', 'zapi')
                    ->where('is_active', true)
                    ->first();
                $evoConfigId = $zapiEvoConfig?->id;

                $conversation = Conversation::where('contact_id', $contact->id)
                    ->where('is_group', false)
                    ->when($evoConfigId, fn($q) => $q->where(function ($q2) use ($evoConfigId) {
                        $q2->where('evolution_api_config_id', $evoConfigId)
                           ->orWhereNull('evolution_api_config_id');
                    }))
                    ->whereIn('status', ['open', 'pending', 'resolved'])
                    ->latest()
                    ->first();

                if (!$conversation) {
                    if ($fromMe) return;

                    $department = $zapiEvoConfig && $zapiEvoConfig->default_department_id
                        ? Department::find($zapiEvoConfig->default_department_id)
                        : Department::active()->first();
                    if (!$department) {
                        Log::error('ProcessIncomingMessage: no active department found');
                        return;
                    }

                    $conversation = Conversation::create([
                        'contact_id'              => $contact->id,
                        'department_id'            => $department->id,
                        'evolution_api_config_id'  => $evoConfigId,
                        'status'                   => 'open',
                        'is_group'                 => false,
                    ]);
                } elseif (!$fromMe && $conversation->status === 'resolved') {
                    // Cliente reabriu conversa encerrada → reset completo → URA entra
                    $conversation->update([
                        'status'               => 'open',
                        'assigned_to'          => null,
                        'menu_awaiting'        => false,
                        'waiting_human_reason' => null,
                    ]);
                    Message::where('conversation_id', $conversation->id)
                        ->where('sender_type', 'system')
                        ->where('content', 'like', 'Menu: cliente selecionou%')
                        ->delete();
                } elseif ($fromMe && $conversation->status === 'resolved') {
                    // Humano reabriu pelo WhatsApp direto → URA não entra
                    $conversation->update([
                        'status'               => 'open',
                        'waiting_human_reason' => 'Atendente respondeu pelo WhatsApp',
                    ]);
                }
            }

            // Atualiza foto de perfil do WhatsApp (apenas conversas individuais, não grupos)
            if (isset($contact) && !$isGroup) {
                $senderPhoto = $this->payload['photo'] ?? $this->payload['senderPhoto'] ?? null;
                if ($senderPhoto && $contact->avatar_url !== $senderPhoto) {
                    $contact->update(['avatar_url' => $senderPhoto]);
                }
            }

            // Extrai conteúdo e tipo da mensagem
            [$content, $type, $mediaUrl, $mediaFilename] = $this->extractMessageData($this->payload);

            // Evita duplicatas por zapi_message_id
            $zapiId = $this->payload['messageId'] ?? null;
            if ($zapiId && Message::where('zapi_message_id', $zapiId)->exists()) {
                return;
            }

            $senderType     = $fromMe ? 'agent' : 'contact';
            $deliveryStatus = $fromMe ? 'sent'  : 'delivered';

            // Quoted message (reply context)
            $replyToId = null;
            $quotedMsgId = $this->payload['quotedMsg']['messageId'] ?? $this->payload['contextInfo']['stanzaId'] ?? null;
            if ($quotedMsgId) {
                $quotedMsg = Message::where('zapi_message_id', $quotedMsgId)->first();
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
                'zapi_message_id' => $zapiId,
                'delivery_status' => $deliveryStatus,
                'reply_to_id'     => $replyToId,
            ]);

            $conversation->update(['last_message_at' => now(), 'status' => 'open']);

            // Dispara bot de atendimento se mensagem veio do contato
            // Isolado em try-catch para que falha do bot não impeça o broadcast
            if (!$fromMe) {
                try {
                    $menuConfig = ChatbotMenuConfig::current();
                    $botConfig  = AiBotConfig::current();

                    // Verifica se a conversa veio de uma automação com IA habilitada
                    $automationAi = false;
                    if (!$isGroup && $conversation->source_automation_id) {
                        $sourceAutomation = $conversation->sourceAutomation;
                        $automationAi = $sourceAutomation?->enable_ai_on_reply === true;
                    }

                    // Conversas de automação com IA habilitada pulam o menu e vão direto para a IA
                    $skipMenu = $automationAi && $botConfig && $botConfig->is_active && $botConfig->hasKey();

                    // Não envia chatbot/IA em grupos se reply_in_groups está desativado
                    $skipGroups = $isGroup && (!$menuConfig || !$menuConfig->reply_in_groups);

                    if ($skipGroups) {
                        // Grupo sem permissão de bot — ignora
                    } elseif ($menuConfig && $menuConfig->is_active && !$skipMenu) {
                        \App\Jobs\ProcessMenuBot::dispatch($conversation, $menuConfig, $botConfig, $message->id);
                    } elseif ($botConfig && $botConfig->is_active && $botConfig->hasKey()) {
                        \App\Jobs\ProcessBotResponse::dispatch($conversation, $botConfig, $message->id);
                    }
                } catch (\Throwable $botException) {
                    Log::warning('Bot dispatch falhou (continuando com broadcast)', ['error' => $botException->getMessage()]);
                }
            }

            // Broadcast sempre dispara, independente do bot
            try {
                broadcast(new MessageReceived($message))->toOthers();
            } catch (\Throwable $broadcastException) {
                Log::warning('Broadcast falhou (Reverb offline?)', ['error' => $broadcastException->getMessage()]);
            }

        } catch (\Throwable $e) {
            Log::error('ProcessIncomingMessage failed', [
                'payload' => $this->payload,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            // Não re-lança: webhook deve sempre retornar 200 para o Z-API
        }
    }

    private function extractMessageData(array $payload): array
    {
        $content       = null;
        $type          = 'text';
        $mediaUrl      = null;
        $mediaFilename = null;

        if (!empty($payload['text']['message'])) {
            $content = $payload['text']['message'];
            $type    = 'text';
        } elseif (!empty($payload['image']['imageUrl'])) {
            $mediaUrl = $payload['image']['imageUrl'];
            $content  = $payload['image']['caption'] ?? null;
            $type     = 'image';
        } elseif (!empty($payload['audio']['audioUrl'])) {
            $mediaUrl = $payload['audio']['audioUrl'];
            $type     = 'audio';
        } elseif (!empty($payload['document']['documentUrl'])) {
            $mediaUrl      = $payload['document']['documentUrl'];
            $mediaFilename = $payload['document']['fileName'] ?? 'documento';
            $mime          = $payload['document']['mimetype'] ?? '';
            if ($mediaFilename && !pathinfo($mediaFilename, PATHINFO_EXTENSION)) {
                $ext = str_contains($mime, 'pdf') ? 'pdf' : (str_contains($mime, 'word') ? 'docx' : (str_contains($mime, 'excel') ? 'xlsx' : null));
                if ($ext) $mediaFilename .= '.' . $ext;
            }
            $type          = 'document';
        } elseif (!empty($payload['video']['videoUrl'])) {
            $mediaUrl = $payload['video']['videoUrl'];
            $content  = $payload['video']['caption'] ?? null;
            $type     = 'video';
        } elseif (!empty($payload['sticker']['stickerUrl'])) {
            $mediaUrl = $payload['sticker']['stickerUrl'];
            $type     = 'sticker';
        }

        return [$content, $type, $mediaUrl, $mediaFilename];
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D/', '', $phone);
    }

    /**
     * Mapeia o webhook Z-API → company_id.
     * Tenta primeiro o instanceId; se não houver, cai para o connectedPhone.
     * Como Z-API é legacy, este job tende a ser desativado quando todas as
     * empresas migrarem para Evolution.
     */
    private function resolveCompanyId(): ?int
    {
        $instanceId     = $this->payload['instanceId'] ?? null;
        $connectedPhone = $this->payload['connectedPhone'] ?? null;

        $query = ZapiConfig::withoutCompanyScope()->where('is_active', true);

        if ($instanceId) {
            $companyId = (clone $query)->where('instance_id', $instanceId)->value('company_id');
            if ($companyId) return (int) $companyId;
        }

        if ($connectedPhone) {
            $normalized = preg_replace('/\D/', '', $connectedPhone);
            $companyId  = (clone $query)->where('phone_number', $normalized)->value('company_id');
            if ($companyId) return (int) $companyId;
        }

        return null;
    }
}
