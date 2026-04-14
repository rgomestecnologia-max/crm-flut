<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use BelongsToCompany;

    public $timestamps = false;

    protected $fillable = [
        'company_id', 'user_id', 'user_name', 'action',
        'auditable_type', 'auditable_id', 'auditable_label',
        'old_values', 'new_values', 'ip_address', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Nome curto do model auditado (ex: "Contato", "Conversa", "Pipeline").
     */
    public function getModelLabelAttribute(): string
    {
        return match ($this->auditable_type) {
            'App\\Models\\Contact'           => 'Contato',
            'App\\Models\\Conversation'      => 'Conversa',
            'App\\Models\\Message'           => 'Mensagem',
            'App\\Models\\Department'        => 'Departamento',
            'App\\Models\\User'              => 'Usuário',
            'App\\Models\\CrmPipeline'       => 'Pipeline',
            'App\\Models\\CrmStage'          => 'Etapa',
            'App\\Models\\CrmCard'           => 'Card CRM',
            'App\\Models\\CrmCardActivity'   => 'Atividade CRM',
            'App\\Models\\CrmCustomField'    => 'Campo personalizado',
            'App\\Models\\QuickReply'        => 'Resposta rápida',
            'App\\Models\\Tag'               => 'Tag',
            'App\\Models\\Automation'        => 'Automação',
            'App\\Models\\AiBotConfig'       => 'Config IA',
            'App\\Models\\AiBotProduct'      => 'Produto IA',
            'App\\Models\\ChatbotMenuConfig' => 'Config Chatbot',
            'App\\Models\\EvolutionApiConfig'=> 'Config Evolution',
            'App\\Models\\TransferLog'       => 'Transferência',
            'App\\Models\\ApiToken'          => 'Token API',
            default => class_basename($this->auditable_type),
        };
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created' => 'Criou',
            'updated' => 'Alterou',
            'deleted' => 'Excluiu',
            default   => $this->action,
        };
    }

    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'created' => '#22c55e',
            'updated' => '#3b82f6',
            'deleted' => '#ef4444',
            default   => '#6b7280',
        };
    }
}
