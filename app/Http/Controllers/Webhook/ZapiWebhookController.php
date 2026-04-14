<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessage;
use App\Jobs\ProcessMessageReaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZapiWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $raw     = $request->getContent();
        $payload = $request->all();
        $type    = $payload['type'] ?? 'unknown';

        Log::info('Z-API WEBHOOK RECEBIDO', [
            'type'      => $type,
            'phone'     => $payload['phone'] ?? null,
            'fromMe'    => $payload['fromMe'] ?? null,
            'isGroup'   => $payload['isGroup'] ?? null,
            'messageId' => $payload['messageId'] ?? null,
            'keys'      => array_keys($payload),
            'raw'       => substr($raw, 0, 1200),
        ]);

        // Reações de mensagem
        if ($type === 'ReactionCallback') {
            ProcessMessageReaction::dispatch($payload);
            return response()->json(['ok' => true]);
        }

        // Tipos que não representam mensagens — ignorar
        $ignoredTypes = [
            'MessageStatusCallback', 'ReadCallback', 'DeliveryCallback',
            'SentCallback', 'PresenceChatCallback', 'DisconnectedCallback',
            'ConnectedCallback',
        ];

        if (in_array($type, $ignoredTypes)) {
            return response()->json(['ok' => true]);
        }

        // Só processa ReceivedCallback (mensagens reais)
        if ($type !== 'ReceivedCallback') {
            Log::info('Z-API tipo ignorado', ['type' => $type]);
            return response()->json(['ok' => true]);
        }

        $phone    = $payload['phone'] ?? null;
        $fromMe   = $payload['fromMe'] ?? false;
        $isGroup  = ($payload['isGroup'] ?? false) === true;
        $phoneRaw = (string) ($payload['phone'] ?? '');

        if (!$isGroup) {
            $isNewsletter = ($payload['isNewsletter'] ?? false) === true
                || str_contains($phoneRaw, '@newsletter')
                || str_contains((string) ($payload['chatId'] ?? ''), '@newsletter')
                || preg_match('/^1203\d+/', preg_replace('/\D/', '', $phoneRaw));

            if ($isNewsletter) {
                Log::info('Z-API canal/newsletter ignorado', ['phone' => $phone]);
                return response()->json(['ok' => true]);
            }
        }

        if ($phone) {
            ProcessIncomingMessage::dispatch($payload);
            Log::info('Z-API job despachado para ' . $phone . ($fromMe ? ' [fromMe]' : '') . ($isGroup ? ' [grupo]' : ''));
        } else {
            Log::info('Z-API payload ignorado (sem phone)', ['phone' => $phone]);
        }

        return response()->json(['ok' => true]);
    }
}
