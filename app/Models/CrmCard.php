<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmCard extends Model
{
    use Auditable, BelongsToCompany;

    protected $fillable = [
        'company_id', 'pipeline_id', 'stage_id', 'contact_id', 'assigned_to',
        'title', 'description', 'priority', 'sort_order',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(CrmPipeline::class, 'pipeline_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(CrmStage::class, 'stage_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmCardActivity::class, 'card_id')->orderBy('created_at', 'desc');
    }

    public function fieldValues(): HasMany
    {
        return $this->hasMany(CrmCardFieldValue::class, 'card_id');
    }

    /** Retorna o valor de um campo personalizado pelo key do campo */
    public function getFieldValue(string $key): ?string
    {
        return $this->fieldValues
            ->first(fn($v) => $v->field?->key === $key)
            ?->value;
    }

    public function getPriorityLabelAttribute(): ?string
    {
        return match($this->priority) {
            'critico' => 'Crítico',
            'alto'    => 'Alto',
            'medio'   => 'Médio',
            'baixo'   => 'Baixo',
            default   => null,
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'critico' => 'bg-red-500/20 text-red-400 border-red-500/30',
            'alto'    => 'bg-orange-500/20 text-orange-400 border-orange-500/30',
            'medio'   => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
            'baixo'   => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
            default   => '',
        };
    }
}
