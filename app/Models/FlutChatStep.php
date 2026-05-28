<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class FlutChatStep extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'flow_id', 'type', 'content', 'input_key',
        'input_placeholder', 'options', 'next_step_id',
        'action_type', 'action_value', 'sort_order',
    ];

    protected $casts = ['options' => 'array'];

    public function flow()
    {
        return $this->belongsTo(FlutChatFlow::class, 'flow_id');
    }

    public function nextStep()
    {
        return $this->belongsTo(self::class, 'next_step_id');
    }
}
