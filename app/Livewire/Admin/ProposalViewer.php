<?php

namespace App\Livewire\Admin;

use App\Models\Proposal;
use Livewire\Component;

class ProposalViewer extends Component
{
    public $proposals = [];
    public $expandedId = null;

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
