<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingConfig extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::where('key', $key)->value('value');
        return $value !== null ? $value : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => is_array($value) ? json_encode($value) : $value]);
    }

    public static function getAll(): array
    {
        return static::pluck('value', 'key')->toArray();
    }

    public static function defaults(): array
    {
        return [
            // Multi-atendimento
            'multi_base_price'        => '349.90',
            'multi_base_users'        => '3',
            'multi_extra_user'        => '49.00',
            'multi_extra_instance'    => '189.90',
            'multi_setup'             => '600.00',

            // CRM
            'crm_price'               => '349.90',
            'crm_setup'               => '350.00',

            // Disparos Email
            'email_5k_price'          => '200.00',
            'email_20k_price'         => '400.00',
            'email_50k_price'         => '750.00',
            'email_setup'             => '300.00',

            // IA
            'ia_flow_price'           => '499.00',
            'ia_flow_setup'           => '500.00',

            // Integrações
            'integration_setup'       => '800.00',
            'integration_monthly'     => '200.00',
        ];
    }

    public static function seed(): void
    {
        foreach (self::defaults() as $key => $value) {
            self::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
