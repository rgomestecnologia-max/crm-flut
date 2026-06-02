<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailFunnelLog extends Model
{
    protected $fillable = ['subscriber_id', 'step_id', 'action', 'message_id'];

    public function subscriber()
    {
        return $this->belongsTo(EmailFunnelSubscriber::class, 'subscriber_id');
    }

    public function step()
    {
        return $this->belongsTo(EmailFunnelStep::class, 'step_id');
    }
}
