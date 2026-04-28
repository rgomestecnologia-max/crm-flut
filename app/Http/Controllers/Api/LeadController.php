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

        // ── Cria ou move card ─────────────────────────────────────────

        $existing = CrmCard::where('contact_id', $contact->id)->latest()->first();

        if ($existing) {
            $oldPipeline = $existing->pipeline?->name ?? '—';
            $oldStage    = $existing->stage?->name    ?? '—';

            $existing->update(['pipeline_id' => $pipelineId, 'stage_id' => $stageId]);

            CrmCardActivity::create([
                'card_id' => $existing->id,
                'user_id' => null,
                'type'    => 'stage_change',
                'content' => "Atualizado via API: {$oldPipeline}/{$oldStage} → {$pipeline->name}/{$stage->name}",
            ]);

            $card    = $existing->fresh();
            $created = false;
        } else {
            $card = CrmCard::create([
                'pipeline_id' => $pipelineId,
                'stage_id'    => $stageId,
                'contact_id'  => $contact->id,
                'title'       => $contact->name,
                'sort_order'  => CrmCard::where('stage_id', $stageId)->max('sort_order') + 1,
            ]);

            CrmCardActivity::create([
                'card_id' => $card->id,
                'user_id' => null,
                'type'    => 'note',
                'content' => "Lead criado via API ({$apiToken->name})",
            ]);

            $created = true;
        }

        // ── Salva campos personalizados ───────────────────────────────

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

        // ── Dispara automações ────────────────────────────────────────

        $automations = Automation::where('is_active', true)
            ->where(function ($q) use ($pipelineId) {
                $q->whereNull('pipeline_id')
                  ->orWhere('pipeline_id', $pipelineId);
            })
            ->where('trigger', 'lead_created')
            ->get();

        foreach ($automations as $automation) {
            SendAutomationMessage::dispatch($automation, $contact, $card);
        }

        Log::info('API /leads processado com sucesso', [
            'contact_id' => $contact->id,
            'card_id'    => $card->id,
            'created'    => $created,
            'pipeline'   => $pipeline->name,
            'stage'      => $stage->name,
        ]);

        return response()->json([
            'success'    => true,
            'created'    => $created,
            'contact_id' => $contact->id,
            'card_id'    => $card->id,
            'pipeline'   => $pipeline->name,
            'stage'      => $stage->name,
            'message'    => $created
                ? "Lead criado em {$pipeline->name} / {$stage->name}."
                : "Lead atualizado para {$pipeline->name} / {$stage->name}.",
        ], $created ? 201 : 200);
    }

    /**
     * Normaliza aliases de campos para as chaves canônicas esperadas.
     * Permite que o site externo use nomes de campo diferentes sem precisar alterar o código.
     */
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
            'mensagem'    => 'notes',
            'message'     => 'notes',
            'duvida'      => 'notes',
            'msg'         => 'notes',
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
        ];

        foreach ($fieldAliases as $alias => $canonical) {
            if (isset($data[$alias]) && !isset($data[$canonical])) {
                $data[$canonical] = $data[$alias];
            }
        }

        return $data;
    }
}
