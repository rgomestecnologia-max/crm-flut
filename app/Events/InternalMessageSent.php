<?php

namespace App\Events;

use App\Models\InternalMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InternalMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public InternalMessage $message) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('internal-chat.' . $this->message->recipient_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'internal.message';
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->message->id,
            'sender_id'  => $this->message->sender_id,
            'sender_name'=> $this->message->sender?->name,
            'content'    => $this->message->content,
            'type'       => $this->message->type,
            'media_url'  => $this->message->media_url,
            'created_at' => $this->message->created_at->toISOString(),
        ];
    }
}
