<?php

return [
    'paths' => ['api/*', 'login', 'register', 'logout', 'trips', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'], // Permitir todos los métodos HTTP
    'allowed_origins' => ['https://cosmoviajes.netlify.app, http://localhost:4200/'], // Solo un origen permitido
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'], // Permitir todas las cabeceras
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];