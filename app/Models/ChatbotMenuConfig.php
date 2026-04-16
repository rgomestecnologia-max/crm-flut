<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ChatbotMenuConfig extends Model
{
    use Auditable, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'is_active',
        'reply_in_groups',
        'company_name',
        'welcome_template',
        'menu_prompt',
        'invalid_option_message',
        'after_selection_message',
        'business_hours_enabled',
        'business_hours',
        'outside_hours_message',
    ];

    protected $casts = [
        'is_active'              => 'boolean',
        'reply_in_groups'        => 'boolean',
        'business_hours_enabled' => 'boolean',
        'business_hours'         => 'array',
    ];

    public static function current(): ?self
    {
        return self::first();
    }

    /**
     * Verifica se o horário atual está dentro do expediente configurado.
     * Retorna true se business_hours não está habilitado (sem restrição).
     */
    public function isWithinBusinessHours(): bool
    {
        if (!$this->business_hours_enabled || empty($this->business_hours)) {
            return true;
        }

        $now     = now()->setTimezone('America/Sao_Paulo');
        $dayMap  = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
        $today   = $now->dayOfWeek; // 0=dom, 6=sab

        foreach ($this->business_hours as $day => $config) {
            if (($dayMap[$day] ?? -1) !== $today) continue;
            if (empty($config['active'])) return false;

            $start = $config['start'] ?? '08:00';
            $end   = $config['end']   ?? '18:00';
            $time  = $now->format('H:i');

            return $time >= $start && $time <= $end;
        }

        return false; // Dia não configurado = fora do expediente
    }
}
