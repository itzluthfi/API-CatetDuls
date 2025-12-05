<?php

return [
    // Path yang akan diproteksi CORS
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Semua HTTP methods diizinkan
    'allowed_methods' => ['*'],

    // Izinkan semua origin (untuk development)
    // Untuk production, ganti dengan domain spesifik
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    // Izinkan semua headers
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    // Cache preflight request selama 1 jam
    'max_age' => 3600,

    // Set false untuk API token authentication
    // Set true jika pakai cookie-based auth
    'supports_credentials' => false,
];
