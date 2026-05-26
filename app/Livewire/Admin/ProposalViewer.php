<?php

namespace App\Livewire\Admin;

use App\Models\Proposal;
use Livewire\Component;

class ProposalViewer extends Component
{
    public $proposals = [];
    public $expandedId = null;
    public $discountId = null;
    public $discountPercent = 0;
    public $statusFilter = '';

    // Proposta personalizada
    public bool $showCustomForm = false;
    public string $customClientName = '';
    public array $customItems = [['modulo' => '', 'mensal' => '', 'setup' => '']];

    public array $availableModules = [
        'multi'        => 'Multi-atendimento WhatsApp',
        'crm'          => 'CRM — Pipeline de Vendas',
        'email'        => 'E-mail Marketing',
        'ia'           => 'Inteligência Artificial',
        'integrations' => 'Integrações Externas',
        'chatbot'      => 'Chatbot / URA',
        'broadcasts'   => 'Disparos em Massa',
        'chat_interno' => 'Chat Interno',
        'dashboard'    => 'Dashboard & Relatórios',
    ];
    public string $customObs = '';

    public function mount()
    {
        $this->loadProposals();
    }

    public function loadProposals()
    {
        $query = Proposal::with('user')->orderByDesc('created_at');

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $this->proposals = $query->get()->toArray();
    }

    public function setFilter($status)
    {
        $this->statusFilter = $status;
        $this->expandedId = null;
        $this->discountId = null;
        $this->loadProposals();
    }

    public function toggle($id)
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
        $this->discountId = null;
    }

    public function setStatus($id, $status)
    {
        Proposal::findOrFail($id)->update(['status' => $status]);
        $this->loadProposals();
        $labels = ['analise' => 'Em Análise', 'aprovada' => 'Aprovada', 'reprovada' => 'Não Aprovada'];
        $this->dispatch('toast', type: 'success', message: "Status alterado para: {$labels[$status]}");
    }

    public function openDiscount($id)
    {
        if ($this->discountId === $id) {
            $this->discountId = null;
            return;
        }
        $this->discountId = $id;
        $proposal = collect($this->proposals)->firstWhere('id', $id);
        $this->discountPercent = $proposal['discount_percent'] ?? 0;
    }

    public function applyDiscount($id)
    {
        $proposal = Proposal::findOrFail($id);
        $pct = (float) $this->discountPercent;

        if ($pct < 0 || $pct > 90) {
            $this->dispatch('toast', type: 'error', message: 'Desconto deve ser entre 0% e 90%');
            return;
        }

        // Remover desconto (voltar ao original)
        if ($pct == 0 && $proposal->original_total_monthly) {
            $proposal->update([
                'total_monthly'          => $proposal->original_total_monthly,
                'total_setup'            => $proposal->original_total_setup,
                'discount_percent'       => null,
                'original_total_monthly' => null,
                'original_total_setup'   => null,
            ]);

            $this->discountId = null;
            $this->discountPercent = 0;
            $this->loadProposals();
            $this->dispatch('toast', type: 'success', message: 'Desconto removido! Valores originais restaurados.');
            return;
        }

        if ($pct <= 0) {
            $this->dispatch('toast', type: 'error', message: 'Informe um percentual de desconto');
            return;
        }

        // Salvar originais na primeira aplicação
        if (!$proposal->original_total_monthly) {
            $proposal->original_total_monthly = $proposal->total_monthly;
            $proposal->original_total_setup = $proposal->total_setup;
        }

        // Recalcular a partir dos originais (não acumular descontos)
        $factor = 1 - ($pct / 100);
        $origMonthly = $proposal->original_total_monthly;
        $origSetup = $proposal->original_total_setup;

        // Recalcular details proporcionalmente
        $details = $proposal->details;
        foreach ($details as $key => $value) {
            $details[$key] = round($value * $factor, 2);
        }

        $proposal->update([
            'details'                => $details,
            'total_monthly'          => round($origMonthly * $factor, 2),
            'total_setup'            => round($origSetup * $factor, 2),
            'discount_percent'       => $pct,
            'original_total_monthly' => $origMonthly,
            'original_total_setup'   => $origSetup,
        ]);

        $this->discountId = null;
        $this->loadProposals();

        $this->dispatch('toast', type: 'success', message: "Desconto de {$pct}% aplicado!");
    }

    public function delete($id)
    {
        Proposal::findOrFail($id)->delete();
        $this->loadProposals();
    }

    public function addCustomItem()
    {
        $this->customItems[] = ['modulo' => '', 'mensal' => '', 'setup' => ''];
    }

    public function removeCustomItem(int $index)
    {
        unset($this->customItems[$index]);
        $this->customItems = array_values($this->customItems);
        if (empty($this->customItems)) {
            $this->customItems = [['modulo' => '', 'mensal' => '', 'setup' => '']];
        }
    }

    public function saveCustomProposal()
    {
        $this->validate([
            'customClientName'     => 'required|string|max:255',
            'customItems'          => 'required|array|min:1',
            'customItems.*.modulo' => 'required|string',
        ]);

        $details = [];
        $totalMonthly = 0;
        $totalSetup = 0;
        $modules = [];

        foreach ($this->customItems as $item) {
            $key    = $item['modulo'];
            $label  = $this->availableModules[$key] ?? $key;
            $mensal = (float) str_replace(['.', ','], ['', '.'], $item['mensal'] ?? '0');
            $setup  = (float) str_replace(['.', ','], ['', '.'], $item['setup'] ?? '0');

            $details[$key . '_monthly'] = $mensal;
            $details[$key . '_setup']   = $setup;
            $totalMonthly += $mensal;
            $totalSetup += $setup;
            $modules[] = $key;
        }

        if ($this->customObs) {
            $details['observacao'] = $this->customObs;
        }

        Proposal::create([
            'client_name'   => $this->customClientName,
            'modules'       => $modules,
            'config'        => ['tipo' => 'personalizada'],
            'details'       => $details,
            'total_monthly' => $totalMonthly,
            'total_setup'   => $totalSetup,
            'status'        => 'analise',
            'user_id'       => auth()->id(),
        ]);

        $this->showCustomForm = false;
        $this->customClientName = '';
        $this->customItems = [['modulo' => '', 'mensal' => '', 'setup' => '']];
        $this->customObs = '';
        $this->loadProposals();
        $this->dispatch('toast', type: 'success', message: 'Proposta personalizada criada!');
    }

    public function getCounts()
    {
        return [
            'all'       => Proposal::count(),
            'analise'   => Proposal::where('status', 'analise')->count(),
            'aprovada'  => Proposal::where('status', 'aprovada')->count(),
            'reprovada' => Proposal::where('status', 'reprovada')->count(),
        ];
    }

    public function render()
    {
        return view('livewire.admin.proposal-viewer', [
            'counts' => $this->getCounts(),
        ]);
    }
}
