<?php

return [
    "global" => [
        \Middleware\SessionSetupMiddleware::class,
        \Middleware\CSRFMiddleware::class,
    ],
    "aliases" => [
        "auth" => \Middleware\AuthenticatedMiddleware::class,
        "guest" => \Middleware\GuestMiddleware::class,
        "signature" => \Middleware\SignatureValidationMiddleware::class,
        "verify" => \Middleware\EmailVerifiedMiddleware::class,
    ]
];