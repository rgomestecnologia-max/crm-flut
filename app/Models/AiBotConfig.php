<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class AiBotConfig extends Model
{
    use Auditable, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'is_active',
        'openai_api_key',
        'model',
        'system_prompt',
        'department_routing_prompt',
        'initial_greeting',
        'max_bot_turns',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'max_bot_turns' => 'integer',
    ];

    public static function current(): ?self
    {
        return self::first();
    }

    /**
     * Verifica se a API key Gemini está configurada.
     * A key agora é global (GlobalSetting), não mais por empresa.
     */
    public function hasKey(): bool
    {
        return !empty(GlobalSetting::get('gemini_api_key'));
    }
}
