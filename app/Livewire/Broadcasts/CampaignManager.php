<?php

namespace App\Livewire\Broadcasts;

use App\Jobs\SendBroadcastEmail;
use App\Jobs\SendBroadcastMessage;
use App\Models\BroadcastCampaign;
use App\Models\BroadcastCampaignRecipient;
use App\Models\BroadcastCampaignRun;
use App\Models\BroadcastContact;
use App\Models\MetaMessageTemplate;
use App\Services\WhatsAppProvider;
use Livewire\Component;
use Livewire\WithPagination;

class CampaignManager extends Component
{
    use WithPagination, \Livewire\WithFileUploads;

    // Form
    public bool   $showForm         = false;
    public ?int   $editingId        = null;
    public string $channel          = 'whatsapp';
    public string $name             = '';
    public string $message          = '';
    public string $subject          = '';
    public string $htmlContent      = '';
    public int    $interval_seconds = 10;
    public        $campaignImage     = null;
    public        $emailLogo        = null;
    public        $emailImage       = null;
    public string $emailColor       = '#2563eb';
    public ?string $existingLogoUrl  = null;
    public ?string $existingImageUrl = null;
    public string $filterTag            = '';
    public string $recipientMode        = 'all';
    public string $meta_template_name   = '';
    public string $scheduled_at         = '';

    // Detail
    public ?int $viewingCampaignId = null;

    public function openCreate(): void
    {
        $this->reset('editingId', 'channel', 'name', 'message', 'meta_template_name', 'subject', 'htmlContent', 'campaignImage', 'emailLogo', 'emailImage', 'emailColor', 'interval_seconds', 'filterTag', 'recipientMode', 'scheduled_at', 'existingLogoUrl', 'existingImageUrl');
        $this->emailColor = '#2563eb';
        $this->channel          = 'whatsapp';
        $this->interval_seconds = 10;
        $this->recipientMode    = 'all';
        $this->showForm         = true;
    }

    public function save(): void
    {
        $rules = [
            'name'             => 'required|string|max:200',
            'channel'          => 'required|in:whatsapp,email',
            'interval_seconds' => 'required|integer|min:1|max:120',
        ];

        if ($this->channel === 'whatsapp') {
            $rules['message'] = 'required|string|max:4000';
            $rules['campaignImage'] = 'nullable|image|max:5120';
        } else {
            $rules['subject'] = 'required|string|max:200';
            $rules['message'] = 'required|string|max:4000';
            $rules['emailLogo'] = 'nullable|image|max:2048';
            $rules['emailImage'] = 'nullable|image|max:5120';
        }

        $this->validate($rules);

        $recipientCount = $this->getRecipientsQuery()->count();
        if ($recipientCount === 0) {
            $this->dispatch('toast', type: 'error', message: 'Nenhum lead encontrado para os filtros selecionados.');
            return;
        }

        $imagePath = null;
        if ($this->channel === 'whatsapp' && $this->campaignImage) {
            $imagePath = \App\Services\MediaStorage::store($this->campaignImage, 'broadcasts');
        }
        $emailImagePath = null;

        $htmlContent = null;
        if ($this->channel === 'email') {
            // Upload logo e imagem (novo upload ou manter existente)
            $logoUrl = $this->existingLogoUrl ?? '';
            if ($this->emailLogo) {
                $logoPath = \App\Services\MediaStorage::store($this->emailLogo, 'broadcasts/logos');
                $logoUrl = \App\Services\MediaStorage::url($logoPath);
                if (!str_starts_with($logoUrl, 'http')) $logoUrl = url($logoUrl);
            }
            $imgUrl = $this->existingImageUrl ?? '';
            if ($this->emailImage) {
                $imgPath = \App\Services\MediaStorage::store($this->emailImage, 'broadcasts/images');
                $imgUrl = \App\Services\MediaStorage::url($imgPath);
                if (!str_starts_with($imgUrl, 'http')) $imgUrl = url($imgUrl);
                $emailImagePath = $imgPath;
            }

            $color = $this->emailColor ?: '#2563eb';
            $body = nl2br(e($this->message));

            $htmlContent = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;">';
            // Header
            $htmlContent .= '<div style="background:' . $color . ';padding:25px;text-align:center;">';
            if ($logoUrl) {
                $htmlContent .= '<img src="' . $logoUrl . '" alt="Logo" style="max-height:60px;max-width:200px;margin-bottom:10px;"/>';
            }
            $htmlContent .= '<h1 style="color:#ffffff;margin:0;font-size:20px;">' . e($this->subject) . '</h1>';
            $htmlContent .= '</div>';
            // Image
            if ($imgUrl) {
                $htmlContent .= '<div style="text-align:center;"><img src="' . $imgUrl . '" alt="Imagem" style="width:100%;max-width:600px;display:block;"/></div>';
            }
            // Body
            $htmlContent .= '<div style="padding:30px;"><p style="font-size:14px;color:#333;line-height:1.6;">' . $body . '</p></div>';
            // Footer
            // Footer de unsubscribe é adicionado no SendBroadcastEmail
            $htmlContent .= '</div>';
        }

        $isScheduled = $this->scheduled_at && \Carbon\Carbon::parse($this->scheduled_at)->isFuture();

        $campaignData = [
            'name'                => $this->name,
            'channel'             => $this->channel,
            'message'             => $this->message ?: null,
            'meta_template_name'  => ($this->channel === 'whatsapp' && $this->meta_template_name) ? $this->meta_template_name : null,
            'subject'          => $this->channel === 'email' ? $this->subject : null,
            'status'           => $isScheduled ? 'scheduled' : 'draft',
            'interval_seconds' => $this->interval_seconds,
            'scheduled_at'     => $this->scheduled_at ?: null,
            'total_recipients' => $recipientCount,
        ];

        if ($htmlContent) $campaignData['html_content'] = $htmlContent;
        if ($imagePath ?? $emailImagePath) $campaignData['image_path'] = $imagePath ?? $emailImagePath;

        if ($this->editingId) {
            $campaign = BroadcastCampaign::findOrFail($this->editingId);
            $campaign->update($campaignData);
        } else {
            $campaignData['created_by'] = auth()->id();
            $campaign = BroadcastCampaign::create($campaignData);
        }

        // Se agendada, já cria o run e agenda o job
        if ($isScheduled) {
            $recipients = $this->getRecipientsQuery()->get();
            $run = BroadcastCampaignRun::create([
                'campaign_id'      => $campaign->id,
                'status'           => 'scheduled',
                'total_recipients' => $recipients->count(),
                'sent_count'       => 0,
                'failed_count'     => 0,
                'created_by'       => auth()->id(),
            ]);

            foreach ($recipients as $contact) {
                BroadcastCampaignRecipient::create([
                    'campaign_id'          => $campaign->id,
                    'run_id'               => $run->id,
                    'broadcast_contact_id' => $contact->id,
                    'phone'                => $contact->phone,
                    'status'               => 'pending',
                ]);
            }

            $delaySeconds = max(0, \Carbon\Carbon::parse($this->scheduled_at)->diffInSeconds(now()));
            if ($campaign->channel === 'email') {
                SendBroadcastEmail::dispatch($run)->delay($delaySeconds);
            } else {
                SendBroadcastMessage::dispatch($run)->delay($delaySeconds);
            }
        }

        $this->showForm = false;
        $msg = $isScheduled
            ? "Campanha agendada para " . \Carbon\Carbon::parse($this->scheduled_at)->format('d/m/Y H:i') . " ({$recipientCount} leads)."
            : "Campanha {$this->channel} criada com {$recipientCount} destinatários.";
        $this->dispatch('toast', type: 'success', message: $msg);
    }

