<?php

return [
    'paths'                    => ['api/*', 'livewire/*'],
    'allowed_methods'          => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_origins'          => [
        'https://crm.flut.com.br',
        'https://homologacao.flut.com.br',
        'http://localhost:8000',
        'http://localhost:8001',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['Content-Type', 'Authorization', 'X-CSRF-TOKEN', 'X-Requested-With'],
    'exposed_headers'          => [],
    'max_age'                  => 3600,
    'supports_credentials'     => true,
];
