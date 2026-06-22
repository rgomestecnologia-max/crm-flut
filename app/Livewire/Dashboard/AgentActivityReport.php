<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AgentActivityReport extends Component
{
    public string $agentFilter = '';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    #[\Livewire\Attributes\On('dashboard-date-changed')]
    public function onDateChanged(?string $from = null, ?string $to = null): void
    {
        $this->dateFrom = $from;
        $this->dateTo = $to;
    }

    public function render()
    {
        $companyId = app(\App\Services\CurrentCompany::class)->id();

        // Busca conversas onde o agente ENVIOU mensagem no período (não pela data de criação da conversa)
        $query = DB::table('messages')
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->join('contacts', 'conversations.contact_id', '=', 'contacts.id')
            ->where('messages.sender_type', 'agent')
            ->whereNotNull('messages.sender_id')
            ->where('messages.company_id', $companyId)
            ->where('conversations.is_group', false);

        $dateFrom = $this->dateFrom ?: now()->toDateString();
        $dateTo = $this->dateTo ?: now()->toDateString();
        $query->where('messages.created_at', '>=', $dateFrom);
        $query->where('messages.created_at', '<=', $dateTo . ' 23:59:59');

        if ($this->agentFilter) {
            $query->where('messages.sender_id', (int) $this->agentFilter);
        }

        $rows = $query->select(
            'messages.sender_id as agent_id',
            DB::raw('COUNT(DISTINCT messages.conversation_id) as conversations'),
            DB::raw('COUNT(messages.id) as messages'),
            'contacts.phone'
        )
        ->groupBy('messages.sender_id', 'contacts.phone')
        ->get();

        // Agrupa por agente → estado
        $dddEstados = AgentDddReport::DDD_ESTADOS;
        $data = [];
        $estadoTotals = [];

        $agentIds = $rows->pluck('agent_id')->unique()->toArray();
        $agentNames = User::whereIn('id', $agentIds)->pluck('name', 'id')->toArray();

        foreach ($rows as $row) {
            $phone = $row->phone ?? '';
            if (!str_starts_with($phone, '55') || strlen($phone) < 4) continue;

            $ddd = substr($phone, 2, 2);
            $estado = $dddEstados[$ddd] ?? null;
            if (!$estado) continue;

            $agentId = $row->agent_id;
            $agentName = $agentNames[$agentId] ?? 'Agente #' . $agentId;

            if (!isset($data[$agentId])) {
                $data[$agentId] = ['name' => $agentName, 'estados' => [], 'total_convs' => 0, 'total_msgs' => 0];
            }
            $data[$agentId]['estados'][$estado] = ($data[$agentId]['estados'][$estado] ?? 0) + $row->conversations;
            $data[$agentId]['total_convs'] += $row->conversations;
            $data[$agentId]['total_msgs'] += $row->messages;

            $estadoTotals[$estado] = ($estadoTotals[$estado] ?? 0) + $row->conversations;
        }

        arsort($estadoTotals);
        $estados = array_keys($estadoTotals);

        uasort($data, fn($a, $b) => $b['total_convs'] <=> $a['total_convs']);

        $grandTotal = array_sum($estadoTotals);
        $maxValue = max(array_merge([1], array_values($estadoTotals)));

        $agents = User::where('is_active', true)->where('company_id', $companyId)->orderBy('name')->get(['id', 'name']);

        return view('livewire.dashboard.agent-activity-report', compact('data', 'estados', 'estadoTotals', 'grandTotal', 'maxValue', 'agents'));
    }
}
