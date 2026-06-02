<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\CrmCard;
use App\Models\CrmCardFieldValue;
use App\Models\Message;
use Illuminate\Console\Command;

class FillOrangeFieldsFromHistory extends Command
{
    protected $signature = 'orange:fill-fields';
    protected $description = 'Preenche ramo de atividade e produção diária a partir do histórico de conversas';

    public function handle(): void
    {
        app(\App\Services\CurrentCompany::class)->set(3, persist: false);

        $ramoFieldId = 38;
        $prodFieldId = 39;
        $filled = 0;
        $already = 0;

        $convs = Conversation::withoutGlobalScopes()
            ->where('company_id', 3)
            ->whereNotNull('contact_id')
            ->get();

        $this->info("Conversas a processar: {$convs->count()}");

        foreach ($convs as $conv) {
            $card = CrmCard::where('contact_id', $conv->contact_id)
                ->where('pipeline_id', 6)->first();
            if (!$card) continue;

            $hasRamo = CrmCardFieldValue::where('card_id', $card->id)
                ->where('field_id', $ramoFieldId)
                ->whereNotNull('value')->where('value', '!=', '')->exists();
            $hasProd = CrmCardFieldValue::where('card_id', $card->id)
                ->where('field_id', $prodFieldId)
                ->whereNotNull('value')->where('value', '!=', '')->exists();

            if ($hasRamo && $hasProd) { $already++; continue; }

            $allMsgs = Message::where('conversation_id', $conv->id)
                ->whereIn('sender_type', ['agent', 'contact'])
                ->whereNotNull('content')
                ->orderBy('id')
                ->get();

            $ramo = null;
            $prod = null;

            foreach ($allMsgs as $i => $msg) {
                if ($msg->sender_type !== 'agent') continue;
                $content = mb_strtolower($msg->content ?? '');

                // Pega próxima mensagem do contato
                $nextContact = null;
                for ($j = $i + 1; $j < $allMsgs->count(); $j++) {
                    if ($allMsgs[$j]->sender_type === 'contact' && $allMsgs[$j]->content) {
                        $nextContact = $allMsgs[$j];
                        break;
                    }
                }
                if (!$nextContact) continue;
                $resp = trim($nextContact->content);
                if (strlen($resp) > 200) continue;

                // Pergunta sobre ramo
                if (!$ramo && !$hasRamo && (
                    str_contains($content, 'ramo') ||
                    str_contains($content, 'atividade') ||
                    str_contains($content, 'segmento') ||
                    str_contains($content, 'qual o seu neg')
                )) {
                    $ramo = $resp;
                }

                // Pergunta sobre produção
                if (!$prod && !$hasProd && (
                    str_contains($content, 'produção') ||
                    str_contains($content, 'producao') ||
                    str_contains($content, 'estimativa') ||
                    str_contains($content, 'volume') ||
                    str_contains($content, 'litros por dia') ||
                    str_contains($content, 'suco de laranja')
                )) {
                    $prod = $resp;
                }
            }

            $updated = false;
            if ($ramo && !$hasRamo) {
                CrmCardFieldValue::updateOrCreate(
                    ['card_id' => $card->id, 'field_id' => $ramoFieldId],
                    ['value' => $ramo]
                );
                $updated = true;
            }
            if ($prod && !$hasProd) {
                CrmCardFieldValue::updateOrCreate(
                    ['card_id' => $card->id, 'field_id' => $prodFieldId],
                    ['value' => $prod]
                );
                $updated = true;
            }
            if ($updated) $filled++;
        }

        $this->info("Cards atualizados: {$filled}");
        $this->info("Já tinham campos: {$already}");
    }
}
