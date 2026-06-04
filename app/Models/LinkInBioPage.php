<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LinkInBioPage extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'title', 'slug', 'status', 'bio_text', 'avatar_url',
        'theme', 'custom_css', 'custom_domain', 'fb_pixel', 'ga_id',
        'views_count', 'created_by',
    ];

    protected $casts = [
        'theme' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $base = Str::slug($model->title);
                $slug = $base;
                $i = 1;
                while (self::withoutGlobalScopes()->where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $model->slug = $slug;
            }
        });
    }

    public function links()
    {
        return $this->hasMany(LinkInBioLink::class, 'page_id')->orderBy('sort_order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPublicUrlAttribute(): string
    {
        $company = $this->company ?? Company::withoutGlobalScopes()->find($this->company_id);
        $companySlug = Str::slug($company->name ?? 'empresa');
        return url("/bio/{$companySlug}/{$this->slug}");
    }

    public const THEMES = [
        'minimal' => [
            'name' => 'Minimal',
            'bg_color' => '#ffffff', 'bg_gradient' => null,
            'text_color' => '#1a1a1a', 'button_bg' => 'transparent', 'button_text' => '#1a1a1a',
            'button_radius' => '8px', 'button_border' => '2px solid #1a1a1a',
            'font_family' => 'Inter, sans-serif', 'avatar_border' => '3px solid #1a1a1a',
        ],
        'dark' => [
            'name' => 'Dark Flut',
            'bg_color' => '#0b0f1c', 'bg_gradient' => null,
            'text_color' => '#ffffff', 'button_bg' => '#b2ff00', 'button_text' => '#111111',
            'button_radius' => '12px', 'button_border' => 'none',
            'font_family' => 'Inter, sans-serif', 'avatar_border' => '3px solid #b2ff00',
        ],
        'gradient' => [
            'name' => 'Gradient',
            'bg_color' => '#667eea', 'bg_gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'text_color' => '#ffffff', 'button_bg' => 'rgba(255,255,255,0.95)', 'button_text' => '#4a3f8a',
            'button_radius' => '50px', 'button_border' => 'none',
            'font_family' => 'Inter, sans-serif', 'avatar_border' => '3px solid rgba(255,255,255,0.8)',
        ],
        'neon' => [
            'name' => 'Neon',
            'bg_color' => '#0a0a0a', 'bg_gradient' => null,
            'text_color' => '#ffffff', 'button_bg' => 'transparent', 'button_text' => '#00ff88',
            'button_radius' => '8px', 'button_border' => '2px solid #00ff88',
            'font_family' => 'Space Grotesk, sans-serif', 'avatar_border' => '3px solid #00ff88',
        ],
        'corporate' => [
            'name' => 'Corporate',
            'bg_color' => '#1e293b', 'bg_gradient' => null,
            'text_color' => '#f1f5f9', 'button_bg' => '#3b82f6', 'button_text' => '#ffffff',
            'button_radius' => '6px', 'button_border' => 'none',
            'font_family' => 'Georgia, serif', 'avatar_border' => '3px solid #3b82f6',
        ],
        'pastel' => [
            'name' => 'Pastel',
            'bg_color' => '#fce4ec', 'bg_gradient' => 'linear-gradient(180deg, #fce4ec 0%, #f3e5f5 100%)',
            'text_color' => '#4a3728', 'button_bg' => 'rgba(255,255,255,0.8)', 'button_text' => '#6d4c5e',
            'button_radius' => '50px', 'button_border' => 'none',
            'font_family' => 'Quicksand, sans-serif', 'avatar_border' => '3px solid #e91e63',
        ],
        'nature' => [
            'name' => 'Nature',
            'bg_color' => '#1b3a2d', 'bg_gradient' => 'linear-gradient(180deg, #1b3a2d 0%, #2d5016 100%)',
            'text_color' => '#e8f5e9', 'button_bg' => '#4caf50', 'button_text' => '#ffffff',
            'button_radius' => '10px', 'button_border' => 'none',
            'font_family' => 'Inter, sans-serif', 'avatar_border' => '3px solid #66bb6a',
        ],
        'ocean' => [
            'name' => 'Ocean',
            'bg_color' => '#0d47a1', 'bg_gradient' => 'linear-gradient(180deg, #0d47a1 0%, #006064 100%)',
            'text_color' => '#e0f7fa', 'button_bg' => 'rgba(255,255,255,0.15)', 'button_text' => '#ffffff',
            'button_radius' => '50px', 'button_border' => '1px solid rgba(255,255,255,0.3)',
            'font_family' => 'Inter, sans-serif', 'avatar_border' => '3px solid rgba(255,255,255,0.6)',
        ],
    ];
}
