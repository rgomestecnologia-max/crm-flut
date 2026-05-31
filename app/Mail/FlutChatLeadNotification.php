<?php

namespace App\Mail;

use App\Models\FlutChatLead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FlutChatLeadNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public FlutChatLead $lead,
        public string $widgetName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Um novo lead foi gerado pelo FlutChat 🎉',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.flut-chat-lead',
        );
    }
}
