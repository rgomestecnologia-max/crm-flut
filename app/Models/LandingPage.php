<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LandingPage extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'title', 'slug', 'description', 'favicon', 'og_image',
        'status', 'custom_domain', 'fb_pixel', 'ga_id', 'custom_css',
        'notification_email', 'thank_you_url', 'flutchat_widget_id', 'views_count',
    ];

    protected static function booted(): void
    {
        static::creating(function ($page) {
            if (!$page->slug) {
                $page->slug = Str::slug($page->title) ?: Str::random(8);
            }
        });
    }

    public function sections()
    {
        return $this->hasMany(LandingPageSection::class)->orderBy('sort_order');
    }

    public function leads()
    {
        return $this->hasMany(LandingPageLead::class);
    }

    public function flutchatWidget()
    {
        return $this->belongsTo(FlutChatWidget::class, 'flutchat_widget_id');
    }

    public function getPublicUrlAttribute(): string
    {
        if ($this->custom_domain) {
            return 'https://' . $this->custom_domain;
        }
        $company = Company::find($this->company_id);
        $companySlug = Str::slug($company?->name ?? 'empresa');
        return url("/lp/{$companySlug}/{$this->slug}");
    }
}
