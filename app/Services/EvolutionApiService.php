<?php

namespace App\Services;

use App\Models\EvolutionApiConfig;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionApiService
{
    private ?string $serverUrl;
    private ?string $globalKey;
    private ?string $instanceName;
    private ?string $instanceKey;

    public function __construct(?EvolutionApiConfig $config = null)
    {
        $config ??= EvolutionApiConfig::current();

        $this->serverUrl    = $config?->serverUrl();
        $this->globalKey    = $config?->global_api_key;
        $this->instanceName = $config?->instance_name;
        $this->instanceKey  = $config?->instance_api_key ?: $config?->global_api_key;
    }

    // ─── Instância ────────────────────────────────────────────────────────

    public function serverInfo(): array
    {
        return $this->get('/', useGlobalKey: true, skipInstance: true);
    }

    public function createInstance(array $params): array
    {
        return $this->post('/instance/create', $params, useGlobalKey: true);
    }

    public function fetchInstances(?string $name = null): array
    {
        $query = $name ? "?instanceName={$name}" : '';
        return $this->get("/instance/fetchInstances{$query}", useGlobalKey: true);
    }

    public function connectInstance(): array
    {
        return $this->get("/instance/connect/{$this->instanceName}");
    }

    public function connectionState(): array
    {
        return $this->get("/instance/connectionState/{$this->instanceName}");
    }

    public function restartInstance(): array
    {
        return $this->put("/instance/restart/{$this->instanceName}");
    }

    public function logoutInstance(): array
    {
        return $this->delete("/instance/logout/{$this->instanceName}");
    }

    public function deleteInstance(): array
    {
        return $this->delete("/instance/delete/{$this->instanceName}", useGlobalKey: true);
    }

    public function setPresence(string $presence = 'available'): array
    {
        return $this->post("/instance/setPresence/{$this->instanceName}", [
            'presence' => $presence,
        ]);
    }

    // ─── Configurações ─────────────────────────────────────────────────────

    public function getSettings(): array
    {
        return $this->get("/settings/find/{$this->instanceName}");
    }

    public function setSettings(array $settings): array
    {
        return $this->post("/settings/set/{$this->instanceName}", $settings);
    }

    // ─── Webhook ──────────────────────────────────────────────────────────

    public function getWebhook(): array
    {
        return $this->get("/webhook/find/{$this->instanceName}");
    }

    public function setWebhook(string $url, array $events = [], bool $byEvents = false, bool $base64 = false): array
    {
        if (empty($events)) {
            $events = [
                'MESSAGES_UPSERT', 'MESSAGES_UPDATE', 'MESSAGES_DELETE',
                'CONNECTION_UPDATE', 'QRCODE_UPDATED',
                'CONTACTS_UPSERT', 'CONTACTS_UPDATE',
                'CHATS_UPSERT', 'CHATS_UPDATE',
                'GROUPS_UPSERT', 'GROUP_UPDATE',
                'CALL',
            ];
        }

        return $this->post("/webhook/set/{$this->instanceName}", [
            'enabled'          => true,
            'url'              => $url,
            'webhookByEvents'  => $byEvents,
            'webhookBase64'    => $base64,
            'events'           => $events,
        ]);
    }

    public function disableWebhook(): array
    {
        return $this->post("/webhook/set/{$this->instanceName}", [
            'enabled'          => false,
            'url'              => '',
            'webhookByEvents'  => false,
            'webhookBase64'    => false,
            'events'           => [],
        ]);
    }

    // ─── Envio de mensagens ─────────────────────────────────────────────────

    public function sendText(string $phone, string $text, ?string $quotedId = null): array
    {
        $number = $this->normalizePhone($phone);

        $body = [
            'number'      => $number,
            'textMessage' => ['text' => $text],
        ];

        if ($quotedId) {
            $body['quoted'] = ['key' => ['id' => $quotedId]];
        }

        return $this->post("/message/sendText/{$this->instanceName}", $body);
    }

    public function sendImage(string $phone, string $mediaUrlOrBase64, string $caption = ''): array
    {
        [$media, $mime] = $this->extractMedia($mediaUrlOrBase64, 'image/jpeg');

        return $this->post("/message/sendMedia/{$this->instanceName}", [
            'number'       => $this->normalizePhone($phone),
            'mediaMessage' => [
                'mediatype' => 'image',
                'mimetype'  => $mime,
                'caption'   => $caption,
                'media'     => $media,
                'fileName'  => 'image.' . $this->mimeToExt($mime),
            ],
        ]);
    }

    public function sendDocument(string $phone, string $mediaUrlOrBase64, string $fileName, string $mimetype = 'application/pdf'): array
    {
        [$media, $mime] = $this->extractMedia($mediaUrlOrBase64, $mimetype);

        return $this->post("/message/sendMedia/{$this->instanceName}", [
            'number'       => $this->normalizePhone($phone),
            'mediaMessage' => [
                'mediatype' => 'document',
                'mimetype'  => $mime,
                'caption'   => $fileName,
                'media'     => $media,
                'fileName'  => $fileName,
            ],
        ]);
    }

    public function sendAudio(string $phone, string $audioUrlOrBase64): array
    {
        [$audio, $mime] = $this->extractMedia($audioUrlOrBase64, 'audio/ogg');

        // Se for ogg/opus (gravado no browser ou convertido localmente), envia direto
        // Se for outro formato (webm), deixa o ffmpeg do Evolution API converter (encoding=true)
        $isOgg = str_contains($mime, 'ogg');

        return $this->post("/message/sendWhatsAppAudio/{$this->instanceName}", [
            'number'       => $this->normalizePhone($phone),
            'audioMessage' => ['audio' => $audio],
            'options'      => ['encoding' => !$isOgg],
        ]);
    }

    public function sendVideo(string $phone, string $videoUrlOrBase64, string $caption = ''): array
    {
        [$media, $mime] = $this->extractMedia($videoUrlOrBase64, 'video/mp4');

        return $this->post("/message/sendMedia/{$this->instanceName}", [
            'number'       => $this->normalizePhone($phone),
            'mediaMessage' => [
                'mediatype' => 'video',
                'mimetype'  => $mime,
                'caption'   => $caption,
                'media'     => $media,
                'fileName'  => 'video.' . $this->mimeToExt($mime),
            ],
        ]);
    }

    /**
     * Extrai base64 puro e MIME de um Data URL ou URL pública.
     * Retorna [$media, $mime] onde $media é base64 puro ou URL.
     */
    private function extractMedia(string $input, string $defaultMime): array
    {
        if (str_starts_with($input, 'data:')) {
            // Formato: data:mime/type;base64,xxxxxx
            if (preg_match('/^data:([^;]+);base64,(.+)$/s', $input, $m)) {
                return [$m[2], $m[1]];
            }
            // Sem MIME declarado
            [, $raw] = explode(',', $input, 2);
            return [$raw, $defaultMime];
        }

        // URL pública ou base64 puro — passa direto
        return [$input, $defaultMime];
    }

    private function mimeToExt(string $mime): string
    {
        return match (true) {
            str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') => 'jpg',
            str_contains($mime, 'png')  => 'png',
            str_contains($mime, 'gif')  => 'gif',
            str_contains($mime, 'webp') => 'webp',
            str_contains($mime, 'ogg')  => 'ogg',
            str_contains($mime, 'mpeg') || str_contains($mime, 'mp3') => 'mp3',
            str_contains($mime, 'mp4')  => 'mp4',
            str_contains($mime, 'webm') => 'webm',
            str_contains($mime, 'pdf')  => 'pdf',
            default                     => 'bin',
        };
    }

    // ─── Reações, edição e deleção ────────────────────────────────────────

    public function sendReaction(string $messageId, string $remoteJid, string $emoji): array
    {
        return $this->post("/message/sendReaction/{$this->instanceName}", [
            'reactionMessage' => [
                'key' => [
                    'remoteJid' => $remoteJid,
                    'fromMe'    => true,
                    'id'        => $messageId,
                ],
                'reaction' => $emoji,
            ],
        ]);
    }

    public function updateMessage(string $messageId, string $remoteJid, string $text): array
    {
        return $this->put("/message/updateMessage/{$this->instanceName}", [
            'number' => $remoteJid,
            'key'    => ['id' => $messageId, 'fromMe' => true, 'remoteJid' => $remoteJid],
            'text'   => $text,
        ]);
    }

    public function deleteMessage(string $messageId, string $remoteJid, bool $fromMe = true): array
    {
        return $this->delete("/message/delete/{$this->instanceName}", useGlobalKey: false, body: [
            'number' => $remoteJid,
            'key'    => ['id' => $messageId, 'fromMe' => $fromMe, 'remoteJid' => $remoteJid],
        ]);
    }

    // ─── Grupos ─────────────────────────────────────────────────────────────

    public function fetchAllGroups(): array
    {
        return $this->get("/group/fetchAllGroups/{$this->instanceName}?getParticipants=false");
    }

    // ─── Mídia ──────────────────────────────────────────────────────────────

    /**
     * Baixa o base64 decifrado de uma mensagem de mídia recebida via webhook.
     * Útil quando o webhook não veio com webhookBase64=true.
     *
     * @return array{base64:string,mimetype:?string}|null
     */
    public function getBase64FromMediaMessage(string $messageId, bool $convertToMp4 = false): ?array
    {
        $result = $this->post("/chat/getBase64FromMediaMessage/{$this->instanceName}", [
            'message'      => ['key' => ['id' => $messageId]],
            'convertToMp4' => $convertToMp4,
        ]);

        if (empty($result['success']) || empty($result['base64'])) {
            return null;
        }

        return [
            'base64'   => $result['base64'],
            'mimetype' => $result['mimetype'] ?? null,
        ];
    }

    // ─── Contatos ───────────────────────────────────────────────────────────

    public function getProfilePicture(string $phone): ?string
    {
        $result = $this->get("/contact/getProfilePicture?number={$this->normalizePhone($phone)}");
        return $result['profilePictureUrl'] ?? $result['link'] ?? null;
    }

    // ─── HTTP helpers ────────────────────────────────────────────────────────

    private function get(string $endpoint, bool $useGlobalKey = false, bool $skipInstance = false): array
    {
        try {
            $url = $skipInstance
                ? $this->serverUrl . $endpoint
                : $this->serverUrl . $endpoint;

            $response = Http::withHeaders($this->headers($useGlobalKey))
                ->timeout(20)
                ->get($url);

            return $this->parseResponse($response, $endpoint);
        } catch (\Exception $e) {
            Log::error('EvolutionAPI GET error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function post(string $endpoint, array $data, bool $useGlobalKey = false): array
    {
        try {
            $response = Http::withHeaders($this->headers($useGlobalKey))
                ->timeout(20)
                ->post($this->serverUrl . $endpoint, $data);

            return $this->parseResponse($response, $endpoint);
        } catch (\Exception $e) {
            Log::error('EvolutionAPI POST error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function put(string $endpoint, array $data = []): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(20)
                ->put($this->serverUrl . $endpoint, $data);

            return $this->parseResponse($response, $endpoint);
        } catch (\Exception $e) {
            Log::error('EvolutionAPI PUT error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function delete(string $endpoint, bool $useGlobalKey = false, array $body = []): array
    {
        try {
            $request = Http::withHeaders($this->headers($useGlobalKey))->timeout(20);
            $response = !empty($body)
                ? $request->send('DELETE', $this->serverUrl . $endpoint, ['json' => $body])
                : $request->delete($this->serverUrl . $endpoint);

            return $this->parseResponse($response, $endpoint);
        } catch (\Exception $e) {
            Log::error('EvolutionAPI DELETE error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function headers(bool $useGlobalKey = false): array
    {
        $key = $useGlobalKey ? $this->globalKey : ($this->instanceKey ?: $this->globalKey);
        return [
            'Content-Type'               => 'application/json',
            'apikey'                     => $key,
            'ngrok-skip-browser-warning' => 'true',
        ];
    }

    private function parseResponse(Response $response, string $endpoint): array
    {
        $json = $response->json() ?? [];

        if ($response->successful()) {
            return array_merge(is_array($json) ? $json : ['data' => $json], ['success' => true]);
        }

        Log::warning('EvolutionAPI error response', [
            'endpoint' => $endpoint,
            'status'   => $response->status(),
            'body'     => $response->body(),
        ]);

        return [
            'success'  => false,
            'status'   => $response->status(),
            'error'    => $json['message'] ?? $json['error'] ?? $response->body(),
            'response' => $json['response'] ?? [],  // preserva detalhes (ex: ["message" => ["... already in use"]])
        ];
    }

    private function normalizePhone(string $phone): string
    {
        // Se for JID completo (ex: 237919864385661@lid ou 5511999@s.whatsapp.net), usa direto
        if (str_contains($phone, '@')) {
            return $phone;
        }

        $phone = preg_replace('/\D/', '', $phone);

        if (strlen($phone) <= 11) {
            $phone = '55' . $phone;
        }

        return $phone;
    }
}
