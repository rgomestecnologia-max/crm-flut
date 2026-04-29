<?php

namespace App\Livewire\Admin;

use App\Models\AiBotConfig;
use App\Models\ChatbotMenuConfig;
use App\Models\Department;
use App\Models\GlobalSetting;
use Livewire\Component;

class AiBotManager extends Component
{
    public bool   $is_active                 = false;
    public string $system_prompt             = '';
    public array  $voice_tones               = [];
    public string $company_description       = '';
    public string $website_url               = '';
    public string $department_routing_prompt  = '';
    public string $initial_greeting          = '';
    public int    $max_bot_turns             = 5;
    public int    $response_delay            = 0;
    public string $handoff_message           = '';

    public function mount(): void
    {
        $config = AiBotConfig::current();
        if ($config) {
            $this->is_active                 = $config->is_active;
            $this->system_prompt             = $config->system_prompt ?? '';
            $tones = $config->voice_tones;
            if (is_string($tones)) {
                $decoded = json_decode($tones, true);
                $this->voice_tones = is_array($decoded) ? $decoded : array_map('trim', explode(',', $tones));
            } elseif (is_array($tones)) {
                $this->voice_tones = $tones;
            } else {
                $this->voice_tones = [];
            }
            $this->company_description       = $config->company_description ?? '';
            $this->website_url               = $config->website_url ?? '';
            $this->department_routing_prompt  = $config->department_routing_prompt ?? '';
            $this->initial_greeting          = $config->initial_greeting ?? '';
            $this->max_bot_turns             = $config->max_bot_turns ?? 5;
            $this->response_delay            = $config->response_delay ?? 0;
            $this->handoff_message           = $config->handoff_message ?? '';
        }
    }

    public function toggleActive(): void
    {
        $config = AiBotConfig::current() ?? AiBotConfig::create([]);

        if (!$config->hasKey()) {
            $this->dispatch('toast', type: 'error', message: 'A chave da API Gemini não está configurada. Peça ao administrador do sistema para configurar em Configurações Globais.');
            return;
        }

        $this->is_active = !$this->is_active;
        $config->update(['is_active' => $this->is_active]);

        if ($this->is_active) {
            ChatbotMenuConfig::query()->update(['is_active' => false]);
            $this->dispatch('toast', type: 'success', message: 'IA ativada. Chatbot foi desativado automaticamente.');
        } else {
            $this->dispatch('toast', type: 'success', message: 'IA de atendimento desativada.');
        }
    }

    public const AVAILABLE_TONES = [
        '😊 Amigável', '💼 Profissional', '🎯 Objetivo', '😄 Descontraído',
        '🤝 Empático', '📋 Formal', '⚡ Dinâmico', '🌟 Entusiasmado',
        '🧘 Calmo', '💡 Consultivo', '🛡️ Confiável', '🎨 Criativo',
    ];

    public function toggleTone(string $tone): void
    {
        if (in_array($tone, $this->voice_tones)) {
            $this->voice_tones = array_values(array_diff($this->voice_tones, [$tone]));
        } else {
            $this->voice_tones[] = $tone;
        }
    }

    public function scrapeNow(): void
    {
        if (!$this->website_url) {
            $this->dispatch('toast', type: 'error', message: 'Informe a URL do site primeiro.');
            return;
        }

        $content = $this->scrapeWebsite($this->website_url);
        if ($content) {
            $pages = substr_count($content, '===');
            $chars = strlen($content);

            AiBotConfig::updateOrCreate(
                ['company_id' => app(\App\Services\CurrentCompany::class)->id()],
                ['website_url' => $this->website_url, 'website_content' => $content]
            );

            $this->dispatch('toast', type: 'success', message: "Site lido com sucesso! {$pages} páginas, {$chars} caracteres extraídos.");
        } else {
            $this->dispatch('toast', type: 'error', message: 'Não foi possível ler o site. Verifique a URL.');
        }
    }

