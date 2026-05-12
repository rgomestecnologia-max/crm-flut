<?php

namespace App\Providers;

use App\Events\MessageReceived;
use App\Listeners\SendPushOnNewMessage;
use App\Services\CurrentCompany;
use Illuminate\Support\Facades\Event;
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
        Event::listen(MessageReceived::class, SendPushOnNewMessage::class);
    }
}
