<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class FlutChatFlow extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'widget_id', 'name', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function widget()
    {
        return $this->belongsTo(FlutChatWidget::class, 'widget_id');
    }

    public function steps()
    {
        return $this->hasMany(FlutChatStep::class, 'flow_id')->orderBy('sort_order');
    }

    public function firstStep()
    {
        return $this->hasOne(FlutChatStep::class, 'flow_id')->orderBy('sort_order');
    }
}
