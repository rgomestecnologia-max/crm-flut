<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\CrmCard;
use App\Models\Message;
use App\Services\WhatsAppProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendStageFollowUp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        public int    $cardId,
        public int    $stageId,
        public int    $companyId,
        public string $context,
        public int    $sequenceNumber,
    ) {}

    public function handle(): void
    {
        $card = CrmCard::withoutGlobalScopes()
            ->with(['contact', 'stage', 'pipeline'])
            ->find($this->cardId);

        if (!$card || !$card->contact) {
            return;
        }

        // Parar se card mudou de etapa
        if ($card->stage_id !== $this->stageId) {
            Log::info('StageFollowUp: card mudou de etapa, cancelando', [
                'card' => $card->id,
                'expected_stage' => $this->stageId,
                'current_stage' => $card->stage_id,
            ]);
            return;
        }

        app(\App\Services\CurrentCompany::class)->set($this->companyId, persist: false);

        $contact = $card->contact;
        $phone = preg_replace('/\D/', '', $contact->phone);
        if (!$phone || strlen($phone) < 10) {
            return;
        }

        // Buscar conversa existente com esse contato
        $conversation = Conversation::withoutGlobalScopes()
            ->where('contact_id', $contact->id)
            ->where('company_id', $this->companyId)
            ->where('is_group', false)
            ->latest()
            ->first();

        // Se contato respondeu na conversa, não enviar follow-up
        if ($conversation) {
            $recentReply = Message::where('conversation_id', $conversation->id)
                ->where('sender_type', 'contact')
                ->where('created_at', '>', now()->subDays(2))
                ->exists();

            if ($recentReply) {
                Log::info('StageFollowUp: contato respondeu recentemente', ['contact' => $contact->name]);
                return;
            }
        }

        // Gerar mensagem via IA
        $message = $this->generateAiMessage($contact, $card);

        // Enviar via WhatsApp
        try {
            $whatsapp = WhatsAppProvider::service($this->companyId);
            $jid = $phone . '@s.whatsapp.net';
            $whatsapp->sendText($jid, $message);

            // Salvar na conversa se existir
            if ($conversation) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_type'     => 'agent',
                    'content'         => $message,
                    'type'            => 'text',
                    'delivery_status' => 'sent',
                ]);
            }

            Log::info('StageFollowUp enviado', [
                'card' => $card->id,
                'contact' => $contact->name,
                'sequence' => $this->sequenceNumber,
            ]);
        } catch (\Throwable $e) {
            Log::warning('StageFollowUp: erro ao enviar', ['error' => $e->getMessage()]);
        }
    }

    private function generateAiMessage(Contact $contact, CrmCard $card): string
    {
        $contactName = $contact->name ?? 'Cliente';
        $stageName = $card->stage?->name ?? '';
        $pipelineName = $card->pipeline?->name ?? '';

        $prompt = "Você é um vendedor profissional da Machinery Prime, empresa de máquinas industriais para panificação e alimentos.

Contexto: {$this->context}

Dados:
- Nome do contato: {$contactName}
- Etapa atual: {$stageName}
- Pipeline: {$pipelineName}
- Este é o follow-up #{$this->sequenceNumber}

Gere UMA mensagem de follow-up profissional, amigável e persuasiva para WhatsApp.
A mensagem deve:
- Ser personalizada com o nome do contato
- Ser breve (2-4 frases)
- Ter tom consultivo, não invasivo
- Variar o approach (não repetir o mesmo padrão)
- Incluir um CTA sutil
- NÃO usar markdown, apenas texto simples com emojis moderados

Responda APENAS com a mensagem, sem explicações.";

        try {
            $apiKey = config('services.gemini.key')
                ?: \App\Models\AiBotConfig::withoutGlobalScopes()
                    ->where('company_id', $this->companyId)
                    ->where('is_active', true)
                    ->value('gemini_api_key');

            if (!$apiKey) {
                return "Olá {$contactName}! 👋 Tudo bem? Estamos à disposição para qualquer dúvida sobre nossa proposta. Quando puder, nos dê um retorno! 😊";
            }

            $response = Http::timeout(15)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['maxOutputTokens' => 300, 'temperature' => 0.9],
                ]);

            $text = $response->json('candidates.0.content.parts.0.text');
            return $text ? trim($text) : "Olá {$contactName}! 👋 Gostaria de saber se teve a oportunidade de analisar nossa proposta. Estamos à disposição! 😊";
        } catch (\Throwable $e) {
            return "Olá {$contactName}! 👋 Gostaria de saber se teve a oportunidade de analisar nossa proposta. Estamos à disposição! 😊";
        }
    }
}
