<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmStage extends Model
{
    use Auditable, BelongsToCompany;

    protected $fillable = ['company_id', 'pipeline_id', 'name', 'color', 'sort_order'];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(CrmPipeline::class, 'pipeline_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(CrmCard::class, 'stage_id')->orderBy('sort_order');
    }
}
