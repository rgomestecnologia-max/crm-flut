<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class CrmCardTask extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'card_id', 'user_id', 'title', 'description',
        'due_date', 'due_time', 'is_completed', 'completed_at', 'priority',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function card()
    {
        return $this->belongsTo(CrmCard::class, 'card_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeDueOn($query, $date)
    {
        return $query->whereDate('due_date', $date);
    }

    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }
}
