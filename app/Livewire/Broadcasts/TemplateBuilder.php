<?php

namespace App\Livewire\Broadcasts;

use App\Models\CampaignTemplate;
use App\Models\GlobalSetting;
use App\Services\MediaStorage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

class TemplateBuilder extends Component
{
    use WithFileUploads;

    public bool   $showForm     = false;
    public ?int   $editingId    = null;
    public string $name         = '';
    public string $channel      = 'whatsapp';
    public string $message      = '';
    public string $subject      = '';
    public string $headerColor  = '#b2ff00';
    public        $imageUpload  = null;
    public        $logoUpload   = null;
    public ?string $existingImage = null;
    public ?string $existingLogo  = null;

    // AI Image Generation
    public string $aiPrompt       = '';
    public        $aiRefImages    = [];
    public bool   $aiGenerating   = false;
    public ?string $aiGeneratedUrl = null;

    public function openCreate(): void
    {
        $this->reset('editingId', 'name', 'channel', 'message', 'subject', 'headerColor', 'imageUpload', 'logoUpload', 'existingImage', 'existingLogo', 'aiPrompt', 'aiRefImages', 'aiGeneratedUrl');
        $this->channel = 'whatsapp';
        $this->headerColor = '#b2ff00';
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $tpl = CampaignTemplate::findOrFail($id);
        $this->editingId    = $id;
        $this->name         = $tpl->name;
        $this->channel      = $tpl->channel;
        $this->message      = $tpl->message ?? '';
        $this->subject      = $tpl->subject ?? '';
        $this->headerColor  = $tpl->header_color ?? '#b2ff00';
        $this->existingImage = $tpl->getImageUrl();
        $this->existingLogo  = $tpl->getLogoUrl();
        $this->aiPrompt     = $tpl->ai_prompt ?? '';
        $this->aiGeneratedUrl = null;
        $this->imageUpload  = null;
        $this->logoUpload   = null;
        $this->showForm     = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'    => 'required|string|max:100',
            'channel' => 'required|in:whatsapp,email',
            'message' => 'required|string|max:4000',
        ]);

        $data = [
            'name'         => $this->name,
            'channel'      => $this->channel,
            'message'      => $this->message,
            'subject'      => $this->channel === 'email' ? $this->subject : null,
            'header_color' => $this->channel === 'email' ? $this->headerColor : null,
            'ai_prompt'    => $this->aiPrompt ?: null,
        ];

        if ($this->imageUpload) {
            $data['image_path'] = MediaStorage::store($this->imageUpload, 'templates');
        } elseif ($this->aiGeneratedUrl) {
            $data['image_path'] = $this->aiGeneratedUrl;
        }

        if ($this->logoUpload) {
            $data['logo_path'] = MediaStorage::store($this->logoUpload, 'templates');
        }

        if ($this->editingId) {
            CampaignTemplate::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', type: 'success', message: 'Template atualizado.');
        } else {
            $data['created_by'] = Auth::id();
            CampaignTemplate::create($data);
            $this->dispatch('toast', type: 'success', message: 'Template criado.');
        }

        $this->showForm = false;
    }

    public function delete(int $id): void
    {
        $tpl = CampaignTemplate::findOrFail($id);
        if ($tpl->image_path) MediaStorage::delete($tpl->image_path);
        if ($tpl->logo_path) MediaStorage::delete($tpl->logo_path);
        $tpl->delete();
        $this->dispatch('toast', type: 'success', message: 'Template excluído.');
    }

    public function duplicate(int $id): void
    {
        $tpl = CampaignTemplate::findOrFail($id);
        $new = $tpl->replicate();
        $new->name = $tpl->name . ' (cópia)';
        $new->created_by = Auth::id();
        $new->save();
        $this->dispatch('toast', type: 'success', message: 'Template duplicado.');
    }

    public function generateImage(): void
    {
        if (!$this->aiPrompt) {
            $this->dispatch('toast', type: 'error', message: 'Digite um prompt para gerar a imagem.');
            return;
        }

        $apiKey = GlobalSetting::get('gemini_api_key');
        $model  = GlobalSetting::get('gemini_model', 'gemini-2.0-flash');
        if (!$apiKey) {
            $this->dispatch('toast', type: 'error', message: 'Gemini API key não configurada.');
            return;
        }

        $this->aiGenerating = true;

        try {
            $parts = [];

            // Imagens de referência anexadas
            if (!empty($this->aiRefImages)) {
                foreach ($this->aiRefImages as $img) {
                    $bytes = file_get_contents($img->getRealPath());
                    $parts[] = [
                        'inline_data' => [
                            'mime_type' => $img->getMimeType(),
                            'data' => base64_encode($bytes),
                        ],
                    ];
                }
            }

            $channelLabel = $this->channel === 'email' ? 'email marketing' : 'WhatsApp';
            $parts[] = [
                'text' => "Crie uma imagem promocional profissional para {$channelLabel} com base neste briefing: {$this->aiPrompt}. "
                    . "A imagem deve ser clean, moderna, com boa tipografia e cores vibrantes. "
                    . "Tamanho ideal: 1200x628px para email ou 800x800px para WhatsApp. "
                    . "Não inclua texto longo na imagem, apenas elementos visuais e título curto se necessário.",
            ];

            // Usa modelo com suporte a geração de imagem
            $imageModel = 'gemini-2.5-flash-image';
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$imageModel}:generateContent?key={$apiKey}";
            $response = Http::timeout(90)->post($url, [
                'contents' => [['parts' => $parts]],
                'generationConfig' => [
                    'responseModalities' => ['image', 'text'],
                ],
            ]);

            if ($response->successful()) {
                $candidates = $response->json('candidates') ?? [];
                foreach ($candidates as $candidate) {
                    foreach ($candidate['content']['parts'] ?? [] as $part) {
                        if (!empty($part['inlineData'])) {
                            $imageData = base64_decode($part['inlineData']['data']);
                            $mime = $part['inlineData']['mimeType'] ?? 'image/png';
                            $ext = str_contains($mime, 'png') ? 'png' : 'jpg';
                            $filename = 'ai_' . uniqid() . '.' . $ext;
                            $path = 'templates/' . $filename;
                            MediaStorage::put($path, $imageData);
                            $this->aiGeneratedUrl = $path;
                            $this->existingImage = MediaStorage::url($path);
                            $this->dispatch('toast', type: 'success', message: 'Imagem gerada com sucesso!');
                            $this->aiGenerating = false;
                            return;
                        }
                    }
                }
                $this->dispatch('toast', type: 'error', message: 'A IA não retornou uma imagem. Tente outro prompt.');
            } else {
                Log::error('Gemini image generation failed', ['status' => $response->status(), 'body' => substr($response->body(), 0, 500)]);
                $this->dispatch('toast', type: 'error', message: 'Erro ao gerar imagem: ' . ($response->json('error.message') ?? 'erro desconhecido'));
            }
        } catch (\Throwable $e) {
            Log::error('Gemini image generation exception', ['error' => $e->getMessage()]);
            $this->dispatch('toast', type: 'error', message: 'Erro: ' . $e->getMessage());
        }

        $this->aiGenerating = false;
    }

    public function render()
    {
        $templates = CampaignTemplate::active()
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.broadcasts.template-builder', compact('templates'));
    }
}