    public function save(): void
    {
        $this->validate([
            'system_prompt'             => 'nullable|string|max:8000',
            'voice_tones'               => 'nullable|array',
            'company_description'       => 'nullable|string|max:4000',
            'website_url'               => 'nullable|string|max:500',
            'department_routing_prompt' => 'nullable|string|max:2000',
            'initial_greeting'          => 'nullable|string|max:1000',
            'max_bot_turns'             => 'required|integer|min:1|max:50',
            'response_delay'            => 'required|integer|min:0|max:120',
            'handoff_message'           => 'nullable|string|max:1000',
        ]);

        $data = [
            'system_prompt'             => $this->system_prompt ?: null,
            'voice_tones'               => !empty($this->voice_tones) ? json_encode(array_values($this->voice_tones)) : null,
            'company_description'       => $this->company_description ?: null,
            'website_url'               => $this->website_url ?: null,
            'department_routing_prompt' => $this->department_routing_prompt ?: null,
            'initial_greeting'          => $this->initial_greeting ?: null,
            'max_bot_turns'             => $this->max_bot_turns,
            'response_delay'            => $this->response_delay,
            'handoff_message'           => $this->handoff_message ?: null,
        ];

        // Extrai conteúdo do site se URL foi informada
        if ($this->website_url) {
            $content = $this->scrapeWebsite($this->website_url);
            if ($content) {
                $data['website_content'] = $content;
            }
        } else {
            $data['website_content'] = null;
        }

        AiBotConfig::updateOrCreate(
            ['company_id' => app(\App\Services\CurrentCompany::class)->id()],
            $data
        );

        $this->dispatch('toast', type: 'success', message: 'Configurações do robô salvas.');
    }

    private function scrapeWebsite(string $url): ?string
    {
        try {
            $baseUrl = rtrim($url, '/');
            $ctx = stream_context_create(['http' => ['timeout' => 15, 'user_agent' => 'Mozilla/5.0']]);

            // Busca página principal e extrai links internos
            $html = @file_get_contents($url, false, $ctx);
            if (!$html) return null;

            preg_match_all('/href=["\']([^"\']+)["\']/i', $html, $matches);
            $pages = [$baseUrl . '/'];
            foreach (array_unique($matches[1] ?? []) as $link) {
                if (str_starts_with($link, '/') && !str_contains($link, '#') && !str_contains($link, '.') && strlen($link) > 1) {
                    $pages[] = $baseUrl . $link;
                }
            }
            $pages = array_unique(array_slice($pages, 0, 10)); // máx 10 páginas

            // Extrai texto de cada página
            $allText = '';
            foreach ($pages as $pageUrl) {
                $pageHtml = @file_get_contents($pageUrl, false, $ctx);
                if (!$pageHtml) continue;

                $pageHtml = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $pageHtml);
                $pageHtml = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $pageHtml);
                $pageHtml = preg_replace('/<noscript[^>]*>.*?<\/noscript>/si', '', $pageHtml);

                $text = strip_tags($pageHtml);
                $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
                $text = preg_replace('/[ \t]+/', ' ', $text);
                $text = preg_replace('/\n\s*\n/', "\n", $text);
                $lines = array_filter(array_map('trim', explode("\n", trim($text))), fn($l) => strlen($l) > 2);
                $pageText = implode("\n", $lines);

                if ($pageText) {
                    $allText .= "\n\n=== {$pageUrl} ===\n{$pageText}";
                }
            }

            return trim($allText) ?: null;
        } catch (\Throwable $e) {
            \Log::warning('scrapeWebsite failed', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function render()
    {
        $departments    = Department::active()->get();
        $globalKeySet   = !empty(GlobalSetting::get('gemini_api_key'));
        $globalModel    = GlobalSetting::get('gemini_model', 'gemini-2.0-flash');
        return view('livewire.admin.ai-bot-manager', compact('departments', 'globalKeySet', 'globalModel'));
    }
}
