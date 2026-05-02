<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class BroadcastContact extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'phone', 'email', 'tags', 'is_active'];

    protected function casts(): array
    {
        return [
            'tags'      => 'array',
            'is_active' => 'boolean',
        ];
    }
}
