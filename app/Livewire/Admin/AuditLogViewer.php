<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogViewer extends Component
{
    use WithPagination;

    public string $filterAction = '';
    public string $filterModel  = '';
    public string $search       = '';

    public function updatedFilterAction(): void { $this->resetPage(); }
    public function updatedFilterModel(): void  { $this->resetPage(); }
    public function updatedSearch(): void       { $this->resetPage(); }

    public function render()
    {
        $query = AuditLog::with('user')
            ->orderBy('created_at', 'desc');

        if ($this->filterAction) {
            $query->where('action', $this->filterAction);
        }

        if ($this->filterModel) {
            $query->where('auditable_type', $this->filterModel);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('user_name', 'like', "%{$this->search}%")
                  ->orWhere('auditable_label', 'like', "%{$this->search}%");
            });
        }

        $logs = $query->paginate(30);

        // Lista de models distintos presentes no log (pra popular o filtro)
        $modelTypes = AuditLog::query()
            ->distinct()
            ->pluck('auditable_type')
            ->sort()
            ->values();

        return view('livewire.admin.audit-log-viewer', compact('logs', 'modelTypes'));
    }
}
