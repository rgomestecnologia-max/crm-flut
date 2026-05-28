<?php

return [
    'paths'                    => ['api/*', 'livewire/*'],
    'allowed_methods'          => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_origins'          => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['Content-Type', 'Authorization', 'X-CSRF-TOKEN', 'X-Requested-With'],
    'exposed_headers'          => [],
    'max_age'                  => 3600,
    'supports_credentials'     => false,
];
