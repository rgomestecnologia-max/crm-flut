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
                // O remetente real vem em participantPhone (confirmado nos logs do Z-API)
                $senderRaw = $this->payload['participantPhone']
                    ?? $this->payload['senderPhone']
                    ?? $this->payload['participant']
                    ?? $this->payload['sender']
                    ?? null;
                $senderPhone = $senderRaw ? preg_replace('/\D/', '', $senderRaw) : null;

                $senderName = $this->payload['senderName'] ?? $this->payload['notifyName'] ?? null;

                // Cria/busca contato pelo número do remetente (não o ID do grupo)
                if ($senderPhone) {
                    $contact = Contact::firstOrCreate(
                        ['phone' => $senderPhone],
                        ['name'  => $senderName]
                    );
                    if ($senderName && !$contact->name) {
                        $contact->update(['name' => $senderName]);
                    }
                } else {
                    // Sem remetente identificável — usa o ID do grupo como contato
                    $contact = Contact::firstOrCreate(
                        ['phone' => $phone],
                        ['name'  => $groupName ?? 'Grupo']
                    );
                }

                // Busca ou cria conversa do grupo (identificada pelo phone do grupo)
                $conversation = Conversation::where('contact_id', $contact->id)
                    ->where('is_group', true)
                    ->whereIn('status', ['open', 'pending'])
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

                $conversation = Conversation::where('contact_id', $contact->id)
                    ->where('is_group', false)
                    ->whereIn('status', ['open', 'pending', 'resolved'])
                    ->latest()
                    ->first();

                if (!$conversation) {
                    if ($fromMe) return;

                    $department = Department::active()->first();
                    if (!$department) {
                        Log::error('ProcessIncomingMessage: no active department found');
                        return;
                    }

                    $conversation = Conversation::create([
                        'contact_id'    => $contact->id,
                        'department_id' => $department->id,
                        'status'        => 'open',
                        'is_group'      => false,
                    ]);
                } elseif (!$fromMe && $conversation->status === 'resolved') {
                    // Reabre conversa resolvida quando o contato manda nova mensagem
                    $conversation->update(['status' => 'open']);
                }
            }

            // Atualiza foto de perfil do WhatsApp a partir do payload (Z-API envia no campo "photo")
            if (isset($contact)) {
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

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_type'     => $senderType,
                'sender_id'       => null, // enviado direto pelo WhatsApp, sem usuário CRM
                'content'         => $content,
                'type'            => $type,
                'media_url'       => $mediaUrl,
                'media_filename'  => $mediaFilename,
                'zapi_message_id' => $zapiId,
                'delivery_status' => $deliveryStatus,
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

                    Log::info('Bot dispatch', [
                        'conv'         => $conversation->id,
                        'msg'          => $message->id,
                        'automationAi' => $automationAi,
                        'menuActive'   => $menuConfig?->is_active,
                        'aiActive'     => $botConfig?->is_active,
                        'skipMenu'     => $skipMenu,
                    ]);

                    if ($menuConfig && $menuConfig->is_active && !$skipMenu) {
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

        $query = ZapiConfig::withoutCompanyScope();

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
