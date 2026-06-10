<?php

namespace App\Services;

use App\Models\EvolutionApiConfig;
use App\Models\MetaWhatsAppConfig;

class WhatsAppProvider
{
    /**
     * Retorna o service de WhatsApp ativo para a empresa atual.
     * Aceita config específico para multi-instância.
     */
    public static function service(?EvolutionApiConfig $specificConfig = null): EvolutionApiService|MetaWhatsAppService|null
    {
        // Se passou config específico (multi-instância), usa direto
        if ($specificConfig && $specificConfig->is_active) {
            return new EvolutionApiService($specificConfig);
        }

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

    /**
     * Retorna o service para disparos em massa (usa broadcast_provider da empresa).
     */
    public static function broadcastService(): EvolutionApiService|MetaWhatsAppService|null
    {
        $company = app(CurrentCompany::class)->model();
        $provider = $company?->broadcast_provider ?? 'evolution';

        if ($provider === 'meta') {
            $config = MetaWhatsAppConfig::current();
            return ($config && $config->is_active) ? new MetaWhatsAppService($config) : null;
        }

        $config = EvolutionApiConfig::current();
        return ($config && $config->is_active) ? new EvolutionApiService($config) : null;
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
