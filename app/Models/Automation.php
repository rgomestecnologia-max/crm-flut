<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Automation extends Model
{
    use Auditable, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'pipeline_id',
        'trigger',
        'message_template',
        'is_active',
        'delay_minutes',
        'enable_ai_on_reply',
        'ai_first_response',
        'move_on_reply_from_stage_id',
        'move_on_reply_to_stage_id',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'delay_minutes'      => 'integer',
        'enable_ai_on_reply' => 'boolean',
        'ai_first_response'  => 'boolean',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(CrmPipeline::class);
    }

    /**
     * Replace template variables with actual values.
     *
     * Available variables: {nome}, {telefone}, {email}, {pipeline}, {etapa}, {data}
     * Plus any custom field key wrapped in {braces}.
     */
    public function renderMessage(Contact $contact, CrmCard $card): string
    {
        $pipeline = $card->pipeline?->name ?? '';
        $stage    = $card->stage?->name    ?? '';

        $vars = [
            '{nome}'      => $contact->name     ?? '',
            '{telefone}'  => $contact->phone     ?? '',
            '{email}'     => $contact->email     ?? '',
            '{pipeline}'  => $pipeline,
            '{etapa}'     => $stage,
            '{data}'      => now()->format('d/m/Y'),
        ];

        // Custom field values: {field_key}
        foreach ($card->fieldValues as $fv) {
            $vars['{' . $fv->field->key . '}'] = $fv->value ?? '';
        }

        return str_replace(array_keys($vars), array_values($vars), $this->message_template);
    }
}
