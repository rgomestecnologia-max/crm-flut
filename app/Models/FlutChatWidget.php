<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FlutChatWidget extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'public_id', 'title', 'subtitle',
        'color', 'logo_url', 'position', 'whatsapp_number',
        'whatsapp_message', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function ($widget) {
            if (!$widget->public_id) {
                $widget->public_id = (string) Str::uuid();
            }
        });
    }

    public function flows()
    {
        return $this->hasMany(FlutChatFlow::class, 'widget_id');
    }

    public function activeFlow()
    {
        return $this->hasOne(FlutChatFlow::class, 'widget_id')->where('is_active', true);
    }

    public function leads()
    {
        return $this->hasMany(FlutChatLead::class, 'widget_id');
    }
}
