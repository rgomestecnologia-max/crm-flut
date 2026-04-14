<?php

namespace App\Livewire\Auth;

use App\Models\Company;
use App\Services\CurrentCompany;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.auth')]
#[Title('Selecionar empresa')]
class SelectCompany extends Component
{
    public function mount()
    {
        $user = Auth::user();

        // Apenas admin acessa essa tela. Agente já tem company fixa.
        if (!$user || !$user->isAdmin()) {
            return redirect()->route('dashboard');
        }
    }

    public function enter(int $companyId)
    {
        $company = Company::active()->find($companyId);
        if (!$company) {
            $this->dispatch('toast', type: 'error', message: 'Empresa não encontrada ou inativa.');
            return;
        }

        app(CurrentCompany::class)->set($company);

        return redirect()->route('dashboard');
    }

    public function logout()
    {
        Auth::user()?->update(['status' => 'offline']);
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('login');
    }

    public function render()
    {
        $companies = Company::active()->orderBy('name')->get();
        return view('livewire.auth.select-company', compact('companies'));
    }
}
