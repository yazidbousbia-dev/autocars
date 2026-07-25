<?php

return [

    'paths' => ['api/*', 'login', 'register', 'logout', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost',
        'http://localhost:80',
        'http://127.0.0.1',
        'http://localhost:3000',
        'http://localhost:5173',
        'null', // allows opening .html files directly via file:// during local dev
        'https://car-dashboard-pink.vercel.app', // production frontend on Vercel
    ],

    // Matches any preview/production URL for this project on Vercel
    'allowed_origins_patterns' => [
        '#^https://car-dashboard.*\.vercel\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
