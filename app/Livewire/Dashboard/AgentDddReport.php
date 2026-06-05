<?php

namespace App\Livewire\Dashboard;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AgentDddReport extends Component
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

    public const DDD_ESTADOS = [
        '11'=>'SP','12'=>'SP','13'=>'SP','14'=>'SP','15'=>'SP','16'=>'SP','17'=>'SP','18'=>'SP','19'=>'SP',
        '21'=>'RJ','22'=>'RJ','24'=>'RJ',
        '27'=>'ES','28'=>'ES',
        '31'=>'MG','32'=>'MG','33'=>'MG','34'=>'MG','35'=>'MG','37'=>'MG','38'=>'MG',
        '41'=>'PR','42'=>'PR','43'=>'PR','44'=>'PR','45'=>'PR','46'=>'PR',
        '47'=>'SC','48'=>'SC','49'=>'SC',
        '51'=>'RS','53'=>'RS','54'=>'RS','55'=>'RS',
        '61'=>'DF','62'=>'GO','64'=>'GO','63'=>'TO',
        '65'=>'MT','66'=>'MT','67'=>'MS',
        '68'=>'AC','69'=>'RO',
        '71'=>'BA','73'=>'BA','74'=>'BA','75'=>'BA','77'=>'BA',
        '79'=>'SE',
        '81'=>'PE','82'=>'AL','83'=>'PB','84'=>'RN','85'=>'CE','86'=>'PI','87'=>'PE','88'=>'CE','89'=>'PI',
        '91'=>'PA','92'=>'AM','93'=>'PA','94'=>'PA','95'=>'RR','96'=>'AP','97'=>'AM','98'=>'MA','99'=>'MA',
    ];

    public function render()
    {
        $query = Conversation::with(['contact', 'assignedAgent'])
            ->whereNotNull('assigned_to')
            ->where('is_group', false);

        if ($this->dateFrom) $query->where('created_at', '>=', $this->dateFrom);
        if ($this->dateTo) $query->where('created_at', '<=', $this->dateTo . ' 23:59:59');

        if ($this->agentFilter) {
            $query->where('assigned_to', (int) $this->agentFilter);
        }

        $conversations = $query->get();

        // Agrupa por agente → estado
        $data = [];
        $estadoTotals = [];
        $allEstados = [];

        foreach ($conversations as $conv) {
            $phone = $conv->contact?->phone ?? '';
            if (!str_starts_with($phone, '55') || strlen($phone) < 4) continue;

            $ddd = substr($phone, 2, 2);
            $estado = self::DDD_ESTADOS[$ddd] ?? null;
            if (!$estado) continue;

            $agentName = $conv->assignedAgent?->name ?? 'Sem agente';
            $agentId = $conv->assigned_to;

            if (!isset($data[$agentId])) {
                $data[$agentId] = ['name' => $agentName, 'estados' => [], 'total' => 0];
            }
            $data[$agentId]['estados'][$estado] = ($data[$agentId]['estados'][$estado] ?? 0) + 1;
            $data[$agentId]['total']++;

            $estadoTotals[$estado] = ($estadoTotals[$estado] ?? 0) + 1;
            $allEstados[$estado] = true;
        }

        // Ordena estados por total (mais atendimentos primeiro)
        arsort($estadoTotals);
        $estados = array_keys($estadoTotals);

        // Ordena agentes por total
        uasort($data, fn($a, $b) => $b['total'] <=> $a['total']);

        $grandTotal = array_sum($estadoTotals);
        $maxValue = max(array_merge([1], array_values($estadoTotals)));

        $companyId = app(\App\Services\CurrentCompany::class)->id();
        $agents = User::where('is_active', true)->where('company_id', $companyId)->orderBy('name')->get(['id', 'name']);

        return view('livewire.dashboard.agent-ddd-report', compact('data', 'estados', 'estadoTotals', 'grandTotal', 'maxValue', 'agents'));
    }
}
