<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmCardFieldValue extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'card_id', 'field_id', 'value'];

    public function card(): BelongsTo
    {
        return $this->belongsTo(CrmCard::class, 'card_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(CrmCustomField::class, 'field_id');
    }
}
