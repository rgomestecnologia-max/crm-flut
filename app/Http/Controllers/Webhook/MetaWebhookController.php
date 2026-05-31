<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMetaMessage;
use App\Models\Message;
use App\Models\MetaWhatsAppConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MetaWebhookController extends Controller
{
    /**
     * Verificação do webhook (GET) — Meta envia isso ao configurar.
     */
    public function verify(Request $request): Response
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe') {
            // Busca config que tenha esse verify_token
            $config = MetaWhatsAppConfig::withoutGlobalScopes()
                ->where('verify_token', $token)
                ->first();

            if ($config) {
                Log::info('MetaWebhook: verificação OK', ['phone_number_id' => $config->phone_number_id]);
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }
        }

        Log::warning('MetaWebhook: verificação falhou', ['token' => $token]);
        return response('Forbidden', 403);
    }

    /**
     * Recebe webhooks de mensagens e status (POST).
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $object  = $payload['object'] ?? '';
        $entries = $payload['entry'] ?? [];

        // Messenger: object = 'page'
        if ($object === 'page') {
            foreach ($entries as $entry) {
                $pageId = $entry['id'] ?? null;
                $messaging = $entry['messaging'] ?? [];
                foreach ($messaging as $event) {
                    if (!isset($event['message'])) continue;
                    $config = MetaWhatsAppConfig::withoutGlobalScopes()
                        ->where('page_id', $pageId)->where('messenger_enabled', true)->first();
                    if (!$config) continue;
                    \App\Jobs\ProcessMessengerMessage::dispatch($config, $event, 'messenger');
                }
            }
            return response()->json(['status' => 'ok']);
        }

        // Instagram: object = 'instagram'
        if ($object === 'instagram') {
            foreach ($entries as $entry) {
                $igId = $entry['id'] ?? null;
                $messaging = $entry['messaging'] ?? [];
                foreach ($messaging as $event) {
                    if (!isset($event['message'])) continue;
                    $config = MetaWhatsAppConfig::withoutGlobalScopes()
                        ->where('instagram_account_id', $igId)->where('instagram_enabled', true)->first();
                    if (!$config) continue;
                    \App\Jobs\ProcessMessengerMessage::dispatch($config, $event, 'instagram');
                }
            }
            return response()->json(['status' => 'ok']);
        }

        // WhatsApp: object = 'whatsapp_business_account'
        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

                if (!$phoneNumberId) continue;

                $config = MetaWhatsAppConfig::withoutGlobalScopes()
                    ->where('phone_number_id', $phoneNumberId)->first();

                if (!$config) {
                    Log::warning('MetaWebhook: config não encontrada', ['phone_number_id' => $phoneNumberId]);
                    continue;
                }

                $statuses = $value['statuses'] ?? [];
                foreach ($statuses as $status) {
                    $this->processStatus($status);
                }

                $messages = $value['messages'] ?? [];
                $contacts = $value['contacts'] ?? [];

                foreach ($messages as $message) {
                    $contactInfo = $contacts[0] ?? null;
                    ProcessMetaMessage::dispatch($config->company_id, $message, $contactInfo, $phoneNumberId);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Atualiza delivery_status das mensagens enviadas.
     */
    private function processStatus(array $status): void
    {
        $messageId    = $status['id'] ?? null;
        $statusValue  = $status['status'] ?? null;

        if (!$messageId || !$statusValue) {
            return;
        }

        $deliveryStatus = match ($statusValue) {
            'sent'      => 'sent',
            'delivered' => 'delivered',
            'read'      => 'read',
            'failed'    => 'failed',
            default     => null,
        };

        if (!$deliveryStatus) {
            return;
        }

        Message::where('zapi_message_id', $messageId)
            ->whereIn('delivery_status', ['pending', 'sent', 'delivered'])
            ->update(['delivery_status' => $deliveryStatus]);

        // Rastrear broadcast recipients
        if (in_array($deliveryStatus, ['delivered', 'read'])) {
            $recipient = \App\Models\BroadcastCampaignRecipient::where('message_id', $messageId)->first();
            if ($recipient) {
                $updates = [];
                if ($deliveryStatus === 'delivered' && !$recipient->delivered_at) {
                    $updates['delivered_at'] = now();
                    $updates['status'] = 'delivered';
                }
                if ($deliveryStatus === 'read' && !$recipient->read_at) {
                    $updates['read_at'] = now();
                    $updates['status'] = 'read';
                    if (!$recipient->delivered_at) $updates['delivered_at'] = now();
                }
                if (!empty($updates)) $recipient->update($updates);
            }
        }
    }
}
