<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use Auditable, BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'description', 'color', 'icon', 'is_active', 'hide_from_main_queue', 'sort_order', 'evolution_api_config_id'];

    protected $casts = ['is_active' => 'boolean', 'hide_from_main_queue' => 'boolean'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function quickReplies(): HasMany
    {
        return $this->hasMany(QuickReply::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function evolutionConfig(): BelongsTo
    {
        return $this->belongsTo(EvolutionApiConfig::class, 'evolution_api_config_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
