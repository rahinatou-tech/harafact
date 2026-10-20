<?php

return [

    'defaults' => [
        'guard' => 'utilisateur', // ← change 'web' en 'utilisateur'
        'passwords' => 'utilisateurs',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // ✅ AJOUTER
    'api' => [
        'driver'   => 'session',
        'provider' => 'utilisateurs',
    ],
        // ← Ajoute ce guard
        'utilisateur' => [
            'driver'   => 'session',
            'provider' => 'utilisateurs',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Utilisateur::class,
        ],

        // ← Ajoute ce provider
        'utilisateurs' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Utilisateur::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],

        // ← Ajoute ça
        'utilisateurs' => [
            'provider' => 'utilisateurs',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];