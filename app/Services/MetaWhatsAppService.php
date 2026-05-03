<?php

namespace App\Services;

use App\Models\MetaWhatsAppConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaWhatsAppService
{
    private string $phoneNumberId;
    private string $accessToken;
    private string $baseUrl;

    public function __construct(?MetaWhatsAppConfig $config = null)
    {
        $config ??= MetaWhatsAppConfig::current();

        $this->phoneNumberId = $config?->phone_number_id ?? '';
        $this->accessToken   = $config?->access_token ?? '';
        $this->baseUrl       = "https://graph.facebook.com/v21.0/{$this->phoneNumberId}";
    }

    // ─── Envio de mensagens ─────────────────────────────────────────────────

    public function sendText(string $phone, string $text, ?string $quotedId = null): array
    {
        $body = [
            'messaging_product' => 'whatsapp',
            'to'                => $this->normalizePhone($phone),
            'type'              => 'text',
            'text'              => ['body' => $text],
        ];

        if ($quotedId) {
            $body['context'] = ['message_id' => $quotedId];
        }

        return $this->postMessage($body);
    }

    public function sendImage(string $phone, string $mediaUrlOrBase64, string $caption = ''): array
    {
        $mediaId = $this->resolveMediaId($mediaUrlOrBase64, 'image/jpeg');

        $image = $mediaId
            ? ['id' => $mediaId]
            : ['link' => $mediaUrlOrBase64];

        if ($caption) {
            $image['caption'] = $caption;
        }

        return $this->postMessage([
            'messaging_product' => 'whatsapp',
            'to'                => $this->normalizePhone($phone),
            'type'              => 'image',
            'image'             => $image,
        ]);
    }

    public function sendAudio(string $phone, string $audioUrlOrBase64): array
    {
        $mediaId = $this->resolveMediaId($audioUrlOrBase64, 'audio/ogg');

        $audio = $mediaId
            ? ['id' => $mediaId]
            : ['link' => $audioUrlOrBase64];

        return $this->postMessage([
            'messaging_product' => 'whatsapp',
            'to'                => $this->normalizePhone($phone),
            'type'              => 'audio',
            'audio'             => $audio,
        ]);
    }

    public function sendVideo(string $phone, string $videoUrlOrBase64, string $caption = ''): array
    {
        $mediaId = $this->resolveMediaId($videoUrlOrBase64, 'video/mp4');

        $video = $mediaId
            ? ['id' => $mediaId]
            : ['link' => $videoUrlOrBase64];

        if ($caption) {
            $video['caption'] = $caption;
        }

        return $this->postMessage([
            'messaging_product' => 'whatsapp',
            'to'                => $this->normalizePhone($phone),
            'type'              => 'video',
            'video'             => $video,
        ]);
    }

    public function sendDocument(string $phone, string $mediaUrlOrBase64, string $fileName, string $mimetype = 'application/pdf'): array
    {
        $mediaId = $this->resolveMediaId($mediaUrlOrBase64, $mimetype);

        $document = $mediaId
            ? ['id' => $mediaId, 'filename' => $fileName]
            : ['link' => $mediaUrlOrBase64, 'filename' => $fileName];

        return $this->postMessage([
            'messaging_product' => 'whatsapp',
            'to'                => $this->normalizePhone($phone),
            'type'              => 'document',
            'document'          => $document,
        ]);
    }

    public function sendReaction(string $messageId, string $phone, string $emoji): array
    {
        return $this->postMessage([
            'messaging_product' => 'whatsapp',
            'to'                => $this->normalizePhone($phone),
            'type'              => 'reaction',
            'reaction'          => [
                'message_id' => $messageId,
                'emoji'      => $emoji,
            ],
        ]);
    }

    /**
     * Envia mensagem usando template aprovado.
     *
     * @param  string  $phone          Telefone destino
     * @param  string  $templateName   Nome do template (ex: "hello_world")
     * @param  string  $language       Código do idioma (ex: "pt_BR")
     * @param  array   $bodyParams     Parâmetros do body [["text" => "João"], ["text" => "10/05"]]
     * @param  array   $headerParams   Parâmetros do header (opcional)
     */
    public function sendTemplate(
        string $phone,
        string $templateName,
        string $language = 'pt_BR',
        array  $bodyParams = [],
        array  $headerParams = [],
    ): array {
        $components = [];

        if (!empty($headerParams)) {
            $params = array_map(fn($p) => is_array($p) ? $p : ['type' => 'text', 'text' => $p], $headerParams);
            $components[] = ['type' => 'header', 'parameters' => $params];
        }

        if (!empty($bodyParams)) {
            $params = array_map(fn($p) => is_array($p) ? $p : ['type' => 'text', 'text' => $p], $bodyParams);
            $components[] = ['type' => 'body', 'parameters' => $params];
        }

        $body = [
            'messaging_product' => 'whatsapp',
            'to'                => $this->normalizePhone($phone),
            'type'              => 'template',
            'template'          => [
                'name'     => $templateName,
                'language' => ['code' => $language],
            ],
        ];

        if (!empty($components)) {
            $body['template']['components'] = $components;
        }

        return $this->postMessage($body);
    }

    /**
     * Busca todos os templates de mensagem da conta WABA.
     */
    public function fetchTemplates(string $wabaId): array
    {
        try {
            $templates = [];
            $url = "https://graph.facebook.com/v21.0/{$wabaId}/message_templates?limit=100&fields=id,name,language,status,category,components";

            while ($url) {
                $response = Http::withToken($this->accessToken)
                    ->timeout(20)
                    ->get($url);

                if (!$response->successful()) {
                    $error = $response->json()['error']['message'] ?? $response->body();
                    Log::error('MetaWhatsApp: fetchTemplates failed', ['error' => $error]);
                    return ['success' => false, 'error' => $error, 'data' => []];
                }

                $json = $response->json();
                $templates = array_merge($templates, $json['data'] ?? []);

                // Paginação
                $url = $json['paging']['next'] ?? null;
            }

            return ['success' => true, 'data' => $templates];
        } catch (\Exception $e) {
            Log::error('MetaWhatsApp: fetchTemplates exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Meta Cloud API não suporta edição de mensagens.
     */
    public function updateMessage(string $messageId, string $phone, string $text): array
    {
        Log::info('MetaWhatsApp: edição de mensagem não suportada pela API oficial', [
            'message_id' => $messageId,
        ]);
        return ['success' => false, 'error' => 'Meta API does not support message editing'];
    }

    /**
     * Meta Cloud API não suporta exclusão de mensagens enviadas.
     */
    public function deleteMessage(string $messageId, string $phone): array
    {
        Log::info('MetaWhatsApp: exclusão de mensagem não suportada pela API oficial', [
            'message_id' => $messageId,
        ]);
        return ['success' => false, 'error' => 'Meta API does not support message deletion'];
    }

    // ─── Upload de mídia ────────────────────────────────────────────────────

    /**
     * Se o input é base64/data URL, faz upload para Meta e retorna media_id.
     * Se é URL pública, retorna null (usar link direto).
     */
    private function resolveMediaId(string $input, string $defaultMime): ?string
    {
        // URL pública — não precisa upload
        if (str_starts_with($input, 'http://') || str_starts_with($input, 'https://')) {
            return null;
        }

        // Data URL: data:mime;base64,xxx
        $mime = $defaultMime;
        $raw  = $input;

        if (str_starts_with($input, 'data:')) {
            if (preg_match('/^data:([^;]+);base64,(.+)$/s', $input, $m)) {
                $mime = $m[1];
                $raw  = $m[2];
            } else {
                [, $raw] = explode(',', $input, 2);
            }
        }

        $binary = base64_decode($raw);
        if (!$binary) {
            return null;
        }

        return $this->uploadMedia($binary, $mime);
    }

    /**
     * Faz upload de mídia binária para Meta e retorna o media ID.
     */
    private function uploadMedia(string $binary, string $mime): ?string
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(30)
                ->attach('file', $binary, 'file', ['Content-Type' => $mime])
                ->post("{$this->baseUrl}/media", [
                    'messaging_product' => 'whatsapp',
                    'type'              => $mime,
                ]);

            $data = $response->json();
            return $data['id'] ?? null;
        } catch (\Exception $e) {
            Log::error('MetaWhatsApp: upload media failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Baixa mídia pelo media ID (para processar mensagens recebidas).
     */
    public function downloadMedia(string $mediaId): ?array
    {
        try {
            // Primeiro busca a URL do media
            $response = Http::withToken($this->accessToken)
                ->timeout(15)
                ->get("https://graph.facebook.com/v21.0/{$mediaId}");

            $data = $response->json();
            $url  = $data['url'] ?? null;
            $mime = $data['mime_type'] ?? null;

            if (!$url) {
                return null;
            }

            // Baixa o conteúdo binário
            $mediaResponse = Http::withToken($this->accessToken)
                ->timeout(30)
                ->get($url);

            if (!$mediaResponse->successful()) {
                return null;
            }

            return [
                'binary'   => $mediaResponse->body(),
                'base64'   => base64_encode($mediaResponse->body()),
                'mimetype' => $mime,
            ];
        } catch (\Exception $e) {
            Log::error('MetaWhatsApp: download media failed', ['media_id' => $mediaId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    // ─── HTTP helpers ────────────────────────────────────────────────────────

    private function postMessage(array $data): array
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(20)
                ->post("{$this->baseUrl}/messages", $data);

            $json = $response->json() ?? [];

            if ($response->successful()) {
                $messageId = $json['messages'][0]['id'] ?? null;
                return [
                    'success' => true,
                    'key'     => ['id' => $messageId],
                    'messages' => $json['messages'] ?? [],
                ];
            }

            Log::warning('MetaWhatsApp: API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            $error = $json['error']['message'] ?? $response->body();
            return ['success' => false, 'error' => $error];
        } catch (\Exception $e) {
            Log::error('MetaWhatsApp: request failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function normalizePhone(string $phone): string
    {
        // Remove JID suffixes se existir
        if (str_contains($phone, '@')) {
            $phone = explode('@', $phone)[0];
        }

        // Remove caracteres não numéricos
        $phone = preg_replace('/\D/', '', $phone);

        // Adiciona DDI Brasil se necessário
        if (strlen($phone) <= 11) {
            $phone = '55' . $phone;
        }

        return $phone;
    }
}
