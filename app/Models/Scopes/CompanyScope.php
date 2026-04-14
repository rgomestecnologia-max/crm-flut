<?php

namespace App\Models\Scopes;

use App\Services\CurrentCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope que filtra qualquer model com `company_id` pela empresa ativa.
 *
 * Comportamento:
 *  - Se há empresa ativa: filtra `where company_id = X`.
 *  - Se NÃO há empresa ativa: força `where 0 = 1` para impedir vazamento de dados
 *    (ex: rota chamada antes do middleware setar a empresa, ou fora de uma request).
 *    Para queries de sistema/admin que precisem cruzar empresas, use `withoutCompanyScope()`.
 */
class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $companyId = app(CurrentCompany::class)->id();

        if ($companyId === null) {
            // Defesa em profundidade: sem empresa = sem dados.
            $builder->whereRaw('1 = 0');
            return;
        }

        $builder->where($model->getTable() . '.company_id', $companyId);
    }
}