    public function pauseCampaign(int $id): void
    {
        $campaign = BroadcastCampaign::findOrFail($id);
        $campaign->update(['status' => 'paused']);

        // Remove jobs pendentes da fila para esta campanha
        $runIds = BroadcastCampaignRun::where('campaign_id', $id)->pluck('id');
        if ($runIds->isNotEmpty()) {
            BroadcastCampaignRun::whereIn('id', $runIds)->update(['status' => 'paused']);

            // Remove jobs da fila que correspondem a esta campanha
            \DB::table('jobs')->where(function ($q) use ($runIds) {
                foreach ($runIds as $runId) {
                    $q->orWhere('payload', 'like', "%\"run\":{\"id\":{$runId}%")
                      ->orWhere('payload', 'like', "%BroadcastCampaignRun\",\"id\":{$runId}%");
                }
            })->delete();
        }

        $this->dispatch('toast', type: 'success', message: 'Campanha pausada.');
    }

    public function editCampaign(int $id): void
    {
        $campaign = BroadcastCampaign::findOrFail($id);
        $this->editingId          = $id;
        $this->channel            = $campaign->channel;
        $this->name               = $campaign->name;
        $this->message            = $campaign->message ?? '';
        $this->subject            = $campaign->subject ?? '';
        $this->meta_template_name = $campaign->meta_template_name ?? '';
        $this->scheduled_at       = $campaign->scheduled_at ? \Carbon\Carbon::parse($campaign->scheduled_at)->format('Y-m-d\TH:i') : '';
        $this->interval_seconds   = $campaign->interval_seconds ?? 10;
        $this->recipientMode      = 'all';
        $this->filterTag          = '';
        $this->campaignImage      = null;
        $this->emailLogo          = null;
        $this->emailImage         = null;
        $this->htmlContent        = $campaign->html_content ?? '';

        // Extrai cor do header, logo e imagem do HTML
        $this->emailColor       = '#2563eb';
        $this->existingLogoUrl  = null;
        $this->existingImageUrl = null;

        if ($campaign->html_content) {
            // A cor do header é o segundo background (o primeiro é #ffffff do container)
            if (preg_match_all('/background:(#[0-9a-fA-F]{3,6})/', $campaign->html_content, $m) && count($m[1]) >= 2) {
                $this->emailColor = $m[1][1];
            } elseif (!empty($m[1][0]) && $m[1][0] !== '#ffffff') {
                $this->emailColor = $m[1][0];
            }
            preg_match_all('/src="([^"]+)"/', $campaign->html_content, $imgs);
            foreach ($imgs[1] ?? [] as $url) {
                if (str_contains($url, 'logos/')) {
                    $this->existingLogoUrl = $url;
                } elseif (str_contains($url, 'images/') || str_contains($url, 'broadcasts/')) {
                    $this->existingImageUrl = $url;
                }
            }
        }

        // Imagem WhatsApp
        if ($campaign->image_path && $campaign->channel === 'whatsapp') {
            $this->existingImageUrl = \App\Services\MediaStorage::url($campaign->image_path);
        }

        $this->showForm = true;
    }

