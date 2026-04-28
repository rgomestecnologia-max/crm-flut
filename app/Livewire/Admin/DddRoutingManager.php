<?php

namespace App\Livewire\Admin;

use App\Models\DddRoutingRule;
use App\Models\User;
use App\Services\CurrentCompany;
use Livewire\Component;

class DddRoutingManager extends Component
{
    public array $rules = []; // ddd => agent_id
    public ?int  $selectedAgent = null;
    public string $newDdds = '';

    public function mount(): void
    {
        $this->loadRules();
    }

    private function loadRules(): void
    {
        $this->rules = DddRoutingRule::where('is_active', true)
            ->pluck('agent_id', 'ddd')
            ->toArray();
    }

    public function addDdds(): void
    {
        if (!$this->selectedAgent || !$this->newDdds) return;

        $ddds = array_map('trim', preg_split('/[\s,;]+/', $this->newDdds));
        $ddds = array_filter($ddds, fn($d) => preg_match('/^\d{2}$/', $d));

        foreach ($ddds as $ddd) {
            DddRoutingRule::updateOrCreate(
                ['ddd' => $ddd],
                ['agent_id' => $this->selectedAgent, 'is_active' => true]
            );
        }

        $this->newDdds = '';
        $this->loadRules();
        $this->dispatch('toast', type: 'success', message: count($ddds) . ' DDD(s) atribuídos.');
    }

    public function removeDdd(string $ddd): void
    {
        DddRoutingRule::where('ddd', $ddd)->delete();
        $this->loadRules();
    }

    public function render()
    {
        $companyId = app(CurrentCompany::class)->id();
        $agents = User::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Group DDDs by agent
        $grouped = [];
        foreach ($this->rules as $ddd => $agentId) {
            $grouped[$agentId][] = $ddd;
        }

        return view('livewire.admin.ddd-routing-manager', compact('agents', 'grouped'));
    }
}
