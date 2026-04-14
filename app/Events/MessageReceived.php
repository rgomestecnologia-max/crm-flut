<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('department.' . $this->message->conversation->department_id),
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.received';
    }

    public function broadcastWith(): array
    {
        $conversation = $this->message->conversation->load('contact', 'department', 'assignedAgent');

        return [
            'message' => [
                'id'              => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'sender_type'     => $this->message->sender_type,
                'content'         => $this->message->content,
                'type'            => $this->message->type,
                'media_url'       => $this->message->media_url,
                'media_filename'  => $this->message->media_filename,
                'delivery_status' => $this->message->delivery_status,
                'created_at'      => $this->message->created_at->toISOString(),
            ],
            'conversation' => [
                'id'             => $conversation->id,
                'protocol'       => $conversation->protocol,
                'status'         => $conversation->status,
                'last_message_at'=> $conversation->last_message_at?->toISOString(),
                'contact' => [
                    'id'     => $conversation->contact->id,
                    'name'   => $conversation->contact->display_name,
                    'phone'  => $conversation->contact->phone,
                    'avatar' => $conversation->contact->avatar,
                ],
                'department' => [
                    'id'    => $conversation->department->id,
                    'name'  => $conversation->department->name,
                    'color' => $conversation->department->color,
                ],
            ],
        ];
    }
}
