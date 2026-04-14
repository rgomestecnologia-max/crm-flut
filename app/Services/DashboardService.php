<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\CrmCard;
use App\Models\CrmCardFieldValue;
use App\Models\CrmCustomField;
use App\Models\CrmPipeline;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Stats de conversas: minhas ativas, fila, resolvidas hoje, total.
     */
    public function conversationStats(User $user): array
    {
        $base = Conversation::forUser($user);

        return [
            'mine'           => (clone $base)->where('assigned_to', $user->id)->where('status', 'open')->count(),
            'queue'          => (clone $base)->whereNull('assigned_to')->whereIn('status', ['open', 'pending', 'transferred'])->count(),
            'resolved_today' => (clone $base)->where('status', 'resolved')->whereDate('updated_at', today())->count(),
            'total_open'     => (clone $base)->whereIn('status', ['open', 'pending', 'transferred'])->count(),
        ];
    }

    /**
     * Novos contatos criados hoje.
     */
    public function newContactsToday(): int
    {
        return Contact::whereDate('created_at', today())->count();
    }

    /**
     * Tempo médio de primeira resposta (em minutos).
     * Calcula: para conversas de hoje com pelo menos 1 msg do contato e 1 do agente,
     * a diferença entre a primeira msg do contato e a primeira resposta do agente.
     */
    public function avgResponseTime(User $user): ?float
    {
        $conversationIds = Conversation::forUser($user)
            ->whereDate('created_at', today())
            ->pluck('id');

        if ($conversationIds->isEmpty()) return null;

        // Subquery: pra cada conversa, pega a primeira msg do contato e a primeira do agente
        $result = DB::select("
            SELECT AVG(response_minutes) as avg_minutes FROM (
                SELECT
                    c.conversation_id,
                    TIMESTAMPDIFF(MINUTE, c.first_contact, a.first_agent) as response_minutes
                FROM
                    (SELECT conversation_id, MIN(created_at) as first_contact
                     FROM messages WHERE sender_type = 'contact' AND conversation_id IN (" . $conversationIds->implode(',') . ")
                     GROUP BY conversation_id) c
                JOIN
                    (SELECT conversation_id, MIN(created_at) as first_agent
                     FROM messages WHERE sender_type = 'agent' AND conversation_id IN (" . $conversationIds->implode(',') . ")
                     GROUP BY conversation_id) a
                ON c.conversation_id = a.conversation_id
                WHERE a.first_agent > c.first_contact
            ) sub
        ");

        $avg = $result[0]->avg_minutes ?? null;
        return $avg !== null ? round((float) $avg, 1) : null;
    }

    /**
     * Conversas abertas agrupadas por departamento (com nome e cor).
     */
    public function conversationsByDepartment(): Collection
    {
        return Conversation::whereIn('status', ['open', 'pending', 'transferred'])
            ->join('departments', 'conversations.department_id', '=', 'departments.id')
            ->selectRaw('departments.name, departments.color, count(*) as total')
            ->groupBy('departments.id', 'departments.name', 'departments.color')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Performance dos agentes: conversas ativas, resolvidas hoje, status.
     */
    public function agentPerformance(): Collection
    {
        $companyId = app(CurrentCompany::class)->id();

        return User::where('role', '!=', 'admin')
            ->where('is_active', true)
            ->where('company_id', $companyId)
            ->withCount([
                'assignedConversations as active_count' => fn($q) => $q->where('status', 'open'),
                'assignedConversations as resolved_today' => fn($q) => $q->where('status', 'resolved')->whereDate('updated_at', today()),
            ])
            ->orderByDesc('active_count')
            ->get(['id', 'name', 'avatar', 'status', 'role', 'department_id']);
    }

    /**
     * Últimas 10 ações no log de auditoria.
     */
    public function recentActivity(): Collection
    {
        return AuditLog::with('user')
            ->latest('created_at')
            ->limit(10)
            ->get();
    }

    /**
     * Resumo do pipeline CRM: cards por etapa com valor total.
     */
    public function pipelineSummary(): Collection
    {
        $pipelines = CrmPipeline::active()->with(['stages' => fn($q) => $q->orderBy('sort_order')])->get();

        // Busca a key do campo valor_da_reserva (se existir)
        $valorField = CrmCustomField::where('key', 'valor_da_reserva')->first();

        foreach ($pipelines as $pipeline) {
            foreach ($pipeline->stages as $stage) {
                $stage->cards_count = CrmCard::where('stage_id', $stage->id)->count();

                if ($valorField) {
                    $stage->total_value = CrmCardFieldValue::where('field_id', $valorField->id)
                        ->whereIn('card_id', CrmCard::where('stage_id', $stage->id)->pluck('id'))
                        ->sum(DB::raw('CAST(value AS DECIMAL(10,2))'));
                } else {
                    $stage->total_value = 0;
                }
            }
        }

        return $pipelines;
    }
}
