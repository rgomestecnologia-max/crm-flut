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

    public function mount()
    {
        $this->loadProposals();
    }

    public function loadProposals()
    {
        $query = Proposal::with('user')->orderByDesc('created_at');

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
        $pct = $this->discountPercent;

        if ($pct <= 0 || $pct > 90) {
            $this->dispatch('toast', type: 'error', message: 'Desconto deve ser entre 1% e 90%');
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
        $oldTotal = $origMonthly + $origSetup;
        if ($oldTotal > 0) {
            foreach ($details as $key => $value) {
                // Proporção: se o original era X% do total, o desconto mantém a proporção
                $details[$key] = round($value * $factor, 2);
            }
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

    public function delete($id)
    {
        Proposal::findOrFail($id)->delete();
        $this->loadProposals();
    }

    public function getCounts()
    {
        return [
            'all'       => Proposal::count(),
            'analise'   => Proposal::where('status', 'analise')->count(),
            'aprovada'  => Proposal::where('status', 'aprovada')->count(),
            'reprovada' => Proposal::where('status', 'reprovada')->count(),
        ];
    }

    public function render()
    {
        return view('livewire.admin.proposal-viewer', [
            'counts' => $this->getCounts(),
        ]);
    }
}
