<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Verifica follow-ups pendentes a cada 5 minutos
Schedule::command('followups:process')->everyFiveMinutes()->withoutOverlapping();

// Processa funis de email marketing a cada 2 minutos
Schedule::job(new \App\Jobs\ProcessEmailFunnel)->everyTwoMinutes()->withoutOverlapping()->name('email-funnels');

// Follow-up de inatividade da IA a cada 10 minutos
Schedule::job(new \App\Jobs\ProcessAiInactivityFollowUp)->everyTenMinutes()->withoutOverlapping()->name('ai-inactivity');

// Monitoramento: limpa failed jobs e reinicia worker se houver acúmulo
Schedule::call(function () {
    $failedCount = \DB::table('failed_jobs')->where('failed_at', '>=', now()->subHour())->count();
    $pendingCount = \DB::table('jobs')->count();

    if ($failedCount > 20 || $pendingCount > 50) {
        \Log::warning('Queue health check: reiniciando worker', [
            'failed_1h' => $failedCount,
            'pending'   => $pendingCount,
        ]);

        // Limpa failed jobs antigos (mais de 24h)
        \DB::table('failed_jobs')->where('failed_at', '<', now()->subDay())->delete();

        // Reinicia worker
        \Artisan::call('queue:restart');
    }
})->everyFiveMinutes()->name('queue-health-check')->withoutOverlapping();
