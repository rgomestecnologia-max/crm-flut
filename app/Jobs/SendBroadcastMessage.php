<?php

namespace App\Jobs;

use App\Models\BroadcastCampaignRecipient;
use App\Models\BroadcastCampaignRun;
use App\Models\MetaMessageTemplate;
use App\Services\MetaWhatsAppService;
use App\Services\WhatsAppProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBroadcastMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 3600; // 1 hour max

    public function __construct(public BroadcastCampaignRun $run) {}

    public function handle(): void
    {
        $run      = $this->run->fresh();
        $campaign = \App\Models\BroadcastCampaign::withoutGlobalScopes()->find($run->campaign_id);

        if (!$campaign) {
            Log::error('SendBroadcastMessage: campaign not found', ['run_id' => $run->id]);
            return;
        }
        if ($campaign->status === 'paused') { Log::info('SendBroadcastMessage: campanha pausada, ignorando'); return; }

        app(\App\Services\CurrentCompany::class)->set((int) $campaign->company_id, persist: false);

        $api = WhatsAppProvider::broadcastService();
        if (!$api) {
            $run->update(['status' => 'failed']);
            $campaign->update(['status' => 'failed']);
            Log::error('SendBroadcastMessage: nenhum provider WhatsApp ativo');
            return;
        }
        $recipients = BroadcastCampaignRecipient::where('run_id', $run->id)
            ->where('status', 'pending')
            ->get();

        $sent   = 0;
        $failed = 0;

        // Verifica se Gemini está disponível para gerar variações
        $geminiKey   = \App\Models\GlobalSetting::get('gemini_api_key');
        $geminiModel = \App\Models\GlobalSetting::get('gemini_model', 'gemini-2.0-flash');
        $useAi       = $campaign->channel === 'whatsapp' && $geminiKey && !$campaign->meta_template_name;

        foreach ($recipients as $recipient) {
            try {
                $contactName = $recipient->broadcastContact?->name ?? '';
                $baseText = str_replace(
                    ['{nome}', '{name}'],
                    [$contactName, $contactName],
                    $campaign->message
                );

                // Gera variação via IA para cada destinatário (evita bloqueio por repetição)
                $text = $baseText;
                if ($useAi) {
                    $variation = $this->generateVariation($baseText, $contactName, $geminiKey, $geminiModel);
                    if ($variation) {
                        $text = $variation;
                    }
                }

                // Se Meta com template, envia via template
                if ($campaign->meta_template_name && $api instanceof MetaWhatsAppService) {
                    $tpl = MetaMessageTemplate::where('name', $campaign->meta_template_name)->first();
                    $bodyParams = [];
                    if ($tpl) {
                        // Extrai parâmetros do campo message (cada linha = um parâmetro)
                        // Linha 1 = {{1}}, Linha 2 = {{2}}, etc.
                        // Usa {nome} como placeholder para o nome do contato
                        $paramLines = $campaign->message
                            ? array_filter(array_map('trim', explode("\n", $campaign->message)), fn($l) => $l !== '')
                            : [];

                        $paramCount = 0;
                        $comps = is_string($tpl->components) ? json_decode($tpl->components, true) : ($tpl->components ?? []);
                        foreach ($comps as $comp) {
                            if ($comp['type'] === 'BODY') {
                                preg_match_all('/\{\{\d+\}\}/', $comp['text'] ?? '', $matches);
                                $paramCount = count($matches[0]);
                            }
                        }

                        for ($p = 0; $p < $paramCount; $p++) {
                            $val = $paramLines[$p] ?? '';
                            $val = str_replace(['{nome}', '{name}'], $contactName, $val);
                            $bodyParams[] = $val ?: ($p === 0 ? ($contactName ?: 'Cliente') : '-');
                        }
                    }
                    $result = $api->sendTemplate(
                        $recipient->phone,
                        $campaign->meta_template_name,
                        $tpl->language ?? 'pt_BR',
                        $bodyParams,
                    );
                } elseif ($campaign->image_path) {
                    $imageUrl = \App\Services\MediaStorage::url($campaign->image_path);
                    if (!str_starts_with($imageUrl, 'http')) {
                        $imageUrl = url($imageUrl);
                    }
                    $result = $api->sendImage($recipient->phone, $imageUrl, $text);
                } else {
                    $result = $api->sendText($recipient->phone, $text);
                }

                if ($result['success'] ?? false) {
                    $msgId = $result['key']['id'] ?? $result['id'] ?? null;
                    $recipient->update([
                        'status'     => 'sent',
                        'sent_at'    => now(),
                        'message_id' => $msgId,
                    ]);
                    $sent++;
                } else {
                    $error = $result['error'] ?? json_encode($result);
                    $recipient->update(['status' => 'failed', 'error' => substr($error, 0, 500)]);
                    $failed++;
                }
            } catch (\Throwable $e) {
                $recipient->update(['status' => 'failed', 'error' => substr($e->getMessage(), 0, 500)]);
                $failed++;
            }

            // Update counters
            $run->update(['sent_count' => $sent, 'failed_count' => $failed]);

            // Interval between messages
            if ($campaign->interval_seconds > 0) {
                sleep($campaign->interval_seconds);
            }
        }

        $run->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'sent_count'   => $sent,
            'failed_count' => $failed,
        ]);

        $campaign->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'sent_count'   => ($campaign->sent_count ?? 0) + $sent,
            'failed_count' => ($campaign->failed_count ?? 0) + $failed,
        ]);

        Log::info('Broadcast campaign completed', [
            'campaign' => $campaign->name,
            'run'      => $run->id,
            'sent'     => $sent,
            'failed'   => $failed,
        ]);
    }

    private function generateVariation(string $baseText, string $contactName, string $apiKey, string $model): ?string
    {
        try {
            $charCount = mb_strlen($baseText);

            $prompt = "Você é um assistente que reescreve mensagens de WhatsApp Business com variações naturais.\n\n"
                . "CONTEXTO/INSTRUÇÃO DA MENSAGEM:\n---\n" . $baseText . "\n---\n\n"
                . "DADOS DO CONTATO:\n- Nome: {$contactName}\n\n"
                . "REGRAS:\n"
                . "- Gere uma mensagem ÚNICA baseada no contexto acima\n"
                . "- Personalize para o contato usando o nome quando natural\n"
                . "- Mantenha o mesmo objetivo e informações da mensagem original\n"
                . "- Varie: estrutura, palavras, emojis, ordem das frases\n"
                . "- Tamanho similar ao original ({$charCount} caracteres)\n"
                . "- Formato WhatsApp: use *negrito* e emojis\n"
                . "- Responda APENAS com a mensagem, sem explicações";

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = \Illuminate\Support\Facades\Http::timeout(30)->post($url, [
                'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 1.2, 'maxOutputTokens' => 4096],
            ]);

            $text = $response->json('candidates.0.content.parts.0.text');
            if ($text && mb_strlen($text) >= ($charCount * 0.4)) {
                return trim($text);
            }
            return null;
        } catch (\Throwable $e) {
            Log::warning('Broadcast: AI variation falhou', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
