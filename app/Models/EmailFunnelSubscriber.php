<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class EmailFunnelSubscriber extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'funnel_id', 'contact_id', 'current_step_id',
        'status', 'step_entered_at', 'entered_at', 'completed_at',
    ];

    protected $casts = [
        'step_entered_at' => 'datetime',
        'entered_at'      => 'datetime',
        'completed_at'    => 'datetime',
    ];

    public function funnel()
    {
        return $this->belongsTo(EmailFunnel::class, 'funnel_id');
    }

    public function contact()
    {
        return $this->belongsTo(BroadcastContact::class, 'contact_id');
    }

    public function currentStep()
    {
        return $this->belongsTo(EmailFunnelStep::class, 'current_step_id');
    }

    public function logs()
    {
        return $this->hasMany(EmailFunnelLog::class, 'subscriber_id');
    }
}
