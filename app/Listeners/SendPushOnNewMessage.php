<?php

namespace App\Listeners;

use App\Events\MessageReceived;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class SendPushOnNewMessage implements ShouldQueue
{
    public function handle(MessageReceived $event): void
    {
        $message = \App\Models\Message::withoutGlobalScopes()
            ->with(['conversation.contact', 'conversation.department'])
            ->find($event->message->id);

        if (!$message || !$message->conversation) {
            return;
        }

        $conversation = $message->conversation;

        // Só notificar mensagens de contato (não do agente)
        if ($message->sender_type !== 'contact') {
            return;
        }

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

        // Buscar push subscriptions desses users
        $subscriptions = PushSubscription::whereIn('user_id', $userIds)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        // Preparar payload
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

        // Enviar push notifications
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

            foreach ($webPush->flush() as $report) {
                if ($report->isSubscriptionExpired()) {
                    // Remover subscription expirada
                    PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Push notification error: ' . $e->getMessage());
        }
    }
}
