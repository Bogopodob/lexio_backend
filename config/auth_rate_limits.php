<?php

return [
    // Requests per minute, keyed by IP + email.
    'otp_request_per_minute' => (int) env('AUTH_RL_OTP_REQUEST', 5),
    'otp_verify_per_minute' => (int) env('AUTH_RL_OTP_VERIFY', 10),
    'password_per_minute' => (int) env('AUTH_RL_PASSWORD', 10),

    // Wrong OTP guesses before the code burns and a re-request is needed.
    'otp_max_attempts' => (int) env('AUTH_OTP_MAX_ATTEMPTS', 5),
];
