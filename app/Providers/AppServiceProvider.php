<?php

namespace App\Providers;

use App\Services\CurrentCompany;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Singleton da empresa ativa — vive durante a request, lê da sessão.
        $this->app->singleton(CurrentCompany::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configura SMTP dinâmico a partir das configurações globais
        try {
            $host = \App\Models\GlobalSetting::get('smtp_host');
            if ($host) {
                config([
                    'mail.default'                    => 'smtp',
                    'mail.mailers.smtp.host'          => $host,
                    'mail.mailers.smtp.port'          => (int) \App\Models\GlobalSetting::get('smtp_port', 587),
                    'mail.mailers.smtp.username'      => \App\Models\GlobalSetting::get('smtp_username'),
                    'mail.mailers.smtp.password'      => \App\Models\GlobalSetting::get('smtp_password'),
                    'mail.mailers.smtp.encryption'    => 'tls',
                    'mail.from.address'               => \App\Models\GlobalSetting::get('smtp_from_address', 'noreply@flut.com.br'),
                    'mail.from.name'                  => \App\Models\GlobalSetting::get('smtp_from_name', 'CRM Flut'),
                ]);
            }
        } catch (\Throwable) {
            // Tabela pode não existir durante migrations
        }
    }
}
