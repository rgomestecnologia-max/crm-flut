<?php

namespace App\Livewire\Admin;

use App\Models\Proposal;
use Livewire\Component;

class ProposalViewer extends Component
{
    public $proposals = [];
    public $expandedId = null;
    public $discountId = null;
    public $discountPercent = 0;
    public $statusFilter = '';

    // Edição inline de valores
    public ?int $editValuesId = null;
    public array $editDetails = [];

    // Duplicar proposta
    public ?int $duplicateId = null;
    public string $duplicateName = '';

    public function mount()
    {
        $this->loadProposals();
    }

    public function loadProposals()
    {
        $query = Proposal::with('user')->orderByDesc('created_at');

        // Vendedor só vê as próprias propostas
        if (auth()->user()->isVendedor()) {
            $query->where('user_id', auth()->id());
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $this->proposals = $query->get()->toArray();
    }

    public function setFilter($status)
    {
        $this->statusFilter = $status;
        $this->expandedId = null;
        $this->discountId = null;
        $this->loadProposals();
    }

    public function toggle($id)
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
        $this->discountId = null;
    }

    public function setStatus($id, $status)
    {
        Proposal::findOrFail($id)->update(['status' => $status]);
        $this->loadProposals();
        $labels = ['analise' => 'Em Análise', 'aprovada' => 'Aprovada', 'reprovada' => 'Não Aprovada'];
        $this->dispatch('toast', type: 'success', message: "Status alterado para: {$labels[$status]}");
    }

    public function openDiscount($id)
    {
        if ($this->discountId === $id) {
            $this->discountId = null;
            return;
        }
        $this->discountId = $id;
        $proposal = collect($this->proposals)->firstWhere('id', $id);
        $this->discountPercent = $proposal['discount_percent'] ?? 0;
    }

    public function applyDiscount($id)
    {
        $proposal = Proposal::findOrFail($id);
        $pct = (float) $this->discountPercent;

        if ($pct < 0 || $pct > 90) {
            $this->dispatch('toast', type: 'error', message: 'Desconto deve ser entre 0% e 90%');
            return;
        }

        // Remover desconto (voltar ao original)
        if ($pct == 0 && $proposal->original_total_monthly) {
            $proposal->update([
                'total_monthly'          => $proposal->original_total_monthly,
                'total_setup'            => $proposal->original_total_setup,
                'discount_percent'       => null,
                'original_total_monthly' => null,
                'original_total_setup'   => null,
            ]);

            $this->discountId = null;
            $this->discountPercent = 0;
            $this->loadProposals();
            $this->dispatch('toast', type: 'success', message: 'Desconto removido! Valores originais restaurados.');
            return;
        }

        if ($pct <= 0) {
            $this->dispatch('toast', type: 'error', message: 'Informe um percentual de desconto');
            return;
        }

        // Salvar originais na primeira aplicação
        if (!$proposal->original_total_monthly) {
            $proposal->original_total_monthly = $proposal->total_monthly;
            $proposal->original_total_setup = $proposal->total_setup;
        }

        // Recalcular a partir dos originais (não acumular descontos)
        $factor = 1 - ($pct / 100);
        $origMonthly = $proposal->original_total_monthly;
        $origSetup = $proposal->original_total_setup;

        // Recalcular details proporcionalmente
        $details = $proposal->details;
        foreach ($details as $key => $value) {
            $details[$key] = round($value * $factor, 2);
        }

        $proposal->update([
            'details'                => $details,
            'total_monthly'          => round($origMonthly * $factor, 2),
            'total_setup'            => round($origSetup * $factor, 2),
            'discount_percent'       => $pct,
            'original_total_monthly' => $origMonthly,
            'original_total_setup'   => $origSetup,
        ]);

        $this->discountId = null;
        $this->loadProposals();

        $this->dispatch('toast', type: 'success', message: "Desconto de {$pct}% aplicado!");
    }

    public function openDuplicate(int $id): void
    {
        $this->duplicateId = $this->duplicateId === $id ? null : $id;
        $this->duplicateName = '';
    }

    public function confirmDuplicate(int $id): void
    {
        if (!trim($this->duplicateName)) {
            $this->dispatch('toast', type: 'error', message: 'Informe o nome da empresa.');
            return;
        }

        $original = Proposal::findOrFail($id);
        $new = $original->replicate(['token', 'status', 'discount_percent', 'original_total_monthly', 'original_total_setup']);
        $new->client_name = trim($this->duplicateName);
        $new->status = 'analise';
        $new->user_id = auth()->id();
        $new->total_monthly = $original->original_total_monthly ?? $original->total_monthly;
        $new->total_setup = $original->original_total_setup ?? $original->total_setup;
        $new->save();

        $this->duplicateId = null;
        $this->duplicateName = '';
        $this->loadProposals();
        $this->dispatch('toast', type: 'success', message: 'Proposta duplicada para "' . $new->client_name . '".');
    }

    public function delete($id)
    {
        Proposal::findOrFail($id)->delete();
        $this->loadProposals();
    }

    public function openEditValues(int $id): void
    {
        if ($this->editValuesId === $id) {
            $this->editValuesId = null;
            return;
        }
        $proposal = Proposal::find($id);
        $this->editDetails = $proposal->details ?? [];
        $this->editValuesId = $id;
    }

    public function saveEditValues(int $id): void
    {
        $proposal = Proposal::findOrFail($id);

        $totalMonthly = 0;
        $totalSetup = 0;
        foreach ($this->editDetails as $key => $value) {
            $this->editDetails[$key] = (float) str_replace(['.', ','], ['', '.'], (string) $value);
            if (str_ends_with($key, '_monthly')) $totalMonthly += $this->editDetails[$key];
            if (str_ends_with($key, '_setup'))   $totalSetup += $this->editDetails[$key];
        }

        $proposal->update([
            'details'       => $this->editDetails,
            'total_monthly' => round($totalMonthly, 2),
            'total_setup'   => round($totalSetup, 2),
        ]);

        $this->editValuesId = null;
        $this->loadProposals();
        $this->dispatch('toast', type: 'success', message: 'Valores personalizados salvos!');
    }

    public function getCounts()
    {
        $query = Proposal::query();
        if (auth()->user()->isVendedor()) {
            $query->where('user_id', auth()->id());
        }

        return [
            'all'       => (clone $query)->count(),
            'analise'   => (clone $query)->where('status', 'analise')->count(),
            'aprovada'  => (clone $query)->where('status', 'aprovada')->count(),
            'reprovada' => (clone $query)->where('status', 'reprovada')->count(),
        ];
    }

    public function render()
    {
        return view('livewire.admin.proposal-viewer', [
            'counts' => $this->getCounts(),
        ]);
    }
}
