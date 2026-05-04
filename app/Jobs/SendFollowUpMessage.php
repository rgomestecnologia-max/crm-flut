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

class SendFollowUpMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public int    $contactId,
        public int    $conversationId,
        public int    $stageId,
        public string $followUpInstruction,
    ) {}

    public function handle(): void
    {
        $contact = Contact::find($this->contactId);
        $conversation = Conversation::find($this->conversationId);

        if (!$contact || !$conversation) return;

        app(\App\Services\CurrentCompany::class)->set((int) $conversation->company_id, persist: false);

        // Verifica se o contato RESPONDEU desde que a mensagem foi enviada
        $contactReplied = Message::where('conversation_id', $this->conversationId)
            ->where('sender_type', 'contact')
            ->exists();

        if ($contactReplied) {
            Log::info('SendFollowUp: cliente já respondeu, ignorando', ['contact' => $contact->name]);
            return;
        }

        // Verifica se o card ainda está na etapa esperada (não foi movido)
        $cardStillInStage = CrmCard::where('contact_id', $this->contactId)
            ->where('stage_id', $this->stageId)
            ->exists();

        if (!$cardStillInStage) {
            Log::info('SendFollowUp: card já moveu de etapa, ignorando', ['contact' => $contact->name]);
            return;
        }

        // Verifica se a conversa ainda está aberta
        if (!in_array($conversation->status, ['open', 'pending'])) {
            Log::info('SendFollowUp: conversa não está mais aberta, ignorando', ['contact' => $contact->name]);
            return;
        }

        // Gera mensagem de follow-up via IA
        $text = $this->generateFollowUp($contact->name ?? 'cliente');

        if (!$text) {
            Log::warning('SendFollowUp: IA falhou, usando mensagem base');
            $text = str_replace('{nome}', $contact->name ?? 'cliente', $this->followUpInstruction);
        }

        // Envia
        $service = WhatsAppProvider::service();
        if (!$service) {
            Log::error('SendFollowUp: nenhum provider ativo');
            return;
        }

        $realPhone = ($contact->phone && preg_match('/^55\d{10,11}$/', $contact->phone)) ? $contact->phone : null;
        $phone = $realPhone ?? $contact->chat_lid ?? $contact->phone;

        $result = $service->sendText($phone, $text);
        $msgId = $result['key']['id'] ?? null;

        Message::create([
            'conversation_id' => $this->conversationId,
            'sender_type'     => 'agent',
            'sender_id'       => null,
            'content'         => $text,
            'type'            => 'text',
            'zapi_message_id' => $msgId,
            'delivery_status' => ($result['success'] ?? false) ? 'sent' : 'failed',
        ]);

        $conversation->update(['last_message_at' => now()]);

        // Captura LID
        $returnedJid = $result['key']['remoteJid'] ?? null;
        if ($returnedJid && str_contains($returnedJid, '@lid') && !$contact->chat_lid) {
            $contact->update(['chat_lid' => $returnedJid]);
        }

        Log::info('SendFollowUp: lembrete enviado', [
            'contact' => $contact->name,
            'length'  => strlen($text),
        ]);
    }

    private function generateFollowUp(string $contactName): ?string
    {
        $apiKey = \App\Models\GlobalSetting::get('gemini_api_key');
        $model  = \App\Models\GlobalSetting::get('gemini_model', 'gemini-2.0-flash');
        if (!$apiKey) return null;

        $charCount = mb_strlen($this->followUpInstruction);

        $prompt = "Você é um assistente que reescreve mensagens para WhatsApp Business com variações naturais.\n\n"
            . "MENSAGEM ORIGINAL ({$charCount} caracteres):\n---\n" . $this->followUpInstruction . "\n---\n\n"
            . "Nome do cliente: {$contactName}\n\n"
            . "REGRAS: Reescreva INTEIRA, mesmo tamanho, varie palavras/emojis, mantenha sentido. Use o nome do cliente. Responda APENAS com a mensagem.";

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
            $response = Http::timeout(30)->post($url, [
                'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 1.0, 'maxOutputTokens' => 4096],
            ]);
            $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? null;
            return $text ? trim($text) : null;
        } catch (\Throwable $e) {
            Log::warning('SendFollowUp: IA falhou', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
