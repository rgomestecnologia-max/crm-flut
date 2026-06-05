<?php

namespace App\Livewire\Leads;

use App\Models\BroadcastContact;
use App\Models\CrmCard;
use App\Models\CrmCardFieldValue;
use App\Models\CrmCustomField;
use App\Models\CrmPipeline;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class LeadManager extends Component
{
    use WithPagination, WithFileUploads;

    public string $search    = '';
    public string $filterTag = '';
    public string $filterType = '';

    // Conversar
    public bool $showChatModal = false;
    public ?int $chatLeadId = null;
    public ?int $chatDeptId = null;

    // Form
    public bool    $showForm     = false;
    public ?int    $editingId    = null;
    public string  $type         = 'person';
    public string  $name         = '';
    public string  $company_name = '';
    public string  $document     = '';
    public string  $phone        = '';
    public string  $email        = '';
    public string  $address      = '';
    public string  $city         = '';
    public string  $state        = '';
    public string  $tags         = '';
    public string  $notes        = '';
    public bool    $is_active    = true;

    // CRM
    public ?int   $pipelineId = null;
    public array  $customFieldValues = [];

    // CSV import
    public bool $showImport  = false;
    public      $csvFile     = null;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterTag(): void { $this->resetPage(); }
    public function updatingFilterType(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->reset('editingId', 'type', 'name', 'company_name', 'document', 'phone', 'email', 'address', 'city', 'state', 'tags', 'notes', 'is_active', 'pipelineId', 'customFieldValues');
        $this->type = 'person';
        $this->is_active = true;
        $this->showForm  = true;
    }

    public function openEdit(int $id): void
    {
        $lead = BroadcastContact::findOrFail($id);
        $this->editingId    = $id;
        $this->type         = $lead->type ?? 'person';
        $this->name         = $lead->name ?? '';
        $this->company_name = $lead->company_name ?? '';
        $this->document     = $lead->document ?? '';
        $this->phone        = $lead->phone;
        $this->email        = $lead->email ?? '';
        $this->address      = $lead->address ?? '';
        $this->city         = $lead->city ?? '';
        $this->state        = $lead->state ?? '';
        $this->tags         = is_array($lead->tags) ? implode(', ', $lead->tags) : '';
        $this->notes        = $lead->notes ?? '';
        $this->is_active    = $lead->is_active;

        // Carrega valores dos campos CRM se tem card
        $this->customFieldValues = [];
        $contact = \App\Models\Contact::where('phone', $lead->phone)->first();
        if ($contact) {
            $card = CrmCard::where('contact_id', $contact->id)->latest()->first();
            if ($card) {
                $this->pipelineId = $card->pipeline_id;
                $this->customFieldValues = CrmCardFieldValue::where('card_id', $card->id)
                    ->pluck('value', 'field_id')->toArray();
            }
        }

        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'  => 'nullable|string|max:200',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:200',
            'document' => 'nullable|string|max:30',
        ]);

        $phone = preg_replace('/\D/', '', $this->phone);
        if (strlen($phone) <= 11 && !str_starts_with($phone, '55')) {
            $phone = '55' . $phone;
        }

        $tagsArray = $this->tags
            ? array_map('trim', explode(',', $this->tags))
            : [];

        $data = [
            'type'         => $this->type,
            'name'         => $this->name ?: null,
            'company_name' => $this->type === 'company' ? ($this->company_name ?: null) : null,
            'document'     => $this->document ?: null,
            'phone'        => $phone,
            'email'        => $this->email ?: null,
            'address'      => $this->address ?: null,
            'city'         => $this->city ?: null,
            'state'        => $this->state ?: null,
            'tags'         => array_values(array_filter($tagsArray)),
            'notes'        => $this->notes ?: null,
            'is_active'    => $this->is_active,
        ];

        if ($this->editingId) {
            $bc = BroadcastContact::findOrFail($this->editingId);
            $oldTags = $bc->tags ?? [];
            $bc->update($data);
            $newTags = array_diff($data['tags'], $oldTags);
            if (!empty($newTags)) {
                \App\Services\EmailFunnelEnroller::enrollByTag($bc->company_id, $bc->id, $newTags);
            }
            $this->dispatch('toast', type: 'success', message: 'Lead atualizado.');
        } else {
            $bc = BroadcastContact::create($data);
            if (!empty($data['tags'])) {
                \App\Services\EmailFunnelEnroller::enrollByTag($bc->company_id, $bc->id, $data['tags']);
            }
            $this->dispatch('toast', type: 'success', message: 'Lead adicionado.');
        }

        // Sincronizar Contact do atendimento
        $contact = \App\Models\Contact::where('phone', $phone)->first();
        if (!$contact) {
            $contact = \App\Models\Contact::create([
                'phone' => $phone,
                'name'  => $this->name ?: ($this->company_name ?: null),
            ]);
        } elseif ($this->name) {
            $contact->update(['name' => $this->name]);
        }

        // CRM: cria ou atualiza card + campos personalizados
        $this->saveCardAndFields($contact, $bc);

        $this->showForm = false;
    }

    private function saveCardAndFields(\App\Models\Contact $contact, BroadcastContact $bc): void
    {
        $pipelineId = $this->pipelineId ?: CrmPipeline::first()?->id;
        if (!$pipelineId) return;

        $pipeline = CrmPipeline::find($pipelineId);
        if (!$pipeline) return;

        $firstStage = $pipeline->stages()->orderBy('sort_order')->first();
        if (!$firstStage) return;

        // Busca card existente ou cria
        $card = CrmCard::where('contact_id', $contact->id)
            ->where('pipeline_id', $pipelineId)
            ->first();

        if (!$card) {
            $card = CrmCard::create([
                'pipeline_id' => $pipelineId,
                'stage_id'    => $firstStage->id,
                'contact_id'  => $contact->id,
                'title'       => $this->name ?: ($this->company_name ?: $this->phone),
            ]);
        }

        // Salva campos personalizados
        $customFields = CrmCustomField::orderBy('sort_order')->get();
        foreach ($customFields as $field) {
            $value = $this->customFieldValues[$field->id] ?? null;
            if ($value !== null && $value !== '') {
                CrmCardFieldValue::updateOrCreate(
                    ['card_id' => $card->id, 'field_id' => $field->id],
                    ['value' => $value]
                );
            }
        }
    }

    public function openChat(int $leadId): void
    {
        $this->chatLeadId = $leadId;
        $this->chatDeptId = null;
        $this->showChatModal = true;
    }

    public function startChat()
    {
        if (!$this->chatLeadId) return;

        $lead = BroadcastContact::findOrFail($this->chatLeadId);
        $phone = $lead->phone;
        if (!$phone) {
            $this->dispatch('toast', type: 'error', message: 'Lead sem telefone.');
            return;
        }

        $user = \Illuminate\Support\Facades\Auth::user();

        // Busca ou cria Contact
        $contact = \App\Models\Contact::where('phone', $phone)->first();
        if (!$contact) {
            $contact = \App\Models\Contact::create([
                'phone' => $phone,
                'name'  => $lead->name ?: ($lead->company_name ?: null),
            ]);
        }

        // Departamento e instância
        $deptId = $this->chatDeptId ?: \App\Models\Department::active()->first()?->id;
        $dept = \App\Models\Department::find($deptId);
        $evoConfigId = $dept?->evolution_api_config_id ?? \App\Models\EvolutionApiConfig::first()?->id;

        // Busca conversa existente nessa instância
        $conv = \App\Models\Conversation::where('contact_id', $contact->id)
            ->where('is_group', false)
            ->when($evoConfigId, fn($q) => $q->where('evolution_api_config_id', $evoConfigId))
            ->latest()
            ->first();

        if ($conv) {
            if ($conv->status === 'resolved') {
                $conv->update([
                    'status'               => 'open',
                    'assigned_to'          => $user->id,
                    'waiting_human_reason' => null,
                ]);
            }
        } else {
            $conv = \App\Models\Conversation::create([
                'contact_id'             => $contact->id,
                'department_id'          => $deptId,
                'evolution_api_config_id' => $evoConfigId,
                'status'                 => 'open',
                'assigned_to'            => $user->id,
                'is_group'               => false,
                'last_message_at'        => now(),
            ]);
        }

        $this->showChatModal = false;
        $this->chatLeadId = null;

        return $this->redirect(route('chat') . '?conv=' . $conv->id, navigate: true);
    }

    public function toggleActive(int $id): void
    {
        $lead = BroadcastContact::findOrFail($id);
        $lead->update(['is_active' => !$lead->is_active]);
    }

    public function delete(int $id): void
    {
        BroadcastContact::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Lead removido.');
    }

    public function openImport(): void
    {
        $this->showImport = true;
        $this->csvFile    = null;
    }

    public function importCsv(): void
    {
        $this->validate(['csvFile' => 'required|file|mimes:csv,txt|max:2048']);

        $path  = $this->csvFile->getRealPath();
        $lines = array_filter(array_map('str_getcsv', file($path)));
        $count = 0;

        foreach ($lines as $i => $row) {
            if ($i === 0 && (strtolower($row[0] ?? '') === 'nome' || strtolower($row[0] ?? '') === 'name')) {
                continue;
            }

            $name  = trim($row[0] ?? '');
            $phone = preg_replace('/\D/', '', $row[1] ?? $row[0] ?? '');

            if (!$phone || strlen($phone) < 8) continue;

            if (strlen($phone) <= 11 && !str_starts_with($phone, '55')) {
                $phone = '55' . $phone;
            }

            BroadcastContact::firstOrCreate(
                ['phone' => $phone],
                ['name' => $name ?: null, 'tags' => [], 'is_active' => true]
            );
            $count++;
        }

        $this->showImport = false;
        $this->dispatch('toast', type: 'success', message: "{$count} leads importados.");
    }

    public function render()
    {
        $query = BroadcastContact::query();

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('company_name', 'like', "%{$s}%")
                  ->orWhere('document', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($this->filterTag) {
            $query->whereJsonContains('tags', $this->filterTag);
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        $leads = $query->orderByDesc('created_at')->paginate(15);

        $allTags = BroadcastContact::whereNotNull('tags')
            ->pluck('tags')->flatten()->unique()->sort()->values();

        $pipelines = CrmPipeline::with('stages')->get();
        $customFields = CrmCustomField::orderBy('sort_order')->get();

        return view('livewire.leads.lead-manager', compact('leads', 'allTags', 'pipelines', 'customFields'));
    }
}
