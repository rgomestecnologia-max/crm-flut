<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Proposal extends Model
{
    protected $fillable = [
        'client_name', 'modules', 'config', 'details',
        'total_monthly', 'total_setup', 'user_id', 'token',
    ];

    protected $casts = [
        'modules' => 'array',
        'config'  => 'array',
        'details' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Proposal $proposal) {
            if (!$proposal->token) {
                $proposal->token = Str::random(32);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
