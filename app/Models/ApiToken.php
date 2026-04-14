<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    use Auditable, BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'token', 'default_pipeline_id', 'default_stage_id', 'is_active', 'last_used_at',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function defaultPipeline(): BelongsTo
    {
        return $this->belongsTo(CrmPipeline::class, 'default_pipeline_id');
    }

    public function defaultStage(): BelongsTo
    {
        return $this->belongsTo(CrmStage::class, 'default_stage_id');
    }

    /** Gera um token legível e único. Retorna o valor em texto puro (só exibido uma vez). */
    public static function generate(string $name, ?int $pipelineId = null, ?int $stageId = null): array
    {
        $plain = 'crm_' . Str::random(40);

        $model = self::create([
            'name'                => $name,
            'token'               => hash('sha256', $plain),
            'default_pipeline_id' => $pipelineId,
            'default_stage_id'    => $stageId,
            'is_active'           => true,
        ]);

        return ['model' => $model, 'plain' => $plain];
    }

    public static function findByPlain(string $plain): ?self
    {
        // Bypass do scope de tenant porque essa busca acontece no middleware
        // de API externa, antes de qualquer empresa ter sido resolvida.
        // O caller é responsável por chamar app(CurrentCompany::class)->set($token->company_id)
        // depois de validar o token.
        return self::withoutCompanyScope()
            ->where('token', hash('sha256', $plain))
            ->where('is_active', true)
            ->first();
    }

    public function getMaskedTokenAttribute(): string
    {
        // Exibe apenas os primeiros 8 chars do hash para identificação visual
        return 'crm_' . substr($this->token, 0, 8) . '••••••••••••••••';
    }
}
