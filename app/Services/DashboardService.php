<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BroadcastCampaign;
use App\Models\BroadcastCampaignRecipient;
use App\Models\BroadcastContact;
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
    public function conversationStats(User $user, ?string $from = null, ?string $to = null): array
    {
        $base = Conversation::forUser($user);
        if ($from) $base->where('created_at', '>=', $from);
        if ($to) $base->where('created_at', '<=', $to . ' 23:59:59');

        return [
            'mine'           => (clone $base)->where('assigned_to', $user->id)->where('status', 'open')->count(),
            'queue'          => (clone $base)->whereNull('waiting_human_reason')->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNull('assigned_to')->whereIn('status', ['open', 'pending', 'transferred']);
                })->orWhere(function ($q2) {
                    $q2->where('is_group', true)->whereIn('status', ['open', 'pending']);
                });
            })->count(),
            'resolved_today' => (clone $base)->where('status', 'resolved')
                ->when(!$from && !$to, fn($q) => $q->whereDate('updated_at', today()))
                ->when($from, fn($q) => $q->where('updated_at', '>=', $from))
                ->when($to, fn($q) => $q->where('updated_at', '<=', $to . ' 23:59:59'))
                ->count(),
            'total_open'     => (clone $base)->whereIn('status', ['open', 'pending', 'transferred'])->count(),
        ];
    }

    public function newContactsToday(?string $from = null, ?string $to = null): int
    {
        $q = Contact::query();
        if ($from) $q->where('created_at', '>=', $from);
        elseif (!$to) $q->whereDate('created_at', today());
        if ($to) $q->where('created_at', '<=', $to . ' 23:59:59');
        return $q->count();
    }

    public function avgResponseTime(User $user, ?string $from = null, ?string $to = null): ?float
    {
        $q = Conversation::forUser($user);
        if ($from) $q->where('created_at', '>=', $from);
        elseif (!$to) $q->whereDate('created_at', today());
        if ($to) $q->where('created_at', '<=', $to . ' 23:59:59');
        $conversationIds = $q->pluck('id');

        if ($conversationIds->isEmpty()) return null;

        $placeholders = $conversationIds->map(fn() => '?')->implode(',');
        $bindings = $conversationIds->values()->all();
        $result = DB::select("
            SELECT AVG(response_minutes) as avg_minutes FROM (
                SELECT
                    c.conversation_id,
                    TIMESTAMPDIFF(MINUTE, c.first_contact, a.first_agent) as response_minutes
                FROM
                    (SELECT conversation_id, MIN(created_at) as first_contact
                     FROM messages WHERE sender_type = 'contact' AND conversation_id IN ({$placeholders})
                     GROUP BY conversation_id) c
                JOIN
                    (SELECT conversation_id, MIN(created_at) as first_agent
                     FROM messages WHERE sender_type = 'agent' AND conversation_id IN ({$placeholders})
                     GROUP BY conversation_id) a
                ON c.conversation_id = a.conversation_id
                WHERE a.first_agent > c.first_contact
            ) sub
        ", array_merge($bindings, $bindings));

        $avg = $result[0]->avg_minutes ?? null;
        return $avg !== null ? round((float) $avg, 1) : null;
    }

    public function conversationsByDepartment(?string $from = null, ?string $to = null): Collection
    {
        $q = Conversation::whereIn('status', ['open', 'pending', 'transferred'])
            ->join('departments', 'conversations.department_id', '=', 'departments.id');
        if ($from) $q->where('conversations.created_at', '>=', $from);
        if ($to) $q->where('conversations.created_at', '<=', $to . ' 23:59:59');
        return $q->selectRaw('departments.name, departments.color, count(*) as total')
            ->groupBy('departments.id', 'departments.name', 'departments.color')
            ->orderByDesc('total')
            ->get();
    }

    public function agentPerformance(?string $from = null, ?string $to = null): Collection
    {
        $companyId = app(CurrentCompany::class)->id();

        return User::where('is_active', true)
            ->where('company_id', $companyId)
            ->withCount([
                'assignedConversations as active_count' => function ($q) use ($from, $to) {
                    $q->where('status', 'open');
                    if ($from) $q->where('created_at', '>=', $from);
                    if ($to) $q->where('created_at', '<=', $to . ' 23:59:59');
                },
                'assignedConversations as resolved_today' => function ($q) use ($from, $to) {
                    $q->where('status', 'resolved');
                    if ($from) $q->where('updated_at', '>=', $from);
                    elseif (!$to) $q->whereDate('updated_at', today());
                    if ($to) $q->where('updated_at', '<=', $to . ' 23:59:59');
                },
            ])
            ->orderByDesc('active_count')
            ->get(['id', 'name', 'avatar', 'status', 'role', 'department_id', 'last_seen_at']);
    }

    public function crmStats(?string $from = null, ?string $to = null): array
    {
        $cardsQ = CrmCard::query();
        if ($from) $cardsQ->where('created_at', '>=', $from);
        if ($to) $cardsQ->where('created_at', '<=', $to . ' 23:59:59');

        $activeCards = (clone $cardsQ)->count();
        $createdToday = $from ? $activeCards : CrmCard::whereDate('created_at', today())->count();

        $valorField = CrmCustomField::where('key', 'valor_da_reserva')->first();
        $totalValue = 0;
        if ($valorField) {
            $cardIds = (clone $cardsQ)->pluck('id');
            $totalValue = CrmCardFieldValue::where('field_id', $valorField->id)
                ->whereIn('card_id', $cardIds)
                ->sum(DB::raw('CAST(value AS DECIMAL(10,2))'));
        }

        return [
            'active_cards'   => $activeCards,
            'created_today'  => $createdToday,
            'total_value'    => $totalValue,
            'pipelines'      => CrmPipeline::active()->count(),
        ];
    }

    public function leadsStats(?string $from = null, ?string $to = null): array
    {
        if ($from || $to) {
            $q = BroadcastContact::query();
            if ($from) $q->where('created_at', '>=', $from);
            if ($to) $q->where('created_at', '<=', $to . ' 23:59:59');
            $count = $q->count();
            return ['today' => $count, 'week' => $count, 'month' => $count, 'total' => BroadcastContact::count()];
        }
        return [
            'today' => BroadcastContact::whereDate('created_at', today())->count(),
            'week'  => BroadcastContact::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'month' => BroadcastContact::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'total' => BroadcastContact::count(),
        ];
    }

    public function broadcastStats(?string $from = null, ?string $to = null): array
    {
        $q = BroadcastCampaign::query();
        if ($from) $q->where('created_at', '>=', $from);
        if ($to) $q->where('created_at', '<=', $to . ' 23:59:59');

        $campaigns = (clone $q)->count();
        $activeCampaigns = (clone $q)->whereIn('status', ['draft', 'running'])->count();
        $totalSent = (clone $q)->sum('sent_count');
        $totalFailed = (clone $q)->sum('failed_count');
        $successRate = ($totalSent + $totalFailed) > 0
            ? round(($totalSent / ($totalSent + $totalFailed)) * 100, 1) : 0;

        return [
            'total_campaigns'  => $campaigns,
            'active_campaigns' => $activeCampaigns,
            'total_sent'       => (int) $totalSent,
            'success_rate'     => $successRate,
        ];
    }

    public function pipelineSummary(): Collection
    {
        $pipelines = CrmPipeline::active()->with(['stages' => fn($q) => $q->orderBy('sort_order')])->get();
        $valorField = CrmCustomField::where('key', 'valor_da_reserva')->first();

        foreach ($pipelines as $pipeline) {
            foreach ($pipeline->stages as $stage) {
                $stage->cards_count = CrmCard::where('stage_id', $stage->id)->count();
                $stage->total_value = $valorField
                    ? CrmCardFieldValue::where('field_id', $valorField->id)
                        ->whereIn('card_id', CrmCard::where('stage_id', $stage->id)->pluck('id'))
                        ->sum(DB::raw('CAST(value AS DECIMAL(10,2))'))
                    : 0;
            }
        }
        return $pipelines;
    }
}
