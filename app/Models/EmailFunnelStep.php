<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailFunnelStep extends Model
{
    protected $fillable = ['funnel_id', 'sort_order', 'type', 'config'];

    protected $casts = ['config' => 'array'];

    public function funnel()
    {
        return $this->belongsTo(EmailFunnel::class, 'funnel_id');
    }

    public function logs()
    {
        return $this->hasMany(EmailFunnelLog::class, 'step_id');
    }
}
