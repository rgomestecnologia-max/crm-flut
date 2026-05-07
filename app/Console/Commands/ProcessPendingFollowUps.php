<?php

namespace App\Console\Commands;

use App\Jobs\SendFollowUpMessage;
use App\Models\Automation;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\CrmCard;
use Illuminate\Console\Command;

class ProcessPendingFollowUps extends Command
{
    protected $signature = 'followups:process';
    protected $description = 'Envia follow-ups pendentes para conversas sem resposta';

    public function handle(): void
    {
        $automations = Automation::withoutGlobalScopes()
            ->where('is_active', true)
            ->whereNotNull('follow_up_message')
            ->where('follow_up_delay_minutes', '>', 0)
            ->get();

        foreach ($automations as $automation) {
            app(\App\Services\CurrentCompany::class)->set($automation->company_id, persist: false);

            $cutoff = now()->subMinutes($automation->follow_up_delay_minutes);

            // Conversas da automação criadas antes do cutoff, abertas, sem resposta do contato
            $conversations = Conversation::where('source_automation_id', $automation->id)
                ->where('status', 'open')
                ->where('created_at', '<=', $cutoff)
                ->where('created_at', '>=', now()->subHours(24)) // só últimas 24h
                ->get();

            foreach ($conversations as $conv) {
                // Já respondeu?
                if (Message::where('conversation_id', $conv->id)->where('sender_type', 'contact')->exists()) continue;

                // Já recebeu follow-up? (mais de 1 msg agent)
                $agentMsgs = Message::where('conversation_id', $conv->id)->where('sender_type', 'agent')->count();
                if ($agentMsgs > 1) continue;

                // Card ainda na primeira etapa?
                $contact = $conv->contact;
                if (!$contact) continue;

                $firstStageId = \App\Models\CrmStage::where('pipeline_id', function ($q) use ($automation) {
                    $q->select('id')->from('crm_pipelines')->where('company_id', $automation->company_id)->where('is_active', true)->orderBy('sort_order')->limit(1);
                })->orderBy('sort_order')->value('id');

                if ($firstStageId) {
                    $cardInStage = CrmCard::where('contact_id', $contact->id)->where('stage_id', $firstStageId)->exists();
                    if (!$cardInStage) continue;
                }

                // Já tem job na fila para esta conversa?
                $jobExists = \DB::table('jobs')
                    ->where('payload', 'like', '%"conversationId":' . $conv->id . '%')
                    ->where('payload', 'like', '%SendFollowUp%')
                    ->exists();
                if ($jobExists) continue;

                // Dispara follow-up imediatamente
                SendFollowUpMessage::dispatch(
                    $contact->id,
                    $conv->id,
                    $firstStageId ?? 0,
                    $automation->follow_up_message,
                );

                $this->line("Follow-up: {$contact->name} (conv {$conv->id})");
            }
        }
    }
}
