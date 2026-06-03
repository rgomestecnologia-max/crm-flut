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

class ProcessAiInactivityFollowUp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        // Busca todas as empresas com IA ativa
        $botConfigs = AiBotConfig::withoutGlobalScopes()
            ->where('is_active', true)
            ->get();

        foreach ($botConfigs as $config) {
            app(\App\Services\CurrentCompany::class)->set($config->company_id, persist: false);
            $this->processCompany($config);
        }
    }

    private function processCompany(AiBotConfig $config): void
    {
        $inactivityMinutes = $config->inactivity_followup_minutes ?? 60;
        $closeMinutes = $config->inactivity_close_minutes ?? 60;

        if (!$inactivityMinutes) return;

        // Busca conversas abertas sem agente humano, inativas pelo menor tempo configurado
        $minMinutes = min($inactivityMinutes, $closeMinutes);
        $conversations = Conversation::withoutGlobalScopes()
            ->where('company_id', $config->company_id)
            ->where('status', 'open')
            ->whereNull('assigned_to')
            ->whereNull('waiting_human_reason')
            ->where('is_group', false)
            ->where('last_message_at', '<', now()->subMinutes($minMinutes))
            ->get();

        foreach ($conversations as $conv) {
            try {
                $this->processConversation($conv, $config, $inactivityMinutes, $closeMinutes);
            } catch (\Throwable $e) {
                Log::warning('AiInactivityFollowUp: erro', [
                    'conv' => $conv->id, 'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function processConversation(Conversation $conv, AiBotConfig $config, int $followUpMinutes, int $closeMinutes): void
    {
        // Pega a última mensagem da conversa (agent ou contact)
        $lastMsg = Message::where('conversation_id', $conv->id)
            ->whereIn('sender_type', ['agent', 'contact'])
            ->latest()
            ->first();

        if (!$lastMsg) return;

        // Se a última mensagem é do contato, a IA deveria ter respondido — ignora
        if ($lastMsg->sender_type === 'contact') return;

        // Se a última mensagem é de humano (sender_id não nulo), ignora
        if ($lastMsg->sender_type === 'agent' && $lastMsg->sender_id !== null) return;

        // Já encerrou por inatividade? Não processa de novo
        $closeSent = Message::where('conversation_id', $conv->id)
            ->where('sender_type', 'system')
            ->where('content', 'like', '%encerramento inatividade%')
            ->exists();
        if ($closeSent) return;

        // Verifica se já enviou follow-up de inatividade
        $followUpMsg = Message::where('conversation_id', $conv->id)
            ->where('sender_type', 'system')
            ->where('content', 'like', '%follow-up inatividade%')
            ->latest()
            ->first();

        if ($followUpMsg) {
            // Cliente respondeu depois do follow-up? Se sim, IA já cuidou
            $clientRepliedAfter = Message::where('conversation_id', $conv->id)
                ->where('sender_type', 'contact')
                ->where('created_at', '>', $followUpMsg->created_at)
                ->exists();
            if ($clientRepliedAfter) return;

            // Já enviou follow-up e cliente não respondeu — verifica se passou closeMinutes
            $minutesSinceFollowUp = (int) now()->diffInMinutes($followUpMsg->created_at);
            if ($minutesSinceFollowUp >= $closeMinutes) {
                $this->sendCloseMessage($conv, $config);
            }
        } else {
            // Ainda não enviou follow-up — envia agora
            $this->sendFollowUpMessage($conv, $config);
        }
    }

    private function sendFollowUpMessage(Conversation $conv, AiBotConfig $config): void
    {
        $contact = \App\Models\Contact::withoutGlobalScopes()->find($conv->contact_id);
        $contactName = $contact->name ?? 'cliente';
        $text = $config->inactivity_followup_message
            ?: "Oi {nome}! Vi que ficou um tempinho sem responder. 😊 Tem alguma dúvida que posso te ajudar? Se preferir, posso te conectar com um de nossos consultores!";

        $text = str_replace(['{nome}', '{name}'], [$contactName, $contactName], $text);

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_type'     => 'agent',
            'sender_id'       => null,
            'content'         => $text,
            'type'            => 'text',
            'delivery_status' => 'pending',
        ]);

        $conv->update(['last_message_at' => now()]);
        SendWhatsAppMessage::dispatch($msg);

        // Marca que enviou follow-up (mensagem de sistema invisível)
        Message::create([
            'conversation_id' => $conv->id,
            'sender_type'     => 'system',
            'content'         => 'IA: follow-up inatividade enviado',
            'type'            => 'text',
            'delivery_status' => 'sent',
        ]);

        try { broadcast(new \App\Events\MessageReceived($msg)); } catch (\Throwable) {}

        Log::info('AiInactivityFollowUp: follow-up enviado', ['conv' => $conv->id, 'contact' => $contactName]);
    }

    private function sendCloseMessage(Conversation $conv, AiBotConfig $config): void
    {
        $contact = \App\Models\Contact::withoutGlobalScopes()->find($conv->contact_id);
        $contactName = $contact->name ?? 'cliente';
        $text = $config->inactivity_close_message
            ?: "Oi {nome}, como não recebi resposta, vou encerrar nosso atendimento por aqui. 😊 Caso precise de algo no futuro, é só mandar uma mensagem. Agradecemos o contato! Até mais! 👋";

        $text = str_replace(['{nome}', '{name}'], [$contactName, $contactName], $text);

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_type'     => 'agent',
            'sender_id'       => null,
            'content'         => $text,
            'type'            => 'text',
            'delivery_status' => 'pending',
        ]);

        SendWhatsAppMessage::dispatch($msg);

        // Encerra a conversa
        $conv->update([
            'status'          => 'resolved',
            'last_message_at' => now(),
        ]);

        // Mensagens de sistema
        Message::create([
            'conversation_id' => $conv->id,
            'sender_type'     => 'system',
            'content'         => 'IA: encerramento inatividade — conversa encerrada automaticamente',
            'type'            => 'text',
            'delivery_status' => 'sent',
        ]);

        try {
            broadcast(new \App\Events\MessageReceived($msg));
            broadcast(new \App\Events\ConversationStatusChanged($conv));
        } catch (\Throwable) {}

        Log::info('AiInactivityFollowUp: conversa encerrada por inatividade', ['conv' => $conv->id, 'contact' => $contactName]);
    }
}
