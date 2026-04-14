<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmCardActivity extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'card_id', 'user_id', 'type', 'content'];

    public function card(): BelongsTo
    {
        return $this->belongsTo(CrmCard::class, 'card_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
