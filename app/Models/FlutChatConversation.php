<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class FlutChatConversation extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'widget_id', 'visitor_id', 'visitor_name',
        'status', 'assigned_to', 'last_message_at',
    ];

    protected $casts = ['last_message_at' => 'datetime'];

    public function widget()
    {
        return $this->belongsTo(FlutChatWidget::class, 'widget_id');
    }

    public function messages()
    {
        return $this->hasMany(FlutChatMessage::class, 'conversation_id');
    }

    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function latestMessage()
    {
        return $this->hasOne(FlutChatMessage::class, 'conversation_id')->latest();
    }
}
