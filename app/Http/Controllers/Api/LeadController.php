<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendAutomationMessage;
use App\Models\ApiToken;
use App\Models\Automation;
use App\Models\Contact;
use App\Models\CrmCard;
use App\Models\CrmCardActivity;
use App\Models\CrmCardFieldValue;
use App\Models\CrmCustomField;
use App\Models\CrmPipeline;
use App\Models\CrmStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LeadController extends Controller
{
    /**
     * POST /api/leads
     *
     * Campos fixos aceitos:
     *   name      | nome               string required  Nome completo
     *   phone     | whatsapp | fone    string required  WhatsApp (só dígitos)
     *   email                          string optional  E-mail
     *   notes     | observacoes        string optional  Observações
     *
     * Campos personalizados (key do campo ou aliases sem acentos/preposições):
     *   data_de_entrada | data_entrada   → campo "Data de Entrada"
     *   data_de_saida   | data_saida     → campo "Data de Saida"
     *   valor_da_reserva | valor_reserva → campo "Valor da Reserva"
     *
     * Configuração do destino (opcional):
     *   pipeline_id  int  ID do pipeline (usa padrão do token se omitido)
     *   stage_id     int  ID da etapa   (usa primeira etapa se omitido)
     */
    public function store(Request $request): JsonResponse
    {
        /** @var ApiToken $apiToken */
        $apiToken = $request->attributes->get('api_token');

        // ── Log de entrada para diagnóstico ──────────────────────────
        Log::info('API /leads recebido', [
            'token'   => $apiToken->name,
            'payload' => $request->except([]),
            'headers' => [
                'Content-Type'  => $request->header('Content-Type'),
                'Authorization' => $request->header('Authorization') ? 'Bearer ***' : 'ausente',
            ],
        ]);

        // ── Normaliza aliases de campos ───────────────────────────────
        $data = $this->normalizeFields($request->all());

        // ── Validação base ────────────────────────────────────────────
        $rules = [
            'name'        => 'required|string|max:200',
            'phone'       => 'required|string|max:30',
            'email'       => 'nullable|email|max:200',
            'notes'       => 'nullable|string|max:1000',
            'pipeline_id' => 'nullable|integer|exists:crm_pipelines,id',
            'stage_id'    => 'nullable|integer|exists:crm_stages,id',
        ];

        // Adiciona validação dinâmica para campos personalizados (pela key canônica)
        // Campos numéricos (currency, number) aceitam string ou numeric; demais só string.
        $customFields = CrmCustomField::all()->keyBy('key');
        foreach ($customFields as $key => $field) {
            $numericTypes = ['currency', 'number'];
            $typeRule     = in_array($field->type, $numericTypes) ? 'numeric' : 'string|max:500';
            $rules[$key]  = $field->is_required ? "required|{$typeRule}" : "nullable|{$typeRule}";
        }

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            Log::warning('API /leads validação falhou', [
                'errors'  => $validator->errors()->toArray(),
                'payload' => $data,
            ]);
            return response()->json([
                'error'   => 'Dados inválidos.',
                'details' => $validator->errors(),
            ], 422);
        }

        // ── Resolve pipeline e etapa ──────────────────────────────────

        $pipelineId = $data['pipeline_id']
            ?? $apiToken->default_pipeline_id
            ?? CrmPipeline::active()->orderBy('sort_order')->value('id');

        if (!$pipelineId) {
            return response()->json(['error' => 'Nenhum pipeline disponível.'], 422);
        }

        $pipeline = CrmPipeline::find($pipelineId);
        $stageId  = $data['stage_id']
            ?? $apiToken->default_stage_id
            ?? CrmStage::where('pipeline_id', $pipelineId)->orderBy('sort_order')->value('id');

        // Mapeia tipo_vaga para etapa quando não vem stage_id explícito
        if (empty($data['stage_id'])) {
            $tipoVaga = $data['tipo_vaga'] ?? null;
            if ($tipoVaga && !str_contains(mb_strtolower($tipoVaga), 'simulação') && !str_contains(mb_strtolower($tipoVaga), 'lead')) {
                // Tipo diferente de simulação/lead = reserva confirmada → busca etapa "Reservado"
                $reservadoStage = CrmStage::where('pipeline_id', $pipelineId)
                    ->where('name', 'like', '%reserv%')
                    ->value('id');
                if ($reservadoStage) {
                    $stageId = $reservadoStage;
                }
            }
        }

        $stage = $stageId ? CrmStage::find($stageId) : null;
        if (!$stage) {
            return response()->json(['error' => 'Nenhuma etapa encontrada no pipeline selecionado.'], 422);
        }

        // ── Normaliza telefone ────────────────────────────────────────
        // Sempre armazena com DDI 55 para coincidir com o formato que o Z-API usa
        // nos webhooks de resposta (ex: 5511999999999)

        $phone = preg_replace('/\D/', '', $data['phone']);
        if (strlen($phone) < 8) {
            return response()->json(['error' => 'Telefone inválido: ' . $data['phone']], 422);
        }
        // Adiciona DDI 55 se o número não tiver (10 ou 11 dígitos = BR sem DDI)
        if (strlen($phone) <= 11 && !str_starts_with($phone, '55')) {
            $phone = '55' . $phone;
        }

        // ── Cria ou atualiza contato ──────────────────────────────────

        $contact = Contact::firstOrNew(['phone' => $phone]);
        $contact->name = $data['name'];
        if (!empty($data['email'])) $contact->email = $data['email'];
        if (!empty($data['notes'])) $contact->notes = $data['notes'];
        $contact->save();

        // ── Salva também como Lead (broadcast_contacts) ──────────────
        \App\Models\BroadcastContact::firstOrCreate(
            ['phone' => $phone],
            ['name' => $data['name'], 'tags' => ['site'], 'is_active' => true]
        );

        // ── Cria ou atualiza card ─────────────────────────────────────
        $isUpdate = false;
        $externalId = $data['external_id'] ?? null;

        // Se tem external_id, busca card existente para atualizar
        if ($externalId) {
            $existing = CrmCard::where('external_id', $externalId)->first();
        } else {
            $existing = null;
        }

        if ($existing) {
            // Atualiza card existente (encontrado por external_id)
            $existing->update([
                'title'       => $contact->name,
                'contact_id'  => $contact->id,
            ]);
            $card = $existing;
            $isUpdate = true;

            CrmCardActivity::create([
                'card_id' => $card->id,
                'user_id' => null,
                'type'    => 'note',
                'content' => "Agendamento atualizado via API ({$apiToken->name})",
            ]);
        } else {
            // Sem external_id: lógica anti-duplicata por dia
            // Mesmo dia + mesmo contato → atualiza/move (não duplica)
            // Dias diferentes → cria novo card (nova jornada)
            if (!$externalId) {
                $firstStageId = CrmStage::where('pipeline_id', $pipelineId)->orderBy('sort_order')->value('id');
                $isFirstStage = $stageId == $firstStageId;

                // Busca card do mesmo contato criado HOJE no pipeline
                $todayCard = CrmCard::where('contact_id', $contact->id)
                    ->where('pipeline_id', $pipelineId)
                    ->whereDate('created_at', today())
                    ->latest()
                    ->first();

                if ($isFirstStage) {
                    // Simulação (etapa Novo): se já simulou hoje, atualiza. Se não, cria novo.
                    if ($todayCard && $todayCard->stage_id == $firstStageId) {
                        $todayCard->update(['title' => $contact->name]);
                        $card = $todayCard;
                        $isUpdate = true;
                        CrmCardActivity::create([
                            'card_id' => $card->id,
                            'user_id' => null,
                            'type'    => 'note',
                            'content' => "Simulação atualizada via API ({$apiToken->name})",
                        ]);
                    }
                    // Se não tem card hoje → cria novo (lá embaixo)
                } else {
                    // Reserva ou etapa avançada: busca card de hoje ou o mais recente
                    $existingCard = $todayCard ?? CrmCard::where('contact_id', $contact->id)
                        ->where('pipeline_id', $pipelineId)
                        ->latest()
                        ->first();

                    if ($existingCard) {
                        $oldStageName = $existingCard->stage?->name ?? '?';
                        $moved = $existingCard->stage_id !== $stageId;
                        $existingCard->update(['title' => $contact->name, 'stage_id' => $stageId]);
                        $card = $existingCard;
                        $isUpdate = true;

                        CrmCardActivity::create([
                            'card_id' => $card->id,
                            'user_id' => null,
                            'type'    => $moved ? 'stage_change' : 'note',
                            'content' => $moved
                                ? "Lead movido via API ({$apiToken->name}): {$oldStageName} → {$stage->name}"
                                : "Lead atualizado via API ({$apiToken->name})",
                        ]);

                        // Remove outros cards de HOJE do mesmo contato no pipeline
                        $otherCards = CrmCard::where('contact_id', $contact->id)
                            ->where('pipeline_id', $pipelineId)
                            ->where('id', '!=', $existingCard->id)
                            ->whereDate('created_at', today())
                            ->get();

                        foreach ($otherCards as $other) {
                            $other->delete();
                        }

                        if ($otherCards->count() > 0) {
                            Log::info('API /leads: removidos cards duplicados do dia', [
                                'contact' => $contact->name,
                                'removed' => $otherCards->pluck('id'),
                                'kept'    => $existingCard->id,
                            ]);
                        }
                    }
                }
            }

            // Cria novo card se não encontrou existente
            if (!isset($card)) {
                $card = CrmCard::create([
                    'pipeline_id'  => $pipelineId,
                    'stage_id'     => $stageId,
                    'contact_id'   => $contact->id,
                    'title'        => $contact->name,
                    'external_id'  => $externalId,
                    'sort_order'   => CrmCard::where('stage_id', $stageId)->max('sort_order') + 1,
                ]);

                CrmCardActivity::create([
                    'card_id' => $card->id,
                    'user_id' => null,
                    'type'    => 'note',
                    'content' => "Lead criado via API ({$apiToken->name})",
                ]);
            }
        }

        // ── Converte campos datetime de UTC para timezone local ──────
        foreach ($customFields as $key => $field) {
            if (in_array($field->type, ['datetime']) && !empty($data[$key])) {
                try {
                    $parsed = \Carbon\Carbon::parse($data[$key]);
                    // Se veio com Z (UTC) ou offset, converte para timezone local
                    if (str_contains($data[$key], 'Z') || str_contains($data[$key], '+') || str_contains($data[$key], 'T')) {
                        $parsed = $parsed->setTimezone(config('app.timezone'));
                    }
                    $data[$key] = $parsed->format('Y-m-d H:i:s');
                } catch (\Throwable) {}
            }
        }

        // ── Salva campos personalizados ───────────────────────────────

        // Mapeia valor_da_reserva para o campo correto baseado no tipo de vaga
        if (!empty($data['valor_da_reserva'])) {
            $tipoVaga = mb_strtolower($data['tipo_de_vaga'] ?? '');
            if (str_contains($tipoVaga, 'cobert') || str_contains($tipoVaga, 'sombread')) {
                $data['valor_sombreada'] = $data['valor_sombreada'] ?? $data['valor_da_reserva'];
            } else {
                $data['valor_descoberta'] = $data['valor_descoberta'] ?? $data['valor_da_reserva'];
            }
        }

        // Disponibiliza email e mensagem como campos personalizados também
        if (!empty($data['email']) && !isset($data['email_custom'])) {
            $data['email'] = $data['email']; // já existe
        }
        if (!empty($data['notes'])) {
            $data['mensagem'] = $data['notes']; // alias notes → mensagem para custom field
        }

        foreach ($customFields as $key => $field) {
            if (isset($data[$key]) && $data[$key] !== null && $data[$key] !== '') {
                CrmCardFieldValue::updateOrCreate(
                    ['card_id' => $card->id, 'field_id' => $field->id],
                    ['value'   => $data[$key]]
                );
            }
        }

        // ── Se é update (card movido para etapa avançada), confirma reserva e para IA ──
        if ($isUpdate) {
            $firstStageId = $firstStageId ?? CrmStage::where('pipeline_id', $pipelineId)->orderBy('sort_order')->value('id');
            $isReservation = $stageId != $firstStageId;

            if ($isReservation) {
                // Busca conversa aberta do contato e envia confirmação + resolve
                $openConv = \App\Models\Conversation::where('contact_id', $contact->id)
                    ->where('is_group', false)
                    ->whereIn('status', ['open', 'pending'])
                    ->latest()
                    ->first();

                if ($openConv) {
                    $confirmMsg = "Recebemos sua reserva pelo site! ✅🎉\nSeu estacionamento está confirmado. Qualquer dúvida, estamos à disposição. Boa viagem! ✈️";

                    // Gera variação via IA se possível
                    $automation = Automation::where('is_active', true)->where('ai_greeting', true)->first();
                    if ($automation) {
                        $aiVariation = $this->generateConfirmationMessage($confirmMsg, $contact->name ?? 'cliente');
                        if ($aiVariation) $confirmMsg = $aiVariation;
                    }

                    $msg = \App\Models\Message::create([
                        'conversation_id' => $openConv->id,
                        'sender_type'     => 'agent',
                        'sender_id'       => null,
                        'content'         => $confirmMsg,
                        'type'            => 'text',
                        'delivery_status' => 'pending',
                    ]);
                    $openConv->update(['last_message_at' => now(), 'status' => 'resolved']);
                    \App\Jobs\SendWhatsAppMessage::dispatch($msg);

                    Log::info('API /leads: reserva confirmada, conversa resolvida', [
                        'contact' => $contact->name, 'conv' => $openConv->id,
                    ]);
                }

                return response()->json([
                    'success'     => true,
                    'created'     => false,
                    'updated'     => true,
                    'contact_id'  => $contact->id,
                    'card_id'     => $card->id,
                    'pipeline'    => $pipeline->name,
                    'stage'       => $stage->name,
                    'message'     => "Reserva confirmada em {$pipeline->name} / {$stage->name}.",
                ], 200);
            }

            // Simulação atualizada (mesma etapa) — verifica se já enviou mensagem
            $alreadySent = \App\Models\Conversation::where('contact_id', $contact->id)
                ->whereNotNull('source_automation_id')
                ->whereHas('messages', fn($q) => $q->where('sender_type', 'agent')->whereNull('sender_id'))
                ->exists();

            if ($alreadySent) {
                Log::info('API /leads: card atualizado (mensagem já enviada)', ['card_id' => $card->id]);
                return response()->json([
                    'success' => true, 'created' => false, 'updated' => true,
                    'contact_id' => $contact->id, 'card_id' => $card->id,
                    'pipeline' => $pipeline->name, 'stage' => $stage->name,
                    'message' => "Agendamento atualizado em {$pipeline->name} / {$stage->name}.",
                ], 200);
            }
        }

        $automations = Automation::where('is_active', true)
            ->where(function ($q) use ($pipelineId) {
                $q->whereNull('pipeline_id')
                  ->orWhere('pipeline_id', $pipelineId);
            })
            ->where('trigger', 'lead_created')
            ->get();

        foreach ($automations as $automation) {
            $delay = null;

            // Se o lead tem campo data_hora, calcula disparo 24h antes
            if (!empty($data['data_hora'])) {
                try {
                    $agendamento = \Carbon\Carbon::parse($data['data_hora']);

                    // Agendamento retroativo (data passada) — não envia mensagem
                    if ($agendamento->isPast()) {
                        Log::info('API /leads: agendamento retroativo, sem disparo', [
                            'agendamento' => $agendamento->format('d/m/Y H:i'),
                        ]);
                        continue; // pula esta automação
                    }

                    $disparo = $agendamento->copy()->subHours(24);
                    if ($disparo->isFuture()) {
                        $delay = $disparo;
                        Log::info('API /leads: disparo 24h antes', [
                            'agendamento' => $agendamento->format('d/m/Y H:i'),
                            'disparo'     => $disparo->format('d/m/Y H:i'),
                        ]);
                    }
                    // Se agendamento em menos de 24h (mas futuro), envia imediatamente
                } catch (\Throwable $e) {
                    Log::warning('API /leads: erro ao calcular delay', ['error' => $e->getMessage()]);
                }
            }

            // Se não tem delay calculado por data_hora, usa delay_minutes da automação
            if (!$delay && $automation->delay_minutes > 0) {
                $delay = now()->addMinutes($automation->delay_minutes);
            }

            SendAutomationMessage::dispatch($automation, $contact, $card)->delay($delay);

            // Follow-up é agendado dentro do SendAutomationMessage após criar a conversa
        }

        Log::info('API /leads processado com sucesso', [
            'contact_id' => $contact->id,
            'card_id'    => $card->id,
            'pipeline'   => $pipeline->name,
            'stage'      => $stage->name,
        ]);

        return response()->json([
            'success'     => true,
            'created'     => true,
            'contact_id'  => $contact->id,
            'card_id'     => $card->id,
            'pipeline'    => $pipeline->name,
            'stage'       => $stage->name,
            'message'     => "Lead criado em {$pipeline->name} / {$stage->name}.",
        ], 201);
    }

    /**
     * Normaliza aliases de campos para as chaves canônicas esperadas.
     * Permite que o site externo use nomes de campo diferentes sem precisar alterar o código.
     */
    private function generateConfirmationMessage(string $baseText, string $contactName): ?string
    {
        $apiKey = \App\Models\GlobalSetting::get('gemini_api_key');
        $model  = \App\Models\GlobalSetting::get('gemini_model', 'gemini-2.0-flash');
        if (!$apiKey) return null;

        $charCount = mb_strlen($baseText);
        $prompt = "Reescreva a mensagem abaixo para WhatsApp com variações naturais.\n\n"
            . "MENSAGEM ORIGINAL ({$charCount} chars):\n---\n{$baseText}\n---\n\n"
            . "Nome do cliente: {$contactName}\n\n"
            . "REGRAS: Reescreva INTEIRA, mesmo tamanho, varie palavras/emojis, mantenha dados. Responda APENAS com a mensagem.";

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
            $response = \Illuminate\Support\Facades\Http::timeout(15)->post($url, [
                'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 1.0, 'maxOutputTokens' => 4096],
            ]);
            $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? null;
            return $text ? trim($text) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeFields(array $data): array
    {
        // Aliases de campos base
        $baseAliases = [
            'nome'        => 'name',
            'whatsapp'    => 'phone',
            'fone'        => 'phone',
            'telefone'    => 'phone',
            'celular'     => 'phone',
            'observacoes' => 'notes',
            'observação'  => 'notes',
            'mensagem'       => 'notes',
            'message'        => 'notes',
            'duvida'         => 'notes',
            'msg'            => 'notes',
            'agendamento_id' => 'external_id',
            'id_externo'     => 'external_id',
            'booking_id'     => 'external_id',
        ];

        foreach ($baseAliases as $alias => $canonical) {
            if (isset($data[$alias]) && !isset($data[$canonical])) {
                $data[$canonical] = $data[$alias];
            }
        }

        // Aliases de campos personalizados
        $fieldAliases = [
            'data_entrada'    => 'data_de_entrada',
            'data_saida'      => 'data_de_saida',
            'valor_reserva'   => 'valor_da_reserva',
            'valor'           => 'valor_da_reserva',   // alias curto enviado pelo site
            'horario_entrada' => 'data_de_entrada',
            'horario_saida'   => 'data_de_saida',
            'valor_coberta'   => 'valor_sombreada',
            'tipo_vaga'       => 'tipo_de_vaga',
        ];

        foreach ($fieldAliases as $alias => $canonical) {
            if (isset($data[$alias]) && !isset($data[$canonical])) {
                $data[$canonical] = $data[$alias];
            }
        }

        return $data;
    }
}
