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

    public function mount()
    {
        $this->loadProposals();
    }

    public function loadProposals()
    {
        $this->proposals = Proposal::with('user')
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    public function toggle($id)
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
        $this->discountId = null;
    }

    public function openDiscount($id)
    {
        $this->discountId = $this->discountId === $id ? null : $id;
        $this->discountPercent = 0;
    }

    public function applyDiscount($id)
    {
        $proposal = Proposal::findOrFail($id);
        $pct = $this->discountPercent;
        $factor = 1 - ($pct / 100);

        $details = $proposal->details;
        foreach ($details as $key => $value) {
            $details[$key] = round($value * $factor, 2);
        }

        $proposal->update([
            'details'       => $details,
            'total_monthly' => round($proposal->total_monthly * $factor, 2),
            'total_setup'   => round($proposal->total_setup * $factor, 2),
        ]);

        $this->discountId = null;
        $this->discountPercent = 0;
        $this->loadProposals();

        $this->dispatch('toast', type: 'success', message: "Desconto de {$pct}% aplicado!");
    }

    public function delete($id)
    {
        Proposal::findOrFail($id)->delete();
        $this->loadProposals();
    }

    public function render()
    {
        return view('livewire.admin.proposal-viewer');
    }
}
