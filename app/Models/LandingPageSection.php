<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPageSection extends Model
{
    protected $fillable = ['landing_page_id', 'type', 'sort_order', 'config', 'visible'];

    protected $casts = ['config' => 'array', 'visible' => 'boolean'];

    public function landingPage()
    {
        return $this->belongsTo(LandingPage::class);
    }
}
