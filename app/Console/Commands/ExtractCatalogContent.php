<?php

namespace App\Console\Commands;

use App\Models\AiBotProduct;
use App\Models\GlobalSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExtractCatalogContent extends Command
{
    protected $signature = 'catalog:extract-content
        {--company= : Company ID (required)}
        {--product= : Single product ID (optional, for testing)}
        {--force : Overwrite existing document_content}';

    protected $description = 'Extract text content from catalog page images using Gemini Vision';

    public function handle(): int
    {
        $companyId = $this->option('company');
        if (!$companyId) {
            $this->error('--company is required');
            return 1;
        }

        $apiKey = GlobalSetting::get('gemini_api_key');
        if (!$apiKey) {
            $this->error('Gemini API key not configured');
            return 1;
        }

        $query = AiBotProduct::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNotNull('photo_path')
            ->where('is_active', true)
            ->orderBy('id');

        if ($this->option('product')) {
            $query->where('id', $this->option('product'));
        }

        if (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('document_content')->orWhere('document_content', '');
            });
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            $this->info('No products to process.');
            return 0;
        }

        $this->info("Processing {$products->count()} catalog pages...");
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($products as $product) {
            try {
                $imageUrl = $product->getPhotoAbsoluteUrl();
                if (!$imageUrl) {
                    $this->newLine();
                    $this->warn("Product {$product->id}: no photo URL");
                    $failed++;
                    $bar->advance();
                    continue;
                }

                // Download image
                $imageResponse = Http::timeout(30)->get($imageUrl);
                if (!$imageResponse->successful()) {
                    $this->newLine();
                    $this->warn("Product {$product->id}: failed to download image (HTTP {$imageResponse->status()})");
                    $failed++;
                    $bar->advance();
                    continue;
                }

                $imageBytes = $imageResponse->body();
                $base64 = base64_encode($imageBytes);
                $mimeType = $imageResponse->header('Content-Type') ?: 'image/jpeg';

                // Call Gemini Vision
                $model = GlobalSetting::get('gemini_model', 'gemini-2.0-flash');
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

                $requestBody = [
                    'contents' => [[
                        'parts' => [
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $base64,
                                ],
                            ],
                            [
                                'text' => 'Extraia TODO o conteúdo textual desta página de catálogo de uma gráfica/comunicação visual. '
                                    . 'Para cada produto listado, extraia: '
                                    . '- Nome do produto '
                                    . '- Especificações (tamanho, tipo de papel, gramatura, impressão frente/verso, acabamento, cores) '
                                    . '- Todas as quantidades disponíveis com seus respectivos preços (à vista e parcelado/3x no cartão) '
                                    . '- Prazo de produção se informado '
                                    . 'Formato: liste cada produto com todas as informações de forma estruturada e clara. '
                                    . 'IMPORTANTE: transcreva os valores EXATAMENTE como aparecem na imagem, sem arredondar ou modificar. '
                                    . 'Se houver texto promocional ou observações, inclua também.',
                            ],
                        ],
                    ]],
                    'generationConfig' => [
                        'maxOutputTokens' => 4096,
                        'temperature' => 0.1,
                    ],
                ];

                $geminiResponse = retry(3, function () use ($url, $requestBody) {
                    $r = Http::timeout(60)->post($url, $requestBody);
                    if ($r->status() >= 500 || $r->status() === 429) {
                        throw new \RuntimeException("Gemini transient error: {$r->status()}");
                    }
                    return $r;
                }, function (int $attempt) {
                    return $attempt * 5000; // 5s, 10s, 15s backoff
                });

                if (!$geminiResponse->successful()) {
                    $this->newLine();
                    $this->warn("Product {$product->id}: Gemini API error (HTTP {$geminiResponse->status()})");
                    Log::warning('catalog:extract-content Gemini error', [
                        'product_id' => $product->id,
                        'status' => $geminiResponse->status(),
                        'body' => substr($geminiResponse->body(), 0, 500),
                    ]);
                    $failed++;
                    $bar->advance();
                    continue;
                }

                $content = $geminiResponse->json('candidates.0.content.parts.0.text');

                if (!$content) {
                    $this->newLine();
                    $this->warn("Product {$product->id}: empty Gemini response");
                    $failed++;
                    $bar->advance();
                    continue;
                }

                $product->update(['document_content' => $content]);
                $success++;

            } catch (\Throwable $e) {
                $this->newLine();
                $this->error("Product {$product->id}: {$e->getMessage()}");
                Log::error('catalog:extract-content exception', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }

            $bar->advance();

            // Rate limit: wait 4s between calls
            if ($product !== $products->last()) {
                sleep(4);
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done! Success: {$success}, Failed: {$failed}");

        return $failed > 0 ? 1 : 0;
    }
}
