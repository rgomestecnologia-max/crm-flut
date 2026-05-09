<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proposal extends Model
{
    protected $fillable = [
        'client_name', 'modules', 'config', 'details',
        'total_monthly', 'total_setup', 'user_id',
    ];

    protected $casts = [
        'modules' => 'array',
        'config'  => 'array',
        'details' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
