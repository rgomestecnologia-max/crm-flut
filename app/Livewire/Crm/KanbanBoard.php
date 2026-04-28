<?php

namespace App\Livewire\Crm;

use App\Models\Contact;
use App\Models\CrmCard;
use App\Models\CrmCardActivity;
use App\Models\CrmCardFieldValue;
use App\Models\CrmCustomField;
use App\Models\CrmPipeline;
use App\Models\CrmStage;
use App\Models\User;
use Livewire\Component;

class KanbanBoard extends Component
{
    public ?int $selectedPipelineId = null;

    // Card panel
    public bool   $showCardPanel    = false;
    public ?int   $editingCardId    = null;
    public int    $card_stage_id    = 0;
    public string $card_title       = '';
    public string $card_description = '';
    public string $card_priority    = '';
    public ?int   $card_contact_id  = null;
    public ?int   $card_assigned_to = null;
    public array  $customValues     = []; // field_id => value
    public string $newNote          = '';
    public string $contact_phone    = '';

    public function mount(): void
    {
        $first = CrmPipeline::active()->orderBy('sort_order')->first();
        $this->selectedPipelineId = $first?->id;
    }

    public function selectPipeline(int $id): void
    {
        $this->selectedPipelineId = $id;
        $this->showCardPanel = false;
    }

    // ── Drag & drop entre colunas (etapas) ───────────────────────

    public function moveCard(int $cardId, int $stageId): void
    {
        $card  = CrmCard::find($cardId);
        $stage = CrmStage::find($stageId);

        if (!$card || !$stage || $stage->pipeline_id !== $this->selectedPipelineId) return;

        $old = $card->stage?->name ?? '—';
        $card->update(['stage_id' => $stageId, 'pipeline_id' => $stage->pipeline_id]);

        CrmCardActivity::create([
            'card_id' => $card->id,
            'user_id' => auth()->id(),
            'type'    => 'stage_change',
            'content' => "Movido de «{$old}» para «{$stage->name}»",
        ]);
    }

    // ── Card CRUD ─────────────────────────────────────────────────

    public function openCreateCard(int $stageId): void
    {
        $this->resetCardForm();
        $this->card_stage_id = $stageId;
        $this->showCardPanel = true;
    }

    public function openEditCard(int $cardId): void
    {
        $card = CrmCard::findOrFail($cardId);
        $this->editingCardId    = $cardId;
        $this->card_stage_id    = $card->stage_id ?? 0;
        $this->card_title       = $card->title;
        $this->card_description = $card->description ?? '';
        $this->card_priority    = $card->priority ?? '';
        $this->card_contact_id  = $card->contact_id;
        $this->card_assigned_to = $card->assigned_to;
        $this->contact_phone    = $card->contact?->phone ?? '';
        $this->showCardPanel    = true;
        $this->newNote          = '';

        // Carrega valores dos campos personalizados
        $this->customValues = CrmCardFieldValue::where('card_id', $cardId)
            ->pluck('value', 'field_id')
            ->toArray();
    }

