<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class BroadcastContact extends Model
{
    use Auditable, BelongsToCompany;

    protected $fillable = [
        'company_id', 'type', 'name', 'company_name', 'document',
        'phone', 'email', 'address', 'city', 'state',
        'tags', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tags'      => 'array',
            'is_active' => 'boolean',
        ];
    }
}
