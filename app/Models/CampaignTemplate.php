<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class CampaignTemplate extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'channel', 'message', 'subject',
        'image_path', 'ai_prompt', 'html_content',
        'header_color', 'logo_path', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeWhatsapp($query)
    {
        return $query->where('channel', 'whatsapp');
    }

    public function scopeEmail($query)
    {
        return $query->where('channel', 'email');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getImageUrl(): ?string
    {
        return $this->image_path ? \App\Services\MediaStorage::url($this->image_path) : null;
    }

    public function getLogoUrl(): ?string
    {
        return $this->logo_path ? \App\Services\MediaStorage::url($this->logo_path) : null;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
