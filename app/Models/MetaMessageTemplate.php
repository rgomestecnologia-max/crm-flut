<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class MetaMessageTemplate extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'template_id',
        'name',
        'language',
        'category',
        'status',
        'components',
    ];

    protected $casts = [
        'components' => 'array',
    ];

    /**
     * Retorna o texto do body do template (para preview).
     */
    public function getBodyTextAttribute(): string
    {
        foreach ($this->components ?? [] as $component) {
            if (($component['type'] ?? '') === 'BODY') {
                return $component['text'] ?? '';
            }
        }
        return '';
    }

    /**
     * Retorna os nomes dos parâmetros do body ({{1}}, {{2}}, etc.).
     */
    public function getBodyParameterCountAttribute(): int
    {
        preg_match_all('/\{\{\d+\}\}/', $this->body_text, $matches);
        return count($matches[0] ?? []);
    }

    /**
     * Apenas templates aprovados.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }
}
