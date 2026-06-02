<?php

namespace App\Jobs;

use App\Models\EmailFunnel;
use App\Models\EmailFunnelLog;
use App\Models\EmailFunnelStep;
use App\Models\EmailFunnelSubscriber;
use App\Models\GlobalSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessEmailFunnel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        // Busca todos os funis ativos
        $funnels = EmailFunnel::withoutGlobalScopes()
            ->where('status', 'active')
            ->get();

        foreach ($funnels as $funnel) {
            $this->processFunnel($funnel);
        }
    }

    private function processFunnel(EmailFunnel $funnel): void
    {
        app(\App\Services\CurrentCompany::class)->set($funnel->company_id, persist: false);

        $subscribers = EmailFunnelSubscriber::withoutGlobalScopes()
            ->where('funnel_id', $funnel->id)
            ->where('status', 'active')
            ->whereNotNull('current_step_id')
            ->get();

        foreach ($subscribers as $sub) {
            try {
                $this->processSubscriber($sub, $funnel);
            } catch (\Throwable $e) {
                Log::warning('ProcessEmailFunnel: erro no subscriber', [
                    'subscriber' => $sub->id, 'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function processSubscriber(EmailFunnelSubscriber $sub, EmailFunnel $funnel): void
    {
        $step = EmailFunnelStep::find($sub->current_step_id);
        if (!$step) {
            $sub->update(['status' => 'completed', 'completed_at' => now()]);
            return;
        }

        $config = $step->config ?? [];

        if ($step->type === 'delay') {
            $delaySeconds = (int) ($config['seconds'] ?? 86400);
            $enteredAt = $sub->step_entered_at ?? $sub->entered_at ?? now();

            if (now()->diffInSeconds($enteredAt) < $delaySeconds) {
                return; // Ainda esperando
            }

            // Delay passou — avança para próximo step
            $this->advanceToNext($sub, $step, $funnel);
        }
        elseif ($step->type === 'email') {
            // Verifica se já enviou este email
            $alreadySent = EmailFunnelLog::where('subscriber_id', $sub->id)
                ->where('step_id', $step->id)
                ->where('action', 'sent')
                ->exists();

            if ($alreadySent) {
                // Já enviou — avança
                $this->advanceToNext($sub, $step, $funnel);
                return;
            }

            // Envia email
            $sent = $this->sendEmail($sub, $step, $funnel);
            if ($sent) {
                // Avança para próximo step
                $this->advanceToNext($sub, $step, $funnel);
            }
        }
        elseif ($step->type === 'condition') {
            $field = $config['field'] ?? 'opened';

            // Busca o step de email anterior
            $prevEmailStep = EmailFunnelStep::where('funnel_id', $funnel->id)
                ->where('sort_order', '<', $step->sort_order)
                ->where('type', 'email')
                ->orderByDesc('sort_order')
                ->first();

            if (!$prevEmailStep) {
                $this->advanceToNext($sub, $step, $funnel);
                return;
            }

            // Verifica logs do email anterior
            $hasAction = EmailFunnelLog::where('subscriber_id', $sub->id)
                ->where('step_id', $prevEmailStep->id)
                ->where('action', $field === 'not_opened' ? 'opened' : $field)
                ->exists();

            $conditionMet = $field === 'not_opened' ? !$hasAction : $hasAction;

            // Aguarda pelo menos 24h após envio para avaliar condição
            $sentLog = EmailFunnelLog::where('subscriber_id', $sub->id)
                ->where('step_id', $prevEmailStep->id)
                ->where('action', 'sent')
                ->first();

            if ($sentLog && now()->diffInHours($sentLog->created_at) < 24) {
                return; // Espera 24h para avaliar
            }

            // Condição avaliada — avança (SIM = próximo, NÃO = pula 1)
            if ($conditionMet) {
                $this->advanceToNext($sub, $step, $funnel);
            } else {
                // Pula para 2 steps à frente (pula o branch SIM)
                $skipStep = EmailFunnelStep::where('funnel_id', $funnel->id)
                    ->where('sort_order', '>', $step->sort_order + 1)
                    ->orderBy('sort_order')
                    ->first();
                if ($skipStep) {
                    $sub->update(['current_step_id' => $skipStep->id, 'step_entered_at' => now()]);
                } else {
                    $sub->update(['status' => 'completed', 'completed_at' => now()]);
                }
            }
        }
    }

    private function advanceToNext(EmailFunnelSubscriber $sub, EmailFunnelStep $currentStep, EmailFunnel $funnel): void
    {
        $nextStep = EmailFunnelStep::where('funnel_id', $funnel->id)
            ->where('sort_order', '>', $currentStep->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($nextStep) {
            $sub->update(['current_step_id' => $nextStep->id, 'step_entered_at' => now()]);
        } else {
            $sub->update(['status' => 'completed', 'completed_at' => now(), 'current_step_id' => null]);
        }
    }

    private function sendEmail(EmailFunnelSubscriber $sub, EmailFunnelStep $step, EmailFunnel $funnel): bool
    {
        $contact = $sub->contact;
        if (!$contact || !$contact->email) {
            EmailFunnelLog::create([
                'subscriber_id' => $sub->id, 'step_id' => $step->id,
                'action' => 'failed',
            ]);
            return false;
        }

        $config = $step->config ?? [];
        $name  = $contact->name ?? '';
        $email = $contact->email;

        // Busca config SMTP/SendGrid
        $company = app(\App\Services\CurrentCompany::class)->model();
        $apiKey    = $company?->sendgrid_api_key ?: GlobalSetting::get('sendgrid_api_key');
        $fromEmail = $company?->sendgrid_from_email ?: GlobalSetting::get('sendgrid_from_email')
            ?: GlobalSetting::get('smtp_from_address', 'noreply@flut.com.br');
        $fromName  = $config['from_name'] ?: ($company?->sendgrid_from_name ?: GlobalSetting::get('sendgrid_from_name')
            ?: GlobalSetting::get('smtp_from_name', 'CRM Flut'));

        if (!$apiKey) {
            // Fallback: tenta enviar via Laravel Mail (SMTP configurado nas Config Globais)
            return $this->sendViaSmtp($sub, $step, $contact, $fromEmail, $fromName);
        }

        $subject = str_replace(['{nome}', '{name}', '{email}'], [$name, $name, $email], $config['subject'] ?? 'Sem assunto');
        $body = str_replace(['{nome}', '{name}', '{email}'], [$name, $name, $email], $config['html_content'] ?? '');

        // Link de descadastro
        $unsubToken = \App\Http\Controllers\UnsubscribeController::generateToken($email, $funnel->company_id);
        $unsubUrl = url("/unsubscribe/{$unsubToken}");
        $body .= '<div style="margin-top:30px;padding-top:15px;border-top:1px solid #eee;text-align:center;"><p style="font-size:11px;color:#999;">Não quer mais receber? <a href="' . $unsubUrl . '" style="color:#999;text-decoration:underline;">Cancelar inscrição</a>.</p></div>';

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ])->post('https://api.sendgrid.com/v3/mail/send', [
                'personalizations' => [['to' => [['email' => $email, 'name' => $name]]]],
                'from'    => ['email' => $fromEmail, 'name' => $fromName],
                'subject' => $subject,
                'content' => [['type' => 'text/html', 'value' => $body]],
                'tracking_settings' => ['open_tracking' => ['enable' => true], 'click_tracking' => ['enable' => true]],
            ]);

            $msgId = $response->header('X-Message-Id');

            EmailFunnelLog::create([
                'subscriber_id' => $sub->id, 'step_id' => $step->id,
                'action' => $response->status() < 300 ? 'sent' : 'failed',
                'message_id' => $msgId,
            ]);

            Log::info('EmailFunnel: email enviado', ['subscriber' => $sub->id, 'step' => $step->id, 'email' => $email]);
            return $response->status() < 300;
        } catch (\Throwable $e) {
            EmailFunnelLog::create(['subscriber_id' => $sub->id, 'step_id' => $step->id, 'action' => 'failed']);
            Log::error('EmailFunnel: falha ao enviar', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function sendViaSmtp(EmailFunnelSubscriber $sub, EmailFunnelStep $step, $contact, string $fromEmail, string $fromName): bool
    {
        $config = $step->config ?? [];
        $name  = $contact->name ?? '';
        $email = $contact->email;

        $subject = str_replace(['{nome}', '{name}', '{email}'], [$name, $name, $email], $config['subject'] ?? 'Sem assunto');
        $body = str_replace(['{nome}', '{name}', '{email}'], [$name, $name, $email], $config['html_content'] ?? '');

        try {
            \Illuminate\Support\Facades\Mail::html($body, function ($msg) use ($email, $name, $subject, $fromEmail, $fromName) {
                $msg->to($email, $name)->subject($subject)->from($fromEmail, $fromName);
            });

            EmailFunnelLog::create(['subscriber_id' => $sub->id, 'step_id' => $step->id, 'action' => 'sent']);
            return true;
        } catch (\Throwable $e) {
            EmailFunnelLog::create(['subscriber_id' => $sub->id, 'step_id' => $step->id, 'action' => 'failed']);
            Log::error('EmailFunnel SMTP: falha', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
