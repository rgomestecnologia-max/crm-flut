<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmPipeline extends Model
{
    use Auditable, BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'description', 'color', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function stages(): HasMany
    {
        return $this->hasMany(CrmStage::class, 'pipeline_id')->orderBy('sort_order');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(CrmCard::class, 'pipeline_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
