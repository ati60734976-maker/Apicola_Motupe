<?php

return [
    'paths'                    => ['api/*'],
    'allowed_methods'          => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins'          => ['*'], // Permite peticiones de clientes locales en desarrollo
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['Content-Type', 'Accept', 'Authorization', 'X-Requested-With'],
    'exposed_headers'          => [],
    'max_age'                  => 86400,
    'supports_credentials'     => false,
];