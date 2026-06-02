<?php

namespace App\Services;

use App\Models\EmailFunnel;
use App\Models\EmailFunnelSubscriber;
use App\Models\BroadcastContact;
use Illuminate\Support\Facades\Log;

class EmailFunnelEnroller
{
    /**
     * Enrola um contato em todos os funis ativos que correspondem ao gatilho.
     *
     * @param int    $companyId
     * @param int    $contactId   BroadcastContact ID
     * @param string $triggerType  tag|landing_page|flutchat|crm_stage
     * @param string|null $triggerValue  Ex: nome da tag, slug da LP, stage ID
     */
    public static function enroll(int $companyId, int $contactId, string $triggerType, ?string $triggerValue = null): int
    {
        $funnels = EmailFunnel::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->where('trigger_type', $triggerType)
            ->when($triggerValue, fn($q) => $q->where('trigger_value', $triggerValue))
            ->when(!$triggerValue, fn($q) => $q->whereNull('trigger_value'))
            ->get();

        $enrolled = 0;

        foreach ($funnels as $funnel) {
            $already = EmailFunnelSubscriber::withoutGlobalScopes()
                ->where('funnel_id', $funnel->id)
                ->where('contact_id', $contactId)
                ->exists();

            if ($already) continue;

            $firstStep = $funnel->steps()->first();
            if (!$firstStep) continue;

            EmailFunnelSubscriber::withoutGlobalScopes()->create([
                'company_id'      => $companyId,
                'funnel_id'       => $funnel->id,
                'contact_id'      => $contactId,
                'current_step_id' => $firstStep->id,
                'status'          => 'active',
                'step_entered_at' => now(),
                'entered_at'      => now(),
            ]);

            $enrolled++;
            Log::info('EmailFunnelEnroller: contato inscrito', [
                'contact_id' => $contactId,
                'funnel_id'  => $funnel->id,
                'trigger'    => $triggerType,
            ]);
        }

        return $enrolled;
    }

    /**
     * Enrola por tag — verifica funis com trigger_type=tag e trigger_value=tag.
     */
    public static function enrollByTag(int $companyId, int $contactId, array $tags): int
    {
        $total = 0;
        foreach ($tags as $tag) {
            $total += self::enroll($companyId, $contactId, 'tag', $tag);
        }
        return $total;
    }

    /**
     * Enrola por landing page — tenta funis com slug específico E funis genéricos (sem slug).
     */
    public static function enrollByLandingPage(int $companyId, int $contactId, ?string $pageSlug = null): int
    {
        $total = 0;
        if ($pageSlug) {
            $total += self::enroll($companyId, $contactId, 'landing_page', $pageSlug);
        }
        // Também enrola em funis genéricos (trigger_value null = qualquer landing page)
        $total += self::enroll($companyId, $contactId, 'landing_page', null);
        return $total;
    }

    /**
     * Enrola por FlutChat.
     */
    public static function enrollByFlutChat(int $companyId, int $contactId): int
    {
        return self::enroll($companyId, $contactId, 'flutchat');
    }

    /**
     * Enrola por mudança de stage no CRM.
     */
    public static function enrollByCrmStage(int $companyId, int $contactId, int $stageId): int
    {
        return self::enroll($companyId, $contactId, 'crm_stage', (string) $stageId);
    }
}
