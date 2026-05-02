<?php

namespace App\Jobs;

use App\Models\BroadcastCampaignRecipient;
use App\Models\BroadcastCampaignRun;
use App\Models\EvolutionApiConfig;
use App\Services\EvolutionApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBroadcastMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 3600; // 1 hour max

    public function __construct(public BroadcastCampaignRun $run) {}

    public function handle(): void
    {
        $run      = $this->run->fresh();
        $campaign = $run->campaign;

        if (!$campaign) {
            Log::error('SendBroadcastMessage: campaign not found', ['run_id' => $run->id]);
            return;
        }

        app(\App\Services\CurrentCompany::class)->set((int) $campaign->company_id, persist: false);

        $evolutionConfig = EvolutionApiConfig::current();
        if (!$evolutionConfig || !$evolutionConfig->is_active) {
            $run->update(['status' => 'failed']);
            $campaign->update(['status' => 'failed']);
            Log::error('SendBroadcastMessage: Evolution API não configurada');
            return;
        }

        $api = new EvolutionApiService($evolutionConfig);
        $recipients = BroadcastCampaignRecipient::where('run_id', $run->id)
            ->where('status', 'pending')
            ->get();

        $sent   = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            try {
                $text = str_replace(
                    ['{nome}', '{name}'],
                    [$recipient->broadcastContact?->name ?? '', $recipient->broadcastContact?->name ?? ''],
                    $campaign->message
                );

                // Envia imagem com caption ou texto puro
                if ($campaign->image_path) {
                    $imageUrl = \App\Services\MediaStorage::url($campaign->image_path);
                    if (!str_starts_with($imageUrl, 'http')) {
                        $imageUrl = url($imageUrl);
                    }
                    $result = $api->sendImage($recipient->phone, $imageUrl, $text);
                } else {
                    $result = $api->sendText($recipient->phone, $text);
                }

                if ($result['success'] ?? false) {
                    $recipient->update(['status' => 'sent', 'sent_at' => now()]);
                    $sent++;
                } else {
                    $error = $result['error'] ?? json_encode($result);
                    $recipient->update(['status' => 'failed', 'error' => substr($error, 0, 500)]);
                    $failed++;
                }
            } catch (\Throwable $e) {
                $recipient->update(['status' => 'failed', 'error' => substr($e->getMessage(), 0, 500)]);
                $failed++;
            }

            // Update counters
            $run->update(['sent_count' => $sent, 'failed_count' => $failed]);

            // Interval between messages
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

        Log::info('Broadcast campaign completed', [
            'campaign' => $campaign->name,
            'run'      => $run->id,
            'sent'     => $sent,
            'failed'   => $failed,
        ]);
    }
}
