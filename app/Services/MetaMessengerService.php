<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaMessengerService
{
    private string $pageId;
    private string $accessToken;

    public function __construct(string $pageId, string $accessToken)
    {
        $this->pageId = $pageId;
        $this->accessToken = $accessToken;
    }

    public function sendText(string $recipientId, string $text): array
    {
        return $this->send($recipientId, ['text' => $text]);
    }

    public function sendImage(string $recipientId, string $url): array
    {
        return $this->send($recipientId, [
            'attachment' => ['type' => 'image', 'payload' => ['url' => $url, 'is_reusable' => true]],
        ]);
    }

    public function sendVideo(string $recipientId, string $url): array
    {
        return $this->send($recipientId, [
            'attachment' => ['type' => 'video', 'payload' => ['url' => $url, 'is_reusable' => true]],
        ]);
    }

    public function sendAudio(string $recipientId, string $url): array
    {
        return $this->send($recipientId, [
            'attachment' => ['type' => 'audio', 'payload' => ['url' => $url, 'is_reusable' => true]],
        ]);
    }

    public function sendDocument(string $recipientId, string $url, string $filename = 'file'): array
    {
        return $this->send($recipientId, [
            'attachment' => ['type' => 'file', 'payload' => ['url' => $url, 'is_reusable' => true]],
        ]);
    }

    private function send(string $recipientId, array $message): array
    {
        $url = "https://graph.facebook.com/v21.0/{$this->pageId}/messages";

        $response = Http::withToken($this->accessToken)->post($url, [
            'recipient' => ['id' => $recipientId],
            'message'   => $message,
        ]);

        $result = $response->json();

        if (!$response->ok()) {
            Log::error('MetaMessengerService: envio falhou', ['result' => $result]);
        }

        return array_merge($result ?? [], ['success' => $response->ok()]);
    }
}