    public function saveCard(): void
    {
        $this->validate([
            'card_title'       => 'required|string|max:200',
            'card_stage_id'    => 'required|exists:crm_stages,id',
            'card_priority'    => 'nullable|in:baixo,medio,alto,critico',
            'card_contact_id'  => 'nullable|exists:contacts,id',
            'card_assigned_to' => 'nullable|exists:users,id',
        ]);

        $stage = CrmStage::find($this->card_stage_id);

        $data = [
            'pipeline_id' => $stage->pipeline_id,
            'stage_id'    => $this->card_stage_id,
            'title'       => $this->card_title,
            'description' => $this->card_description ?: null,
            'priority'    => $this->card_priority ?: null,
            'contact_id'  => $this->card_contact_id,
            'assigned_to' => $this->card_assigned_to,
        ];

        if ($this->editingCardId) {
            $card = CrmCard::findOrFail($this->editingCardId);

            if ($card->stage_id !== (int) $this->card_stage_id) {
                $old = $card->stage?->name ?? '—';
                CrmCardActivity::create([
                    'card_id' => $card->id,
                    'user_id' => auth()->id(),
                    'type'    => 'stage_change',
                    'content' => "Etapa alterada de «{$old}» para «{$stage->name}»",
                ]);
            }

            $card->update($data);

            // Atualiza telefone do contato se alterado
            if ($card->contact_id && trim($this->contact_phone)) {
                $contact = Contact::find($card->contact_id);
                if ($contact && $contact->phone !== $this->contact_phone) {
                    $contact->update(['phone' => preg_replace('/\D/', '', $this->contact_phone)]);
                }
            }

            $this->dispatch('toast', type: 'success', message: 'Card atualizado.');
        } else {
            $data['sort_order'] = CrmCard::where('stage_id', $this->card_stage_id)->max('sort_order') + 1;
            $card = CrmCard::create($data);
            CrmCardActivity::create([
                'card_id' => $card->id,
                'user_id' => auth()->id(),
                'type'    => 'note',
                'content' => 'Card criado.',
            ]);
            $this->dispatch('toast', type: 'success', message: 'Card criado.');
        }

        // Salva valores dos campos personalizados
        foreach ($this->customValues as $fieldId => $value) {
            CrmCardFieldValue::updateOrCreate(
                ['card_id' => $card->id, 'field_id' => $fieldId],
                ['value'   => $value !== '' ? $value : null]
            );
        }

        $this->showCardPanel = false;
        $this->resetCardForm();
    }

    public function deleteCard(int $cardId): void
    {
        CrmCard::findOrFail($cardId)->delete();
        $this->showCardPanel = false;
        $this->resetCardForm();
        $this->dispatch('toast', type: 'success', message: 'Card removido.');
    }

    public function addNote(): void
    {
        if (!trim($this->newNote) || !$this->editingCardId) return;
        CrmCardActivity::create([
            'card_id' => $this->editingCardId,
            'user_id' => auth()->id(),
            'type'    => 'note',
            'content' => $this->newNote,
        ]);
        $this->newNote = '';
    }

    public function closePanel(): void
    {
        $this->showCardPanel = false;
        $this->resetCardForm();
    }

    private function resetCardForm(): void
    {
        $this->reset([
            'editingCardId', 'card_stage_id', 'card_title', 'card_description',
            'card_priority', 'card_contact_id', 'card_assigned_to', 'newNote', 'customValues', 'contact_phone',
        ]);
    }

    public function render()
    {
        $pipelines = CrmPipeline::active()->orderBy('sort_order')->get();

        $stages = collect();
        $cards  = collect();
        $currentPipeline = null;

        if ($this->selectedPipelineId) {
            $currentPipeline = $pipelines->firstWhere('id', $this->selectedPipelineId);
            $stages = CrmStage::where('pipeline_id', $this->selectedPipelineId)
                ->orderBy('sort_order')->get();

            $cards = CrmCard::where('pipeline_id', $this->selectedPipelineId)
                ->with(['contact', 'assignedTo', 'fieldValues.field'])
                ->orderBy('sort_order')
                ->get()
                ->groupBy('stage_id');
        }

        $contacts  = Contact::orderBy('name')->get(['id', 'name', 'phone']);
        // User não usa BelongsToCompany, então filtramos manualmente pela empresa atual.
        $agents    = User::where('is_active', true)
            ->where('company_id', app(\App\Services\CurrentCompany::class)->id())
            ->orderBy('name')
            ->get(['id', 'name']);
        $activities = $this->editingCardId
            ? CrmCardActivity::where('card_id', $this->editingCardId)
                ->with('user')->orderBy('created_at', 'desc')->get()
            : collect();

        $customFields = CrmCustomField::orderBy('sort_order')->get();

        return view('livewire.crm.kanban-board', compact(
            'pipelines', 'currentPipeline', 'stages', 'cards',
            'contacts', 'agents', 'activities', 'customFields'
        ));
    }
}
