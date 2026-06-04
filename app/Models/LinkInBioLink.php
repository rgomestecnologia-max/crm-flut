<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinkInBioLink extends Model
{
    protected $fillable = [
        'page_id', 'sort_order', 'type', 'title', 'url', 'icon',
        'thumbnail_url', 'config', 'clicks_count', 'is_active',
    ];

    protected $casts = [
        'config'    => 'array',
        'is_active' => 'boolean',
    ];

    public function page()
    {
        return $this->belongsTo(LinkInBioPage::class, 'page_id');
    }
}
