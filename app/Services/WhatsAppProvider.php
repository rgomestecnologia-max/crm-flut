<?php

namespace App\Services;

use App\Models\EvolutionApiConfig;
use App\Models\MetaWhatsAppConfig;
use App\Models\ZapiConfig;

class WhatsAppProvider
{
    /**
     * Retorna o service de WhatsApp ativo para a empresa atual.
     * Aceita config específico para multi-instância (Evolution).
     */
    public static function service(?EvolutionApiConfig $specificConfig = null): EvolutionApiService|MetaWhatsAppService|ZapiService|null
    {
        // Se passou config específico (multi-instância), verifica api_provider
        if ($specificConfig && $specificConfig->is_active) {
            if (($specificConfig->api_provider ?? 'evolution') === 'zapi') {
                $zapiConfig = ZapiConfig::active();
                return $zapiConfig ? new ZapiService($zapiConfig) : new EvolutionApiService($specificConfig);
            }
            return new EvolutionApiService($specificConfig);
        }

        $provider = static::currentProvider();

        if ($provider === 'meta') {
            $config = MetaWhatsAppConfig::current();
            return ($config && $config->is_active) ? new MetaWhatsAppService($config) : null;
        }

        if ($provider === 'zapi') {
            $config = ZapiConfig::active();
            return $config ? new ZapiService($config) : null;
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
    public static function broadcastService(): EvolutionApiService|MetaWhatsAppService|ZapiService|null
    {
        $company = app(CurrentCompany::class)->model();
        $provider = $company?->broadcast_provider ?? 'evolution';

        if ($provider === 'meta') {
            $config = MetaWhatsAppConfig::current();
            return ($config && $config->is_active) ? new MetaWhatsAppService($config) : null;
        }

        if ($provider === 'zapi') {
            $config = ZapiConfig::active();
            return $config ? new ZapiService($config) : null;
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

    public static function isZapi(): bool
    {
        return static::currentProvider() === 'zapi';
    }
}
