<?php

return [
    'defaults' => [
        'guard' => 'api',
        'passwords' => 'admins',
    ],

    'guards' => [
        'api' => [
            'driver' => 'jwt',
            'provider' => 'admins',
        ],
    ],

    'providers' => [
        'admins' => [
            'driver' => 'eloquent',
            'model' => \App\Models\Admin::class
        ]
    ]
];