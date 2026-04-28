<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastCampaignRecipient extends Model
{
    protected $fillable = [
        'campaign_id', 'run_id', 'broadcast_contact_id', 'phone', 'status', 'sent_at', 'error',
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(BroadcastCampaignRun::class, 'run_id');
    }

    public function broadcastContact(): BelongsTo
    {
        return $this->belongsTo(BroadcastContact::class, 'broadcast_contact_id');
    }
}
