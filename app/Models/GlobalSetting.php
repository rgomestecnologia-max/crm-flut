<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Configurações globais do sistema (não escopadas por empresa).
 * Tabela key/value simples. Não usa BelongsToCompany.
 */
class GlobalSetting extends Model
{
    protected $primaryKey = 'key';
    public    $incrementing = false;
    protected $keyType      = 'string';

    protected $fillable = ['key', 'value'];

    /**
     * Lê um valor global com cache de 60s pra não bater no banco a cada request.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("global_setting:{$key}", 60, function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    /**
     * Grava um valor global e limpa o cache.
     */
    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("global_setting:{$key}");
    }
}
