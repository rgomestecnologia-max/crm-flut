<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CrmCustomField extends Model
{
    use Auditable, BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'key', 'type', 'is_required', 'sort_order'];

    protected $casts = ['is_required' => 'boolean'];

    public function values(): HasMany
    {
        return $this->hasMany(CrmCardFieldValue::class, 'field_id');
    }

    public static function generateKey(string $name): string
    {
        return Str::slug($name, '_');
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'text'     => 'Texto',
            'textarea' => 'Texto longo',
            'number'   => 'Número',
            'currency' => 'Valor (R$)',
            'date'     => 'Data',
            'time'     => 'Horário',
            'datetime' => 'Data + Horário',
            'email'    => 'E-mail',
            'phone'    => 'Telefone',
            'url'      => 'Link (URL)',
            default    => $this->type,
        };
    }
}
