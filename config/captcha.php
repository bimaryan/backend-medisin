<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Captcha Debug Exposure
    |--------------------------------------------------------------------------
    |
    | Ketika diaktifkan DAN environment bukan production, query ?debug=1 pada
    | GET /api/captcha akan mengembalikan captcha_code plaintext untuk
    | keperluan testing manual API. Default: false (aman untuk production).
    |
    */

    'debug_enabled' => env('CAPTCHA_DEBUG_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Captcha Lifetime (seconds)
    |--------------------------------------------------------------------------
    */

    'ttl' => (int) env('CAPTCHA_TTL_SECONDS', 300),

    /*
    |--------------------------------------------------------------------------
    | Bind Captcha Token to Client IP
    |--------------------------------------------------------------------------
    |
    | Token captcha hanya valid untuk verifikasi dari IP yang sama dengan
    | saat token tersebut digenerate. Default: true.
    |
    */

    'bind_ip' => (bool) env('CAPTCHA_BIND_IP', true),
];
