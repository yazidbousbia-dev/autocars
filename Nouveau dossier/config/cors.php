<?php

return [

    'paths' => ['api/*', 'login', 'register', 'logout', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Add your production frontend domain(s) here once deployed
    'allowed_origins' => [
        'http://localhost',
        'http://localhost:80',
        'http://127.0.0.1',
        'http://localhost:3000',
        'http://localhost:5173',
        'null', // allows opening .html files directly via file:// during local dev
    ],

    // Matches any Vercel preview URL for this project — update "your-frontend-name" once you name it
    'allowed_origins_patterns' => [
        '#^https://your-frontend-name.*\.vercel\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];