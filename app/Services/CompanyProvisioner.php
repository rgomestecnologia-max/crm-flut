<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CrmPipeline;
use App\Models\CrmStage;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

/**
 * Cria empresas novas e popula com o template mínimo (1 departamento + 1 pipeline).
 *
 * Toda criação roda dentro de uma transação E dentro do contexto da nova empresa,
 * pra que o trait BelongsToCompany preencha automaticamente os company_ids dos
 * departments/pipelines/stages criados.
 */
class CompanyProvisioner
{
    public function __construct(protected CurrentCompany $currentCompany) {}

    /**
     * Cria uma empresa nova já populada com o template mínimo.
     */
    public function create(array $attributes): Company
    {
        return DB::transaction(function () use ($attributes) {
            $company = Company::create([
                'name'      => $attributes['name'],
                'slug'      => $attributes['slug'] ?? null,  // auto via Company::booted
                'color'     => $attributes['color']     ?? '#b2ff00',
                'logo'      => $attributes['logo']      ?? null,
                'is_active' => $attributes['is_active'] ?? true,
            ]);

            $this->seedMinimalTemplate($company);

            return $company;
        });
    }

    /**
     * Cria 1 departamento "Geral" + 1 pipeline "Vendas" com 3 etapas
     * para destravar a primeira tela quando o usuário entra na empresa nova.
     */
    protected function seedMinimalTemplate(Company $company): void
    {
        $this->currentCompany->asTenant($company->id, function () {
            // Departamento padrão
            Department::create([
                'name'        => 'Geral',
                'description' => 'Departamento padrão criado automaticamente.',
                'color'       => '#b2ff00',
                'is_active'   => true,
            ]);

            // Pipeline + etapas
            $pipeline = CrmPipeline::create([
                'name'        => 'Vendas',
                'description' => 'Funil padrão criado automaticamente.',
                'color'       => '#b2ff00',
                'is_active'   => true,
                'sort_order'  => 1,
            ]);

            $stages = [
                ['name' => 'Novo',            'color' => '#3b82f6', 'sort_order' => 1],
                ['name' => 'Em negociação',   'color' => '#f59e0b', 'sort_order' => 2],
                ['name' => 'Fechado',         'color' => '#22c55e', 'sort_order' => 3],
            ];
            foreach ($stages as $stage) {
                CrmStage::create(array_merge($stage, ['pipeline_id' => $pipeline->id]));
            }
        });
    }
}
