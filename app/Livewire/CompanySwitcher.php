<?php

namespace App\Livewire;

use App\Models\Company;
use App\Services\CurrentCompany;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Componente da sidebar que mostra a empresa atual e permite admin trocar
 * de empresa rapidamente sem passar pela tela /select-company.
 *
 * Para agentes/supervisores, mostra apenas o nome da empresa (sem dropdown).
 */
class CompanySwitcher extends Component
{
    public function switchTo(int $companyId)
    {
        if (!Auth::user()?->isAdmin()) {
            abort(403);
        }

        $company = Company::active()->find($companyId);
        if (!$company) {
            $this->dispatch('toast', type: 'error', message: 'Empresa não encontrada ou inativa.');
            return;
        }

        app(CurrentCompany::class)->set($company);

        // Redireciona pra dashboard pra recarregar tudo no contexto da nova empresa.
        return redirect()->route('dashboard');
    }

    public function render()
    {
        $current   = app(CurrentCompany::class)->model();
        $companies = collect();

        if (Auth::user()?->isAdmin()) {
            $companies = Company::active()->orderBy('name')->get();
        }

        return view('livewire.company-switcher', compact('current', 'companies'));
    }
}
