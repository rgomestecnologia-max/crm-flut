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

    // SMTP
    public string $smtp_host     = '';
    public string $smtp_port     = '587';
    public string $smtp_username = '';
    public string $smtp_password = '';
    public string $smtp_from_address = '';
    public string $smtp_from_name    = '';
    public bool   $smtpKeySaved = false;

    public function mount(): void
    {
        if (!Auth::user()?->isAdmin()) {
            abort(403);
        }

        $existingKey        = GlobalSetting::get('gemini_api_key');
        $this->gemini_model = GlobalSetting::get('gemini_model', 'gemini-2.0-flash');
        $this->keyAlreadySaved = !empty($existingKey);

        // SMTP
        $this->smtp_host         = GlobalSetting::get('smtp_host', '');
        $this->smtp_port         = GlobalSetting::get('smtp_port', '587');
        $this->smtp_username     = GlobalSetting::get('smtp_username', '');
        $this->smtp_from_address = GlobalSetting::get('smtp_from_address', '');
        $this->smtp_from_name    = GlobalSetting::get('smtp_from_name', 'CRM Flut');
        $this->smtpKeySaved      = !empty(GlobalSetting::get('smtp_password'));
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

        // SMTP
        if ($this->smtp_host) GlobalSetting::set('smtp_host', $this->smtp_host);
        if ($this->smtp_port) GlobalSetting::set('smtp_port', $this->smtp_port);
        if ($this->smtp_username) GlobalSetting::set('smtp_username', $this->smtp_username);
        if (!empty($this->smtp_password)) GlobalSetting::set('smtp_password', $this->smtp_password);
        if ($this->smtp_from_address) GlobalSetting::set('smtp_from_address', $this->smtp_from_address);
        if ($this->smtp_from_name) GlobalSetting::set('smtp_from_name', $this->smtp_from_name);

        $this->gemini_api_key  = '';
        $this->smtp_password   = '';
        $this->keyAlreadySaved = true;
        $this->smtpKeySaved    = !empty(GlobalSetting::get('smtp_password'));

        $this->dispatch('toast', type: 'success', message: 'Configurações globais salvas.');
    }

    public function render()
    {
        return view('livewire.admin.global-settings-manager');
    }
}
