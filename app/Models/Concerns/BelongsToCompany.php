<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;
use App\Services\CurrentCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aplica multi-tenancy automático em um model Eloquent.
 *
 * Ao usar este trait:
 *  - O model passa a filtrar TODA query pelo `CurrentCompany::id()` (global scope).
 *  - Ao criar um registro, o `company_id` é preenchido automaticamente.
 *  - Para queries cross-company (admin), use `Model::withoutCompanyScope()` ou
 *    `Model::query()->withoutGlobalScope(CompanyScope::class)`.
 *
 * Não esqueça de:
 *  - Adicionar `company_id` no `$fillable` do model.
 *  - Adicionar a coluna `company_id` na tabela com FK.
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model) {
            if (empty($model->company_id)) {
                $companyId = app(CurrentCompany::class)->id();
                if ($companyId) {
                    $model->company_id = $companyId;
                }
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Atalho para queries que precisam ignorar o filtro de tenant.
     * Use com cuidado — geralmente só admin/jobs internos devem usar.
     */
    public function scopeWithoutCompanyScope($query)
    {
        return $query->withoutGlobalScope(CompanyScope::class);
    }
}
