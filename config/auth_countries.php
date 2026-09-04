<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT access token lifetime
    |--------------------------------------------------------------------------
    |
    | How long issued access tokens stay valid, in seconds.
    | 30 days by default so learning sessions survive; revoked
    | tokens stop working immediately via the denylist.
    |
    */
    'jwt_ttl_seconds' => (int) env('JWT_TTL_SECONDS', 30 * 24 * 60 * 60),
];
