<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\BroadcastCampaignRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SendGridWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $events = $request->all();

        foreach ($events as $event) {
            $type        = $event['event'] ?? null;
            $recipientId = $event['recipient_id'] ?? null;
            $sgMessageId = $event['sg_message_id'] ?? null;

            if (!$type) continue;

            // Busca por custom_args.recipient_id ou por sg_message_id
            $recipient = null;
            if ($recipientId) {
                $recipient = BroadcastCampaignRecipient::find($recipientId);
            }
            if (!$recipient && $sgMessageId) {
                $cleanId = explode('.', $sgMessageId)[0] ?? $sgMessageId;
                $recipient = BroadcastCampaignRecipient::where('message_id', $cleanId)
                    ->orWhere('message_id', $sgMessageId)
                    ->first();
            }

            if (!$recipient) continue;

            match ($type) {
                'delivered' => $recipient->update([
                    'status'       => 'delivered',
                    'delivered_at' => now(),
                ]),
                'open' => $recipient->update([
                    'status'       => $recipient->status !== 'read' ? 'read' : $recipient->status,
                    'read_at'      => $recipient->read_at ?? now(),
                    'delivered_at' => $recipient->delivered_at ?? now(),
                ]),
                'bounce', 'dropped' => $recipient->update([
                    'status' => 'failed',
                    'error'  => substr(($event['reason'] ?? $event['response'] ?? $type), 0, 500),
                ]),
                default => null,
            };
        }

        return response()->json(['ok' => true]);
    }
}
