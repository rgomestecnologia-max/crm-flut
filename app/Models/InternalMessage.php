<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalMessage extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'sender_id', 'recipient_id', 'group_id',
        'content', 'type', 'media_url', 'media_filename', 'is_read',
    ];

    protected $casts = ['is_read' => 'boolean'];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(InternalGroup::class, 'group_id');
    }
}
