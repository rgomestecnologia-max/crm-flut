<?php

namespace App\Livewire\Leads;

use App\Models\BroadcastContact;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class LeadManager extends Component
{
    use WithPagination, WithFileUploads;

    public string $search    = '';
    public string $filterTag = '';

    // Form
    public bool    $showForm  = false;
    public ?int    $editingId = null;
    public string  $name      = '';
    public string  $phone     = '';
    public string  $tags      = '';
    public bool    $is_active = true;

    // CSV import
    public bool $showImport  = false;
    public      $csvFile     = null;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterTag(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->reset('editingId', 'name', 'phone', 'tags', 'is_active');
        $this->is_active = true;
        $this->showForm  = true;
    }

    public function openEdit(int $id): void
    {
        $lead = BroadcastContact::findOrFail($id);
        $this->editingId = $id;
        $this->name      = $lead->name ?? '';
        $this->phone     = $lead->phone;
        $this->tags      = is_array($lead->tags) ? implode(', ', $lead->tags) : '';
        $this->is_active = $lead->is_active;
        $this->showForm  = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'  => 'nullable|string|max:200',
            'phone' => 'required|string|max:30',
        ]);

        $phone = preg_replace('/\D/', '', $this->phone);
        if (strlen($phone) <= 11 && !str_starts_with($phone, '55')) {
            $phone = '55' . $phone;
        }

        $tagsArray = $this->tags
            ? array_map('trim', explode(',', $this->tags))
            : [];

        $data = [
            'name'      => $this->name ?: null,
            'phone'     => $phone,
            'tags'      => array_values(array_filter($tagsArray)),
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            BroadcastContact::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', type: 'success', message: 'Lead atualizado.');
        } else {
            BroadcastContact::create($data);
            $this->dispatch('toast', type: 'success', message: 'Lead adicionado.');
        }

        $this->showForm = false;
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
                continue; // skip header
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
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('phone', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterTag) {
            $query->whereJsonContains('tags', $this->filterTag);
        }

        $leads = $query->orderByDesc('created_at')->paginate(15);

        // Coleta todas as tags únicas para o filtro
        $allTags = BroadcastContact::whereNotNull('tags')
            ->pluck('tags')->flatten()->unique()->sort()->values();

        return view('livewire.leads.lead-manager', compact('leads', 'allTags'));
    }
}
