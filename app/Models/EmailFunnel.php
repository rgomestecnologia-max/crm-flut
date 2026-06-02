<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class EmailFunnel extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'status', 'trigger_type', 'trigger_value', 'created_by',
    ];

    public function steps()
    {
        return $this->hasMany(EmailFunnelStep::class, 'funnel_id')->orderBy('sort_order');
    }

    public function subscribers()
    {
        return $this->hasMany(EmailFunnelSubscriber::class, 'funnel_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
