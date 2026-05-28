<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class FlutChatLead extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'widget_id', 'data', 'action_taken',
        'ip', 'user_agent', 'page_url',
    ];

    protected $casts = ['data' => 'array'];

    public function widget()
    {
        return $this->belongsTo(FlutChatWidget::class, 'widget_id');
    }
}
