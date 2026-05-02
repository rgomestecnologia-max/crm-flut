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

    // SendGrid
    public string $sendgrid_api_key    = '';
    public string $sendgrid_from_email = '';
    public string $sendgrid_from_name  = '';
    public bool   $sendgridKeySaved    = false;

    public function mount(): void
    {
        if (!Auth::user()?->isAdmin()) {
            abort(403);
        }

        $existingKey        = GlobalSetting::get('gemini_api_key');
        $this->gemini_model = GlobalSetting::get('gemini_model', 'gemini-2.0-flash');
        $this->keyAlreadySaved = !empty($existingKey);

        // SendGrid
        $this->sendgrid_from_email = GlobalSetting::get('sendgrid_from_email', '');
        $this->sendgrid_from_name  = GlobalSetting::get('sendgrid_from_name', '');
        $this->sendgridKeySaved    = !empty(GlobalSetting::get('sendgrid_api_key'));
    }

    public function save(): void
    {
        $rules = [
            'gemini_model'         => 'required|string',
            'sendgrid_from_email'  => 'nullable|email|max:200',
            'sendgrid_from_name'   => 'nullable|string|max:100',
        ];

        if (!$this->keyAlreadySaved || !empty($this->gemini_api_key)) {
            $rules['gemini_api_key'] = 'required|string|min:20';
        }

        $this->validate($rules);

        if (!empty($this->gemini_api_key)) {
            GlobalSetting::set('gemini_api_key', $this->gemini_api_key);
        }
        GlobalSetting::set('gemini_model', $this->gemini_model);

        // SendGrid
        if (!empty($this->sendgrid_api_key)) {
            GlobalSetting::set('sendgrid_api_key', $this->sendgrid_api_key);
            $this->sendgridKeySaved = true;
        }
        if ($this->sendgrid_from_email) {
            GlobalSetting::set('sendgrid_from_email', $this->sendgrid_from_email);
        }
        if ($this->sendgrid_from_name) {
            GlobalSetting::set('sendgrid_from_name', $this->sendgrid_from_name);
        }

        $this->gemini_api_key   = '';
        $this->sendgrid_api_key = '';
        $this->keyAlreadySaved  = true;

        $this->dispatch('toast', type: 'success', message: 'Configurações globais salvas.');
    }

    public function render()
    {
        return view('livewire.admin.global-settings-manager');
    }
}
