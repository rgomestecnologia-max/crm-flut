<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 30;

    public function __construct(public int $messageId) {}

    public function handle(): void
    {
        $message = Message::withoutGlobalScopes()
            ->with(['conversation.contact', 'conversation.department'])
            ->find($this->messageId);

        if (!$message || !$message->conversation) {
            return;
        }

        $conversation = $message->conversation;
        $departmentId = $conversation->department_id;
        $companyId    = $conversation->company_id;
        $contactName  = $conversation->contact->display_name ?? 'Contato';
        $deptName     = $conversation->department->name ?? '';

        // Buscar users com acesso a esse departamento
        $userIds = User::where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId)
                  ->orWhereHas('departments', fn($q2) => $q2->where('departments.id', $departmentId))
                  ->orWhere('role', 'admin');
            })
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        $subscriptions = PushSubscription::whereIn('user_id', $userIds)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        // Payload
        $body = $message->type === 'text'
            ? mb_substr($message->content ?? '', 0, 100)
            : match($message->type) {
                'image'    => '📷 Foto',
                'audio'    => '🎵 Áudio',
                'video'    => '🎬 Vídeo',
                'document' => '📄 Documento',
                'sticker'  => '🎭 Sticker',
                default    => '📎 Mídia',
            };

        $payload = json_encode([
            'title' => "{$contactName} — {$deptName}",
            'body'  => $body,
            'url'   => '/chat',
        ]);

        $auth = [
            'VAPID' => [
                'subject'    => config('app.url'),
                'publicKey'  => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ];

        try {
            $webPush = new WebPush($auth);

            foreach ($subscriptions as $sub) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $sub->endpoint,
                        'keys'     => [
                            'p256dh' => $sub->p256dh,
                            'auth'   => $sub->auth,
                        ],
                    ]),
                    $payload
                );
            }

            $sent = 0;
            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $sent++;
                } elseif ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                }
            }

            Log::info("Push notification enviada", ['message_id' => $this->messageId, 'sent' => $sent, 'total' => $subscriptions->count()]);
        } catch (\Throwable $e) {
            Log::warning('Push notification error: ' . $e->getMessage());
        }
    }
}
