<?php

namespace App\Jobs;

use App\Models\BroadcastCampaignRecipient;
use App\Models\BroadcastCampaignRun;
use App\Models\GlobalSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendBroadcastEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 3600;

    public function __construct(public BroadcastCampaignRun $run) {}

    public function handle(): void
    {
        $run      = $this->run->fresh();
        $campaign = $run->campaign;

        if (!$campaign) return;

        $apiKey    = GlobalSetting::get('sendgrid_api_key');
        $fromEmail = GlobalSetting::get('sendgrid_from_email');
        $fromName  = GlobalSetting::get('sendgrid_from_name', 'CRM Flut');

        if (!$apiKey || !$fromEmail) {
            $run->update(['status' => 'failed']);
            $campaign->update(['status' => 'failed']);
            Log::error('SendBroadcastEmail: SendGrid não configurado');
            return;
        }

        $recipients = BroadcastCampaignRecipient::where('run_id', $run->id)
            ->where('status', 'pending')
            ->get();

        $sent   = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            $email = $recipient->broadcastContact?->email;
            if (!$email) {
                $recipient->update(['status' => 'failed', 'error' => 'Sem email']);
                $failed++;
                continue;
            }

            try {
                $name = $recipient->broadcastContact?->name ?? '';

                // Replace variables in subject and content
                $subject = str_replace(
                    ['{nome}', '{name}', '{email}'],
                    [$name, $name, $email],
                    $campaign->subject ?? ''
                );

                $htmlContent = str_replace(
                    ['{nome}', '{name}', '{email}'],
                    [$name, $name, $email],
                    $campaign->html_content ?? $campaign->message ?? ''
                );

                // If no HTML, wrap plain text in basic HTML
                if ($campaign->html_content) {
                    $body = $htmlContent;
                } else {
                    $body = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#333;">'
                        . nl2br(e($htmlContent))
                        . '</div>';
                }

                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type'  => 'application/json',
                ])->post('https://api.sendgrid.com/v3/mail/send', [
                    'personalizations' => [
                        ['to' => [['email' => $email, 'name' => $name]]],
                    ],
                    'from'    => ['email' => $fromEmail, 'name' => $fromName],
                    'subject' => $subject,
                    'content' => [
                        ['type' => 'text/html', 'value' => $body],
                    ],
                ]);

                if ($response->status() >= 200 && $response->status() < 300) {
                    $recipient->update(['status' => 'sent', 'sent_at' => now()]);
                    $sent++;
                } else {
                    $error = $response->body();
                    $recipient->update(['status' => 'failed', 'error' => substr($error, 0, 500)]);
                    $failed++;
                }
            } catch (\Throwable $e) {
                $recipient->update(['status' => 'failed', 'error' => substr($e->getMessage(), 0, 500)]);
                $failed++;
            }

            $run->update(['sent_count' => $sent, 'failed_count' => $failed]);

            // Interval
            if ($campaign->interval_seconds > 0) {
                sleep($campaign->interval_seconds);
            }
        }

        $run->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'sent_count'   => $sent,
            'failed_count' => $failed,
        ]);

        $campaign->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'sent_count'   => ($campaign->sent_count ?? 0) + $sent,
            'failed_count' => ($campaign->failed_count ?? 0) + $failed,
        ]);

        Log::info('Broadcast email completed', [
            'campaign' => $campaign->name, 'sent' => $sent, 'failed' => $failed,
        ]);
    }
}
