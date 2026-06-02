<?php

namespace App\Jobs;

use App\Models\AiBotConfig;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSiteLeadGreeting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public int $conversationId,
        public string $contactName = '',
    ) {}

    public function handle(): void
    {
        $conv = Conversation::withoutGlobalScopes()->find($this->conversationId);
        if (!$conv || $conv->status !== 'open') return;

        app(\App\Services\CurrentCompany::class)->set($conv->company_id, persist: false);

        // Se o cliente já enviou mensagem, a IA já está atendendo — não envia saudação
        $clientSent = Message::where('conversation_id', $conv->id)
            ->where('sender_type', 'contact')
            ->exists();

        if ($clientSent) {
            Log::info('SendSiteLeadGreeting: cliente já enviou mensagem, pulando saudação', [
                'conv' => $conv->id,
            ]);
            return;
        }

        // Se já tem mensagem do bot (outro processo já enviou), não duplica
        $botSent = Message::where('conversation_id', $conv->id)
            ->where('sender_type', 'agent')
            ->whereNull('sender_id')
            ->exists();

        if ($botSent) return;

        $botConfig = AiBotConfig::current();
        if (!$botConfig || !$botConfig->is_active || !$botConfig->hasKey()) return;

        $name = $this->contactName ?: 'cliente';
        $greeting = "Olá {$name}! Vi que você se cadastrou no nosso site. 😊 Sou a assistente virtual da Orangexpress e posso te ajudar a encontrar a máquina extratora ideal para o seu negócio. Me conta, qual o seu ramo de atividade?";

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_type'     => 'agent',
            'sender_id'       => null,
            'content'         => $greeting,
            'type'            => 'text',
            'delivery_status' => 'pending',
        ]);

        $conv->update(['last_message_at' => now()]);
        SendWhatsAppMessage::dispatch($msg);

        try {
            broadcast(new \App\Events\MessageReceived($msg));
        } catch (\Throwable) {}

        Log::info('SendSiteLeadGreeting: saudação enviada', [
            'conv' => $conv->id, 'contact' => $name,
        ]);
    }
}
