<?php

namespace App\Services;

use App\Models\EvolutionApiConfig;
use App\Models\MetaWhatsAppConfig;

class WhatsAppProvider
{
    /**
     * Retorna o service de WhatsApp ativo para a empresa atual.
     */
    public static function service(): EvolutionApiService|MetaWhatsAppService|null
    {
        $provider = static::currentProvider();

        if ($provider === 'meta') {
            $config = MetaWhatsAppConfig::current();
            return ($config && $config->is_active) ? new MetaWhatsAppService($config) : null;
        }

        $config = EvolutionApiConfig::current();
        return ($config && $config->is_active) ? new EvolutionApiService($config) : null;
    }

    /**
     * Retorna o nome do provider ativo para a empresa atual.
     */
    public static function currentProvider(): string
    {
        $company = app(CurrentCompany::class)->model();
        return $company?->whatsapp_provider ?? 'evolution';
    }

    public static function isMeta(): bool
    {
        return static::currentProvider() === 'meta';
    }

    public static function isEvolution(): bool
    {
        return static::currentProvider() === 'evolution';
    }
}
