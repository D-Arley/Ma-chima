<?php

return [
    'initial_users' => [
        [
            'name' => 'Administrador',
            'email' => env('MACHIMA_ADMIN_EMAIL', 'admin@machima.test'),
            'role' => 'admin',
            'password' => env('MACHIMA_ADMIN_PASSWORD'),
        ],
        [
            'name' => 'Vendedor',
            'email' => env('MACHIMA_VENDEDOR_EMAIL', 'vendedor@machima.test'),
            'role' => 'vendedor',
            'password' => env('MACHIMA_VENDEDOR_PASSWORD'),
        ],
        [
            'name' => 'Bodeguero',
            'email' => env('MACHIMA_BODEGUERO_EMAIL', 'bodeguero@machima.test'),
            'role' => 'bodeguero',
            'password' => env('MACHIMA_BODEGUERO_PASSWORD'),
        ],
    ],
];
