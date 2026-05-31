<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class MetaWhatsAppConfig extends Model
{
    use Auditable, BelongsToCompany;

    protected $table = 'meta_whatsapp_configs';

    protected $fillable = [
        'company_id',
        'phone_number_id',
        'whatsapp_business_account_id',
        'access_token',
        'verify_token',
        'phone_display',
        'page_id',
        'page_access_token',
        'instagram_account_id',
        'is_active',
        'messenger_enabled',
        'instagram_enabled',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'access_token',
    ];

    public static function current(): ?self
    {
        return self::first();
    }
}
