<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ChatbotMenuConfig extends Model
{
    use Auditable, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'is_active',
        'company_name',
        'welcome_template',
        'menu_prompt',
        'invalid_option_message',
        'after_selection_message',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function current(): ?self
    {
        return self::first();
    }
}
