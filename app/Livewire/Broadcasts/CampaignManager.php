<?php

namespace App\Livewire\Broadcasts;

use App\Jobs\SendBroadcastMessage;
use App\Models\BroadcastCampaign;
use App\Models\BroadcastCampaignRecipient;
use App\Models\BroadcastCampaignRun;
use App\Models\BroadcastContact;
use Livewire\Component;
use Livewire\WithPagination;

class CampaignManager extends Component
{
    use WithPagination;

    // Form
    public bool   $showForm         = false;
    public ?int   $editingId        = null;
    public string $name             = '';
    public string $message          = '';
    public int    $interval_seconds = 10;
    public string $filterTag        = '';
    public string $recipientMode    = 'all'; // all, tag

    // Detail
    public ?int $viewingCampaignId = null;

    public function openCreate(): void
    {
        $this->reset('editingId', 'name', 'message', 'interval_seconds', 'filterTag', 'recipientMode');
        $this->interval_seconds = 10;
        $this->recipientMode    = 'all';
        $this->showForm         = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'             => 'required|string|max:200',
            'message'          => 'required|string|max:4000',
            'interval_seconds' => 'required|integer|min:3|max:120',
        ]);

        // Count recipients
        $recipientCount = $this->getRecipientsQuery()->count();

        if ($recipientCount === 0) {
            $this->dispatch('toast', type: 'error', message: 'Nenhum lead encontrado para os filtros selecionados.');
            return;
        }

        $campaign = BroadcastCampaign::create([
            'name'             => $this->name,
            'message'          => $this->message,
            'status'           => 'draft',
            'interval_seconds' => $this->interval_seconds,
            'total_recipients' => $recipientCount,
            'created_by'       => auth()->id(),
        ]);

        $this->showForm = false;
        $this->dispatch('toast', type: 'success', message: "Campanha criada com {$recipientCount} destinatários.");
    }

    public function send(int $campaignId): void
    {
        $campaign = BroadcastCampaign::findOrFail($campaignId);

        // Create a new run
        $recipients = $this->getAllActiveContacts();
        $run = BroadcastCampaignRun::create([
            'campaign_id'      => $campaign->id,
            'status'           => 'sending',
            'total_recipients' => $recipients->count(),
            'sent_count'       => 0,
            'failed_count'     => 0,
            'started_at'       => now(),
            'created_by'       => auth()->id(),
        ]);

        // Create recipients
        foreach ($recipients as $contact) {
            BroadcastCampaignRecipient::create([
                'campaign_id'          => $campaign->id,
                'run_id'               => $run->id,
                'broadcast_contact_id' => $contact->id,
                'phone'                => $contact->phone,
                'status'               => 'pending',
            ]);
        }

        $campaign->update(['status' => 'sending', 'started_at' => now()]);

        // Dispatch job
        SendBroadcastMessage::dispatch($run);

        $this->dispatch('toast', type: 'success', message: "Disparando para {$recipients->count()} leads...");
    }

    public function viewRuns(int $campaignId): void
    {
        $this->viewingCampaignId = $campaignId;
    }

    public function closeRuns(): void
    {
        $this->viewingCampaignId = null;
    }

    public function deleteCampaign(int $id): void
    {
        $campaign = BroadcastCampaign::findOrFail($id);
        if ($campaign->status === 'sending') {
            $this->dispatch('toast', type: 'error', message: 'Não é possível excluir campanha em andamento.');
            return;
        }
        // Delete recipients and runs
        BroadcastCampaignRecipient::where('campaign_id', $id)->delete();
        BroadcastCampaignRun::where('campaign_id', $id)->delete();
        $campaign->delete();
        $this->dispatch('toast', type: 'success', message: 'Campanha removida.');
    }

    private function getRecipientsQuery()
    {
        $query = BroadcastContact::where('is_active', true);
        if ($this->recipientMode === 'tag' && $this->filterTag) {
            $query->whereJsonContains('tags', $this->filterTag);
        }
        return $query;
    }

    private function getAllActiveContacts()
    {
        return BroadcastContact::where('is_active', true)->get();
    }

    public function render()
    {
        $campaigns = BroadcastCampaign::orderByDesc('created_at')->paginate(15);

        $runs = $this->viewingCampaignId
            ? BroadcastCampaignRun::where('campaign_id', $this->viewingCampaignId)
                ->orderByDesc('created_at')->get()
            : collect();

        $viewingCampaign = $this->viewingCampaignId
            ? BroadcastCampaign::find($this->viewingCampaignId)
            : null;

        $allTags = BroadcastContact::whereNotNull('tags')
            ->pluck('tags')->flatten()->unique()->sort()->values();

        $activeLeadCount = BroadcastContact::where('is_active', true)->count();

        return view('livewire.broadcasts.campaign-manager', compact(
            'campaigns', 'runs', 'viewingCampaign', 'allTags', 'activeLeadCount'
        ));
    }
}
