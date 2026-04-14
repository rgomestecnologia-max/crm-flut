<?php

namespace App\Livewire\Admin;

use App\Models\CrmPipeline;
use App\Models\CrmStage;
use Livewire\Component;

class CrmPipelineManager extends Component
{
    // Pipeline
    public bool   $showPipelineForm   = false;
    public ?int   $editingPipelineId  = null;
    public string $pipeline_name      = '';
    public string $pipeline_color     = '#b2ff00';
    public string $pipeline_desc      = '';

    // Etapas — pipeline expandido
    public ?int   $openPipelineId     = null;
    public bool   $showStageForm      = false;
    public ?int   $editingStageId     = null;
    public string $stage_name         = '';
    public string $stage_color        = '#6B7280';

    public array $presetColors = [
        '#EAB308','#F97316','#A855F7','#EF4444',
        '#22C55E','#3B82F6','#b2ff00','#EC4899',
        '#6366F1','#64748B',
    ];

    // ── Pipeline ──────────────────────────────────────────────────

    public function openCreatePipeline(): void
    {
        $this->reset(['editingPipelineId','pipeline_name','pipeline_desc']);
        $this->pipeline_color     = '#b2ff00';
        $this->showPipelineForm   = true;
        $this->showStageForm      = false;
    }

    public function openEditPipeline(int $id): void
    {
        $p = CrmPipeline::findOrFail($id);
        $this->editingPipelineId  = $id;
        $this->pipeline_name      = $p->name;
        $this->pipeline_color     = $p->color;
        $this->pipeline_desc      = $p->description ?? '';
        $this->showPipelineForm   = true;
        $this->showStageForm      = false;
    }

    public function savePipeline(): void
    {
        $this->validate(['pipeline_name' => 'required|string|max:100']);

        $data = [
            'name'        => $this->pipeline_name,
            'color'       => $this->pipeline_color,
            'description' => $this->pipeline_desc ?: null,
        ];

        if ($this->editingPipelineId) {
            CrmPipeline::findOrFail($this->editingPipelineId)->update($data);
            $this->dispatch('toast', type: 'success', message: 'Pipeline atualizado.');
        } else {
            $p = CrmPipeline::create(array_merge($data, [
                'sort_order' => CrmPipeline::max('sort_order') + 1,
            ]));
            $this->openPipelineId  = $p->id; // abre para adicionar etapas
            $this->dispatch('toast', type: 'success', message: 'Pipeline criado! Agora adicione as etapas.');
        }

        $this->showPipelineForm = false;
        $this->reset(['editingPipelineId','pipeline_name','pipeline_desc']);
        $this->pipeline_color = '#b2ff00';
    }

    public function deletePipeline(int $id): void
    {
        $p = CrmPipeline::withCount('cards')->findOrFail($id);
        if ($p->cards_count > 0) {
            $this->dispatch('toast', type: 'error', message: "Mova ou remova os {$p->cards_count} card(s) antes de excluir.");
            return;
        }
        if ($this->openPipelineId === $id) $this->openPipelineId = null;
        $p->delete();
        $this->dispatch('toast', type: 'success', message: 'Pipeline removido.');
    }

    public function togglePipeline(int $id): void
    {
        $p = CrmPipeline::findOrFail($id);
        $p->update(['is_active' => !$p->is_active]);
    }

    public function toggleOpen(int $id): void
    {
        $this->openPipelineId = ($this->openPipelineId === $id) ? null : $id;
        $this->showStageForm  = false;
        $this->showPipelineForm = false;
    }

    public function movePipelineUp(int $id): void   { $this->reorderPipeline($id, 'up'); }
    public function movePipelineDown(int $id): void { $this->reorderPipeline($id, 'down'); }

    private function reorderPipeline(int $id, string $dir): void
    {
        $list  = CrmPipeline::orderBy('sort_order')->get();
        $index = $list->search(fn($p) => $p->id === $id);
        if ($index === false) return;
        $swap  = $dir === 'up' ? $index - 1 : $index + 1;
        if ($swap < 0 || $swap >= $list->count()) return;
        [$list[$index]->sort_order, $list[$swap]->sort_order] = [$list[$swap]->sort_order, $list[$index]->sort_order];
        $list[$index]->save();
        $list[$swap]->save();
    }

    // ── Etapas ────────────────────────────────────────────────────

    public function openCreateStage(): void
    {
        $this->reset(['editingStageId','stage_name']);
        $this->stage_color   = '#6B7280';
        $this->showStageForm = true;
    }

    public function openEditStage(int $id): void
    {
        $s = CrmStage::findOrFail($id);
        $this->editingStageId = $id;
        $this->stage_name     = $s->name;
        $this->stage_color    = $s->color;
        $this->showStageForm  = true;
    }

    public function saveStage(): void
    {
        $this->validate(['stage_name' => 'required|string|max:100']);

        $data = ['name' => $this->stage_name, 'color' => $this->stage_color];

        if ($this->editingStageId) {
            CrmStage::findOrFail($this->editingStageId)->update($data);
            $this->dispatch('toast', type: 'success', message: 'Etapa atualizada.');
        } else {
            CrmStage::create(array_merge($data, [
                'pipeline_id' => $this->openPipelineId,
                'sort_order'  => CrmStage::where('pipeline_id', $this->openPipelineId)->max('sort_order') + 1,
            ]));
            $this->dispatch('toast', type: 'success', message: 'Etapa adicionada.');
        }

        $this->showStageForm = false;
        $this->reset(['editingStageId','stage_name']);
        $this->stage_color = '#6B7280';
    }

    public function deleteStage(int $id): void
    {
        $s = CrmStage::withCount('cards')->findOrFail($id);
        if ($s->cards_count > 0) {
            $this->dispatch('toast', type: 'error', message: "Mova os {$s->cards_count} card(s) antes de excluir esta etapa.");
            return;
        }
        $s->delete();
        $this->dispatch('toast', type: 'success', message: 'Etapa removida.');
    }

    public function moveStageUp(int $id): void   { $this->reorderStage($id, 'up'); }
    public function moveStageDown(int $id): void { $this->reorderStage($id, 'down'); }

    private function reorderStage(int $id, string $dir): void
    {
        $list  = CrmStage::where('pipeline_id', $this->openPipelineId)->orderBy('sort_order')->get();
        $index = $list->search(fn($s) => $s->id === $id);
        if ($index === false) return;
        $swap  = $dir === 'up' ? $index - 1 : $index + 1;
        if ($swap < 0 || $swap >= $list->count()) return;
        [$list[$index]->sort_order, $list[$swap]->sort_order] = [$list[$swap]->sort_order, $list[$index]->sort_order];
        $list[$index]->save();
        $list[$swap]->save();
    }

    public function render()
    {
        $pipelines = CrmPipeline::withCount('cards')
            ->with(['stages' => fn($q) => $q->withCount('cards')->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('livewire.admin.crm-pipeline-manager', compact('pipelines'));
    }
}
