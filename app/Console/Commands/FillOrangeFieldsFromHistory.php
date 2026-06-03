<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\CrmCard;
use App\Models\CrmCardFieldValue;
use App\Models\GlobalSetting;
use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FillOrangeFieldsFromHistory extends Command
{
    protected $signature = 'orange:fill-fields';
    protected $description = 'Usa IA para extrair ramo de atividade e produção diária do histórico de conversas';

    public function handle(): void
    {
        app(\App\Services\CurrentCompany::class)->set(3, persist: false);

        $ramoFieldId = 38;
        $prodFieldId = 39;
        $filled = 0;
        $already = 0;
        $noData = 0;

        $apiKey = GlobalSetting::get('gemini_api_key');
        $model = GlobalSetting::get('gemini_model', 'gemini-2.0-flash');
        if (!$apiKey) {
            $this->error('Gemini API key não configurada');
            return;
        }

        $convs = Conversation::withoutGlobalScopes()
            ->where('company_id', 3)
            ->whereNotNull('contact_id')
            ->get();

        $this->info("Conversas a processar: {$convs->count()}");
        $bar = $this->output->createProgressBar($convs->count());

        foreach ($convs as $conv) {
            $bar->advance();

            $card = CrmCard::withoutGlobalScopes()->where('contact_id', $conv->contact_id)
                ->where('pipeline_id', 6)->first();
            if (!$card) continue;

            $hasRamo = CrmCardFieldValue::withoutGlobalScopes()->where('card_id', $card->id)
                ->where('field_id', $ramoFieldId)
                ->whereNotNull('value')->where('value', '!=', '')->exists();
            $hasProd = CrmCardFieldValue::withoutGlobalScopes()->where('card_id', $card->id)
                ->where('field_id', $prodFieldId)
                ->whereNotNull('value')->where('value', '!=', '')->exists();

            if ($hasRamo && $hasProd) { $already++; continue; }

            // Monta histórico resumido
            $msgs = Message::where('conversation_id', $conv->id)
                ->whereIn('sender_type', ['agent', 'contact'])
                ->whereNotNull('content')
                ->where('content', '!=', '')
                ->orderBy('id')
                ->take(30)
                ->get();

            if ($msgs->count() < 2) continue;

            $history = '';
            foreach ($msgs as $m) {
                $role = $m->sender_type === 'contact' ? 'CLIENTE' : 'IA';
                $history .= "{$role}: {$m->content}\n";
            }

            // Pergunta ao Gemini
            $prompt = "Analise esta conversa entre um cliente e a IA da Orangexpress (empresa de máquinas extratoras de suco de laranja).\n\n"
                . "CONVERSA:\n{$history}\n\n"
                . "Extraia APENAS o que o CLIENTE informou (não o que a IA perguntou):\n"
                . "1. Ramo de atividade do cliente (ex: restaurante, lanchonete, bar, mercado, etc.)\n"
                . "2. Estimativa de produção diária de suco de laranja (ex: 200 litros, 50 copos, etc.)\n\n"
                . "Responda SOMENTE no formato JSON, sem markdown:\n"
                . "{\"ramo\": \"valor ou null\", \"producao\": \"valor ou null\"}\n\n"
                . "REGRAS:\n"
                . "- Se o cliente NÃO informou o dado, use null\n"
                . "- Saudações (Boa tarde, Oi, Olá) NÃO são ramo de atividade\n"
                . "- Seja conciso no valor (ex: 'Restaurante', não 'O cliente tem um restaurante')";

            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
                $response = Http::timeout(15)->post($url, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['maxOutputTokens' => 256, 'temperature' => 0.1],
                ]);

                $text = $response->json('candidates.0.content.parts.0.text') ?? '';
                $text = trim(str_replace(['```json', '```', "\n"], '', $text));
                $data = json_decode($text, true);

                if (!$data) {
                    if (!$hasRamo || !$hasProd) {
                        $this->warn("Conv #{$conv->id} Card #{$card->id}: JSON inválido: {$text}");
                    }
                    continue;
                }

                $updated = false;
                if (!$hasRamo && !empty($data['ramo']) && $data['ramo'] !== 'null') {
                    CrmCardFieldValue::withoutGlobalScopes()->updateOrCreate(
                        ['card_id' => $card->id, 'field_id' => $ramoFieldId],
                        ['value' => $data['ramo']]
                    );
                    $updated = true;
                }
                if (!$hasProd && !empty($data['producao']) && $data['producao'] !== 'null') {
                    CrmCardFieldValue::withoutGlobalScopes()->updateOrCreate(
                        ['card_id' => $card->id, 'field_id' => $prodFieldId],
                        ['value' => $data['producao']]
                    );
                    $updated = true;
                }
                if ($updated) {
                    $filled++;
                } else {
                    $noData++;
                }

                usleep(200000); // 200ms entre requests
            } catch (\Throwable $e) {
                $this->warn("Erro conv #{$conv->id}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("Cards atualizados: {$filled}");
        $this->info("Já tinham campos: {$already}");
        $this->info("Sem dados na conversa: {$noData}");
    }
}
