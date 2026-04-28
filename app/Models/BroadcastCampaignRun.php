<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BroadcastCampaignRun extends Model
{
    protected $fillable = [
        'campaign_id', 'status', 'total_recipients', 'sent_count',
        'failed_count', 'scheduled_at', 'started_at', 'completed_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at'  => 'datetime',
            'started_at'    => 'datetime',
            'completed_at'  => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BroadcastCampaign::class, 'campaign_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(BroadcastCampaignRecipient::class, 'run_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
