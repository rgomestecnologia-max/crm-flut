<?php

namespace App\Livewire\Admin;

use App\Models\GlobalSetting;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Configurações globais do sistema — compartilhadas entre TODAS as empresas.
 * Só o admin do sistema pode acessar.
 */
class GlobalSettingsManager extends Component
{
    public string $gemini_api_key = '';
    public string $gemini_model   = 'gemini-2.0-flash';
    public bool   $keyAlreadySaved = false;

    public function mount(): void
    {
        if (!Auth::user()?->isAdmin()) {
            abort(403);
        }

        $existingKey        = GlobalSetting::get('gemini_api_key');
        $this->gemini_model = GlobalSetting::get('gemini_model', 'gemini-2.0-flash');
        $this->keyAlreadySaved = !empty($existingKey);
    }

    public function save(): void
    {
        $rules = [
            'gemini_model' => 'required|string',
        ];

        if (!$this->keyAlreadySaved || !empty($this->gemini_api_key)) {
            $rules['gemini_api_key'] = 'required|string|min:20';
        }

        $this->validate($rules);

        if (!empty($this->gemini_api_key)) {
            GlobalSetting::set('gemini_api_key', $this->gemini_api_key);
        }
        GlobalSetting::set('gemini_model', $this->gemini_model);

        $this->gemini_api_key  = '';
        $this->keyAlreadySaved = true;

        $this->dispatch('toast', type: 'success', message: 'Configurações globais salvas. Todas as empresas usarão estes valores.');
    }

    public function render()
    {
        return view('livewire.admin.global-settings-manager');
    }
}
