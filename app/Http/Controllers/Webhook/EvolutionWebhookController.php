<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessEvolutionMessage;
use App\Models\EvolutionApiConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EvolutionWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $event   = $payload['event'] ?? 'unknown';

        Log::info('Evolution Webhook recebido', [
            'event'    => $event,
            'instance' => $payload['instance'] ?? null,
            'keys'     => array_keys($payload),
            'raw'      => substr($request->getContent(), 0, 1200),
        ]);

        // Localiza a config DA EMPRESA dona da instância pelo nome — bypass do
        // global scope porque o webhook chega sem nenhuma sessão/empresa setada.
        // É a fonte de verdade pra rotear esse webhook.
        $instanceName = $payload['instance'] ?? null;
        $config       = $this->resolveConfig($instanceName);

        // ── Atualiza status de conexão ──────────────────────────────────────
        if ($event === 'connection.update') {
            $state = $payload['data']['state'] ?? null;
            if ($state && $config) {
                $updates = ['connection_status' => $state];
                if ($state === 'open') {
                    $updates['qr_code']      = null;
                    $updates['pairing_code'] = null;
                }
                $config->update($updates);
            }
            return response()->json(['ok' => true]);
        }

        // ── QR Code atualizado ─────────────────────────────────────────────
        if ($event === 'qrcode.updated') {
            $qrBase64    = $payload['data']['qrcode']['base64'] ?? $payload['data']['base64'] ?? null;
            $pairingCode = $payload['data']['pairingCode'] ?? $payload['data']['qrcode']['pairingCode'] ?? null;

            Log::info('Evolution QR Code atualizado', ['pairingCode' => $pairingCode, 'instance' => $instanceName]);

            if ($config && $qrBase64) {
                $config->update([
                    'qr_code'           => $qrBase64,
                    'pairing_code'      => $pairingCode,
                    'connection_status' => 'connecting',
                ]);
            }
            return response()->json(['ok' => true]);
        }

        // ── Atualização de contato (salva avatar do WhatsApp) ──────────────
        if (in_array($event, ['contacts.upsert', 'contacts.update'])) {
            $contacts = $payload['data'] ?? [];
            // Pode vir como array de objetos ou objeto único
            if (!isset($contacts[0])) $contacts = [$contacts];

            foreach ($contacts as $contactData) {
                $jid      = $contactData['id'] ?? null;
                $photoUrl = $contactData['profilePictureUrl'] ?? null;
                $pushName = $contactData['pushName'] ?? null;

                if (!$jid || !$photoUrl) continue;
                if (str_contains($jid, '@g.us')) continue; // ignora grupos

                $phone = preg_replace('/\D/', '', preg_replace('/@.+/', '', $jid));
                if (!$phone) continue;

                // Atualiza avatar_url do contato (sem company scope — cross-tenant)
                \App\Models\Contact::withoutCompanyScope()
                    ->where(function ($q) use ($jid, $phone) {
                        $q->where('chat_lid', $jid)->orWhere('phone', $phone);
                    })
                    ->whereNull('avatar_url')
                    ->update(['avatar_url' => $photoUrl]);
            }

            return response()->json(['ok' => true]);
        }

        // ── Mensagens recebidas / enviadas ─────────────────────────────────
        if ($event === 'messages.upsert') {
            $data    = $payload['data'] ?? [];
            $fromMe  = $data['key']['fromMe'] ?? false;
            $msgType = $data['messageType'] ?? null;

            // Ignora: status do WhatsApp, protocolMessages
            $ignored = ['protocolMessage', 'ephemeralMessage', 'senderKeyDistributionMessage'];
            if (in_array($msgType, $ignored)) {
                return response()->json(['ok' => true]);
            }

            ProcessEvolutionMessage::dispatch($payload);

            Log::info('Evolution job despachado', [
                'messageType' => $msgType,
                'fromMe'      => $fromMe,
                'jid'         => $data['key']['remoteJid'] ?? null,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Resolve a EvolutionApiConfig pelo nome da instância no payload do webhook.
     * Bypass do global scope porque chamado fora de sessão e antes do tenant
     * estar setado. É o ponto de entrada que define qual empresa é responsável.
     */
    protected function resolveConfig(?string $instanceName): ?EvolutionApiConfig
    {
        if (!$instanceName) return null;

        return EvolutionApiConfig::withoutCompanyScope()
            ->where('instance_name', $instanceName)
            ->first();
    }
}
