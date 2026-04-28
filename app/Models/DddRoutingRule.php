<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DddRoutingRule extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'ddd', 'agent_id', 'department_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
