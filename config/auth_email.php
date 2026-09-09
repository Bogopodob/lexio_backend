<?php

return [
    // How long a one-time email code lives, in seconds.
    'otp_ttl_seconds' => (int) env('AUTH_OTP_TTL_SECONDS', 300),

    // Default mailer for OTP letters (login service).
    'default_mailer' => env('MAIL_MAILER', 'log'),

    'services' => [
        'login' => [
            'mailer' => env('AUTH_EMAIL_MAILER', env('MAIL_MAILER', 'log')),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Lexio'),
            ],
        ],
    ],
];
