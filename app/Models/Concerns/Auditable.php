<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Services\CurrentCompany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Registra automaticamente created/updated/deleted na tabela audit_logs.
 *
 * Para usar: adicione `use Auditable;` no model.
 *
 * Campos sensíveis (senhas, tokens) são automaticamente ocultados via $hidden do model.
 * Para personalizar o label legível, defina `getAuditLabel(): string` no model.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::recordAudit($model, 'created', [], $model->getAttributes());
        });

        static::updated(function ($model) {
            $dirty    = $model->getDirty();
            $original = array_intersect_key($model->getOriginal(), $dirty);

            // Ignora se só mudou updated_at
            $meaningful = array_diff_key($dirty, ['updated_at' => true]);
            if (empty($meaningful)) return;

            static::recordAudit($model, 'updated', $original, $dirty);
        });

        static::deleted(function ($model) {
            static::recordAudit($model, 'deleted', $model->getOriginal(), []);
        });
    }

    protected static function recordAudit($model, string $action, array $old, array $new): void
    {
        $companyId = app(CurrentCompany::class)->id();
        if (!$companyId) return; // sem tenant = sistema/job sem contexto, ignora

        // Remove campos sensíveis definidos em $hidden do model
        $hidden = $model->getHidden();
        $old    = array_diff_key($old, array_flip($hidden));
        $new    = array_diff_key($new, array_flip($hidden));

        // Remove campos volumosos/binários que não agregam valor no log
        $exclude = ['qr_code', 'pairing_code', 'reactions', 'menu_prompt', 'system_prompt',
                     'welcome_template', 'department_routing_prompt', 'message_template',
                     'html_content', 'website_content', 'document_content', 'voice_tones',
                     'company_description', 'base64', 'content'];
        $old = array_diff_key($old, array_flip($exclude));
        $new = array_diff_key($new, array_flip($exclude));

        $user = Auth::user();

        try {
            AuditLog::withoutGlobalScopes()->create([
                'company_id'      => $companyId,
                'user_id'         => $user?->id,
                'user_name'       => $user?->name ?? 'Sistema',
                'action'          => $action,
                'auditable_type'  => get_class($model),
                'auditable_id'    => $model->getKey(),
                'auditable_label' => method_exists($model, 'getAuditLabel')
                    ? $model->getAuditLabel()
                    : ($model->name ?? $model->title ?? $model->phone ?? "#{$model->getKey()}"),
                'old_values'      => !empty($old) ? $old : null,
                'new_values'      => !empty($new) ? $new : null,
                'ip_address'      => Request::ip(),
                'created_at'      => now(),
            ]);
        } catch (\Throwable $e) {
            // Nunca quebra o fluxo principal por causa de auditoria.
            \Illuminate\Support\Facades\Log::warning('Audit log failed', ['error' => $e->getMessage()]);
        }
    }
}
