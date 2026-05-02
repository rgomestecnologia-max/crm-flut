<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'type', 'title', 'message', 'data', 'is_read',
    ];

    protected $casts = [
        'data'    => 'array',
        'is_read' => 'boolean',
    ];

    /**
     * Scope: admin vê todas, outros veem da própria empresa.
     */
    public function scopeForUser($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        $companyId = app(\App\Services\CurrentCompany::class)->id();
        return $query->where(function ($q) use ($companyId, $user) {
            $q->where('company_id', $companyId)
              ->orWhereNull('company_id');
        });
    }
}
