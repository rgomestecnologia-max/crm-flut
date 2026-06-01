<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class LandingPageLead extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'landing_page_id', 'data', 'ip', 'user_agent',
        'page_url', 'utm_source', 'utm_medium', 'utm_campaign',
    ];

    protected $casts = ['data' => 'array'];

    public function landingPage()
    {
        return $this->belongsTo(LandingPage::class);
    }
}
