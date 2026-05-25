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

        $rawContent = $request->getContent();

        // Validação: verifica se a apikey do payload corresponde à instância
        $instanceName = $payload['instance'] ?? null;
        $payloadKey   = $payload['apikey'] ?? $request->header('apikey') ?? null;
        if ($instanceName && $payloadKey) {
            $config = EvolutionApiConfig::withoutCompanyScope()
                ->where('instance_name', $instanceName)
                ->first();
            if ($config && $config->instance_api_key && $config->instance_api_key !== $payloadKey) {
                Log::warning('Webhook rejeitado: apikey inválida', ['instance' => $instanceName]);
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        Log::info('Evolution Webhook recebido', [
            'event'    => $event,
            'instance' => $instanceName,
            'keys'     => array_keys($payload),
            'raw'      => substr($rawContent, 0, 1200),
        ]);

        // Log temporário: captura qualquer evento que contenha "edit"
        if (stripos($rawContent, 'edit') !== false) {
            Log::info('EDIT DETECTADO no webhook', [
                'event' => $event,
                'instance' => $payload['instance'] ?? null,
                'messageType' => $payload['data']['messageType'] ?? null,
                'raw' => substr($rawContent, 0, 2000),
            ]);
        }

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

                // Notificação automática ao desconectar
                if ($state === 'close') {
                    $company = \App\Models\Company::find($config->company_id);
                    \App\Models\Notification::create([
                        'company_id' => $config->company_id,
                        'type'       => 'whatsapp_disconnected',
                        'title'      => 'WhatsApp desconectado: ' . ($company->name ?? $instanceName),
                        'message'    => 'A conexão WhatsApp da instância ' . $instanceName . ' caiu. Reconecte em Configurações > WhatsApp.',
                        'data'       => ['instance' => $instanceName],
                    ]);
                }
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
                $jid      = $contactData['remoteJid'] ?? $contactData['id'] ?? null;
                $photoUrl = $contactData['profilePicUrl'] ?? $contactData['profilePictureUrl'] ?? null;
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

        // ── Status de mensagem (entregue, lida, revogada) ──────────────────
        if ($event === 'messages.update') {
            $data   = $payload['data'] ?? [];
            $msgId  = $data['keyId'] ?? $data['id'] ?? $data['key']['id'] ?? null;
            $status = $data['status'] ?? null;

            // Mensagem revogada/excluída
            if ($msgId && ($status === 'REVOKE' || ($data['messageStubType'] ?? null) === 'REVOKE')) {
                $msg = \App\Models\Message::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                    ->where('zapi_message_id', $msgId)
                    ->first();

                if ($msg) {
                    Log::info('Mensagem excluída pelo remetente', ['message_id' => $msg->id, 'zapi_id' => $msgId]);
                    $msg->delete();
                }
            }

            // Rastrear entrega e leitura (broadcast + mensagens regulares)
            if ($msgId && in_array($status, ['DELIVERY_ACK', 'READ', 'PLAYED'])) {
                $deliveryStatus = match ($status) {
                    'DELIVERY_ACK' => 'delivered',
                    'READ', 'PLAYED' => 'read',
                };

                // Atualizar mensagem regular
                \App\Models\Message::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                    ->where('zapi_message_id', $msgId)
                    ->whereIn('delivery_status', ['pending', 'sent', 'delivered'])
                    ->update(['delivery_status' => $deliveryStatus]);

                // Atualizar recipient de broadcast
                $recipient = \App\Models\BroadcastCampaignRecipient::where('message_id', $msgId)->first();
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

            return response()->json(['ok' => true]);
        }

        // ── Mensagem excluída (evento messages.delete) ────────────────────
        if ($event === 'messages.delete') {
            $data  = $payload['data'] ?? [];
            $msgId = $data['keyId'] ?? $data['id'] ?? $data['key']['id'] ?? null;

            if ($msgId) {
                $msg = \App\Models\Message::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                    ->where('zapi_message_id', $msgId)
                    ->first();

                if ($msg) {
                    Log::info('Mensagem excluída (messages.delete)', ['message_id' => $msg->id, 'zapi_id' => $msgId]);
                    $msg->delete();
                }
            }

            return response()->json(['ok' => true]);
        }

        // ── Mensagem editada ───────────────────────────────────────────────
        if ($event === 'messages.edit' || $event === 'messages.edited') {
            $data     = $payload['data'] ?? [];
            $msgId    = $data['key']['id'] ?? $data['oldKey']['id'] ?? null;
            $newText  = $data['editedMessage']['conversation']
                     ?? $data['editedMessage']['extendedTextMessage']['text']
                     ?? $data['message']['conversation']
                     ?? $data['message']['extendedTextMessage']['text']
                     ?? $data['newMessage']['conversation']
                     ?? $data['newMessage']['extendedTextMessage']['text']
                     ?? null;

            Log::info('Evolution: mensagem editada', ['msgId' => $msgId, 'newText' => substr($newText ?? '', 0, 100)]);

            if ($msgId && $newText) {
                $msg = \App\Models\Message::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                    ->where('zapi_message_id', $msgId)
                    ->first();

                if ($msg) {
                    $msg->update(['content' => $newText]);
                    try { broadcast(new \App\Events\MessageReceived($msg)); } catch (\Throwable) {}
                    Log::info('Mensagem editada pelo WhatsApp', ['msg_id' => $msg->id]);
                }
            }

            return response()->json(['ok' => true]);
        }

        // ── Mensagens recebidas / enviadas ─────────────────────────────────
        if ($event === 'messages.upsert') {
            $data    = $payload['data'] ?? [];
            $fromMe  = $data['key']['fromMe'] ?? false;
            $msgType = $data['messageType'] ?? null;

            // Ignora: distribuição de chave de grupo
            // protocolMessage pode conter edições — só ignora se NÃO for edição
            if ($msgType === 'senderKeyDistributionMessage') {
                return response()->json(['ok' => true]);
            }
            if ($msgType === 'protocolMessage') {
                $hasEdit = !empty($data['message']['protocolMessage']['editedMessage'])
                        || !empty($data['message']['editedMessage']);
                if (!$hasEdit) {
                    return response()->json(['ok' => true]);
                }
                // É uma edição — tratar como editedMessage
                $payload['data']['messageType'] = 'editedMessage';
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
