<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Conversation extends Model
{
    use Auditable, BelongsToCompany;

    protected $fillable = [
        'company_id', 'contact_id', 'department_id', 'assigned_to',
        'status', 'protocol', 'last_message_at', 'menu_awaiting',
        'is_group', 'group_name', 'source_automation_id',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'is_group'        => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->protocol)) {
                $model->protocol = strtoupper(Str::random(2)) . date('ymd') . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest()->limit(1);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'conversation_tag');
    }

    public function sourceAutomation(): BelongsTo
    {
        return $this->belongsTo(Automation::class, 'source_automation_id');
    }

    public function transferLogs(): HasMany
    {
        return $this->hasMany(TransferLog::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }
        return $query->whereIn('department_id', $user->departmentIds());
    }

    public function unreadCount(int $userId): int
    {
        return $this->messages()
            ->where('sender_type', 'contact')
            ->where('is_read', false)
            ->count();
    }

    public function isOpen(): bool    { return $this->status === 'open'; }
    public function isPending(): bool { return $this->status === 'pending'; }
    public function isResolved(): bool{ return $this->status === 'resolved'; }
}
