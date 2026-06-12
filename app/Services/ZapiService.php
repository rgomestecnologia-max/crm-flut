<?php

namespace App\Services;

use App\Models\ZapiConfig;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZapiService
{
    private string  $baseUrl = 'https://api.z-api.io/instances';
    private ?string $instanceId;
    private ?string $token;
    private ?string $clientToken;

    public function __construct(?ZapiConfig $config = null)
    {
        $config ??= ZapiConfig::active();

        $this->instanceId  = $config?->instance_id;
        $this->token       = $config?->token;
        $this->clientToken = $config?->client_token;
    }

    // ─── Envio de mensagens ───────────────────────────────────────

    public function sendTextMessage(string $phone, string $message, ?string $quotedMessageId = null): array
    {
        $body = [
            'phone'   => $this->normalizePhone($phone),
            'message' => $message,
        ];

        if ($quotedMessageId) {
            $body['messageId'] = $quotedMessageId;
        }

        return $this->post('/send-text', $body);
    }

    public function sendReaction(string $phone, string $messageId, string $emoji): array
    {
        return $this->post('/send-reaction', [
            'phone'     => $this->normalizePhone($phone),
            'messageId' => $messageId,
            'reaction'  => $emoji,
        ]);
    }

    public function sendContactMessage(string $phone, string $contactName, string $contactPhone): array
    {
        return $this->post('/send-contact', [
            'phone'        => $this->normalizePhone($phone),
            'contactName'  => $contactName,
            'contactPhone' => $this->normalizePhone($contactPhone),
        ]);
    }

    public function sendImageMessage(string $phone, string $imageUrl, string $caption = ''): array
    {
        return $this->post('/send-image', [
            'phone'   => $this->normalizePhone($phone),
            'image'   => $imageUrl,
            'caption' => $caption,
        ]);
    }

    public function sendDocumentMessage(string $phone, string $documentUrl, string $fileName): array
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) ?: 'pdf';
        return $this->post("/send-document/{$ext}", [
            'phone'    => $this->normalizePhone($phone),
            'document' => $documentUrl,
            'fileName' => $fileName,
        ]);
    }

    public function sendAudioMessage(string $phone, string $audioUrl): array
    {
        return $this->post('/send-audio', [
            'phone' => $this->normalizePhone($phone),
            'audio' => $audioUrl,
        ]);
    }

    public function sendVideoMessage(string $phone, string $videoUrl, string $caption = ''): array
    {
        return $this->post('/send-video', [
            'phone'   => $this->normalizePhone($phone),
            'video'   => $videoUrl,
            'caption' => $caption,
        ]);
    }

    public function editMessage(string $phone, string $messageId, string $newText): array
    {
        return $this->post('/send-text', [
            'phone'         => $this->normalizePhone($phone),
            'message'       => $newText,
            'editMessageId' => $messageId,
        ]);
    }

    public function deleteMessage(string $messageId): array
    {
        return $this->post('/delete-message', [
            'messageId' => $messageId,
        ]);
    }

    // ─── Contatos ─────────────────────────────────────────────────

    public function getProfilePicture(string $phone): ?string
    {
        $result = $this->get('/profile-picture?phone=' . $this->normalizePhone($phone));
        return $result['link'] ?? $result['value'] ?? null;
    }

    // ─── Status da instância ──────────────────────────────────────

    public function getConnectionStatus(): array
    {
        return $this->get('/status');
    }

    public function getQrCode(): array
    {
        return $this->get('/qr-code/image');
    }

    // ─── HTTP helpers ─────────────────────────────────────────────

    private function get(string $endpoint): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->get($this->url($endpoint));

            return $this->handleResponse($response);
        } catch (\Exception $e) {
            Log::error('Z-API GET error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return ['error' => $e->getMessage(), 'success' => false];
        }
    }

    private function post(string $endpoint, array $data): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->post($this->url($endpoint), $data);

            return $this->handleResponse($response);
        } catch (\Exception $e) {
            Log::error('Z-API POST error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return ['error' => $e->getMessage(), 'success' => false];
        }
    }

    private function url(string $endpoint): string
    {
        return "{$this->baseUrl}/{$this->instanceId}/token/{$this->token}{$endpoint}";
    }

    private function headers(): array
    {
        $headers = ['Content-Type' => 'application/json'];

        // Client-Token é o "Security Token" da aba Segurança do Z-API (opcional)
        if ($this->clientToken) {
            $headers['Client-Token'] = $this->clientToken;
        }

        return $headers;
    }

    private function handleResponse(Response $response): array
    {
        if ($response->successful()) {
            return array_merge($response->json() ?? [], ['success' => true]);
        }

        Log::warning('Z-API error response', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        return [
            'success' => false,
            'status'  => $response->status(),
            'error'   => $response->json('error') ?? $response->json('message') ?? $response->body(),
        ];
    }

    // ─── Utilitário ───────────────────────────────────────────────

    private function normalizePhone(string $phone): string
    {
        // Grupos: preservar o sufixo @g.us
        if (str_contains($phone, '@g.us')) {
            return $phone;
        }

        // JID individual: remover sufixo @s.whatsapp.net ou @lid
        if (str_contains($phone, '@')) {
            $phone = explode('@', $phone)[0];
        }

        // Remove tudo que não é número
        $phone = preg_replace('/\D/', '', $phone);

        // Adiciona DDI Brasil se não tiver (número com 10 ou 11 dígitos)
        if (strlen($phone) <= 11) {
            $phone = '55' . $phone;
        }

        return $phone;
    }
}
