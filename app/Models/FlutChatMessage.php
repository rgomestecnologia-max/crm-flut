<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class FlutChatMessage extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'conversation_id', 'sender_type', 'sender_id', 'content',
        'media_url', 'media_type', 'media_filename',
    ];

    public function conversation()
    {
        return $this->belongsTo(FlutChatConversation::class, 'conversation_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