    public function send(int $campaignId): void
    {
        $campaign = BroadcastCampaign::findOrFail($campaignId);

        // Usa o channel da campanha para filtrar corretamente
        $this->channel = $campaign->channel;
        $recipients = $this->getRecipientsQuery()->get();
        $run = BroadcastCampaignRun::create([
            'campaign_id'      => $campaign->id,
            'status'           => 'sending',
            'total_recipients' => $recipients->count(),
            'sent_count'       => 0,
            'failed_count'     => 0,
            'started_at'       => now(),
            'created_by'       => auth()->id(),
        ]);

        foreach ($recipients as $contact) {
            BroadcastCampaignRecipient::create([
                'campaign_id'          => $campaign->id,
                'run_id'               => $run->id,
                'broadcast_contact_id' => $contact->id,
                'phone'                => $contact->phone,
                'status'               => 'pending',
            ]);
        }

        // Agendamento ou disparo imediato
        $isScheduled = $campaign->scheduled_at && \Carbon\Carbon::parse($campaign->scheduled_at)->isFuture();

        if ($isScheduled) {
            $delaySeconds = max(1, \Carbon\Carbon::parse($campaign->scheduled_at)->diffInSeconds(now()));
            $campaign->update(['status' => 'scheduled']);

            if ($campaign->channel === 'email') {
                SendBroadcastEmail::dispatch($run)->delay($delaySeconds);
            } else {
                SendBroadcastMessage::dispatch($run)->delay($delaySeconds);
            }
        } else {
            $campaign->update(['status' => 'sending', 'started_at' => now()]);

            if ($campaign->channel === 'email') {
                SendBroadcastEmail::dispatch($run);
            } else {
                SendBroadcastMessage::dispatch($run);
            }
        }

        if ($isScheduled) {
            $this->dispatch('toast', type: 'success', message: "Campanha agendada para " . \Carbon\Carbon::parse($campaign->scheduled_at)->format('d/m/Y H:i') . " ({$recipients->count()} leads).");
        } else {
            $this->dispatch('toast', type: 'success', message: "Disparando {$campaign->channel} para {$recipients->count()} leads...");
        }
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
        BroadcastCampaignRecipient::where('campaign_id', $id)->delete();
        BroadcastCampaignRun::where('campaign_id', $id)->delete();
        $campaign->delete();
        $this->dispatch('toast', type: 'success', message: 'Campanha removida.');
    }

    private function getRecipientsQuery()
    {
        $query = BroadcastContact::where('is_active', true)
            ->where(fn($q) => $q->whereNull('tags')->orWhere('tags', 'not like', '%unsub:%'));

        if ($this->recipientMode === 'tag' && $this->filterTag) {
            $query->whereJsonContains('tags', $this->filterTag);
        }
        if ($this->channel === 'email') {
            $query->whereNotNull('email')->where('email', '!=', '');
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
            ? BroadcastCampaignRun::where('campaign_id', $this->viewingCampaignId)->orderByDesc('created_at')->get()
            : collect();
        $viewingCampaign = $this->viewingCampaignId ? BroadcastCampaign::find($this->viewingCampaignId) : null;
        $allTags = BroadcastContact::whereNotNull('tags')->pluck('tags')->flatten()->unique()->sort()->values();
        $activeLeadCount = BroadcastContact::where('is_active', true)->count();
        $emailLeadCount = BroadcastContact::where('is_active', true)->whereNotNull('email')->where('email', '!=', '')->count();
        $company = app(\App\Services\CurrentCompany::class)->model();
        $sendgridConfigured = !empty($company?->sendgrid_api_key) || !empty(\App\Models\GlobalSetting::get('sendgrid_api_key'));
        $isMeta        = WhatsAppProvider::isMeta();
        $metaTemplates = $isMeta ? MetaMessageTemplate::approved()->orderBy('name')->get() : collect();

        return view('livewire.broadcasts.campaign-manager', compact(
            'campaigns', 'runs', 'viewingCampaign', 'allTags', 'activeLeadCount', 'emailLeadCount', 'sendgridConfigured', 'isMeta', 'metaTemplates'
        ));
    }
}
