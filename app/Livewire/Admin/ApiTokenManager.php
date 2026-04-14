<?php

namespace App\Livewire\Admin;

use App\Models\ApiToken;
use App\Models\CrmPipeline;
use App\Models\CrmStage;
use Livewire\Component;

class ApiTokenManager extends Component
{
    public bool    $showForm      = false;
    public string  $token_name   = '';
    public ?int    $pipeline_id  = null;
    public ?int    $stage_id     = null;

    // Token gerado — exibido apenas uma vez
    public ?string $generatedToken = null;

    public function openForm(): void
    {
        $this->reset(['token_name', 'pipeline_id', 'stage_id', 'generatedToken']);
        $this->showForm = true;
    }

    public function updatedPipelineId(?int $value): void
    {
        $this->stage_id = CrmStage::where('pipeline_id', $value)
            ->orderBy('sort_order')->value('id');
    }

    public function generate(): void
    {
        $this->validate([
            'token_name'  => 'required|string|max:100',
            'pipeline_id' => 'nullable|exists:crm_pipelines,id',
            'stage_id'    => 'nullable|exists:crm_stages,id',
        ]);

        ['plain' => $plain] = ApiToken::generate(
            $this->token_name,
            $this->pipeline_id ?: null,
            $this->stage_id    ?: null,
        );

        $this->generatedToken = $plain;
        $this->showForm       = false;
        $this->dispatch('toast', type: 'success', message: 'Token gerado! Copie agora — não será exibido novamente.');
    }

    public function revoke(int $id): void
    {
        ApiToken::findOrFail($id)->update(['is_active' => false]);
        $this->dispatch('toast', type: 'success', message: 'Token revogado.');
    }

    public function activate(int $id): void
    {
        ApiToken::findOrFail($id)->update(['is_active' => true]);
        $this->dispatch('toast', type: 'success', message: 'Token reativado.');
    }

    public function delete(int $id): void
    {
        ApiToken::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Token removido.');
    }

    public function render()
    {
        $tokens   = ApiToken::with(['defaultPipeline', 'defaultStage'])->latest()->get();
        $pipelines = CrmPipeline::active()->orderBy('sort_order')->get();
        $stages    = $this->pipeline_id
            ? CrmStage::where('pipeline_id', $this->pipeline_id)->orderBy('sort_order')->get()
            : collect();

        return view('livewire.admin.api-token-manager', compact('tokens', 'pipelines', 'stages'));
    }
}
