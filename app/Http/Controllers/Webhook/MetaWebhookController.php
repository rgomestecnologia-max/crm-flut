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

        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

                if (!$phoneNumberId) {
                    continue;
                }

                // Resolve a empresa pelo phone_number_id
                $config = MetaWhatsAppConfig::withoutGlobalScopes()
                    ->where('phone_number_id', $phoneNumberId)
                    ->first();

                if (!$config) {
                    Log::warning('MetaWebhook: config não encontrada', ['phone_number_id' => $phoneNumberId]);
                    continue;
                }

                // Processa status updates (sent/delivered/read/failed)
                $statuses = $value['statuses'] ?? [];
                foreach ($statuses as $status) {
                    $this->processStatus($status);
                }

                // Processa mensagens recebidas
                $messages = $value['messages'] ?? [];
                $contacts = $value['contacts'] ?? [];

                foreach ($messages as $message) {
                    $contactInfo = $contacts[0] ?? null;

                    ProcessMetaMessage::dispatch(
                        $config->company_id,
                        $message,
                        $contactInfo,
                        $phoneNumberId,
                    );
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
    }
}
