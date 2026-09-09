<?php

return [
    // Requests per minute, keyed by IP + email.
    'otp_request_per_minute' => (int) env('AUTH_RL_OTP_REQUEST', 5),
    'otp_verify_per_minute' => (int) env('AUTH_RL_OTP_VERIFY', 10),
    'password_per_minute' => (int) env('AUTH_RL_PASSWORD', 10),

    // Wrong OTP guesses before the code burns and a re-request is needed.
    'otp_max_attempts' => (int) env('AUTH_OTP_MAX_ATTEMPTS', 5),

    // Server-side resend cooldown: blocks mail-bombing even if the
    // frontend countdown is bypassed (e.g. via curl). Seconds.
    'otp_resend_cooldown_seconds' => (int) env('AUTH_OTP_RESEND_COOLDOWN', 60),

    // Max codes per email per rolling hour. Stops slow-drip spam
    // and brute-force resets (new code resets the guess counter).
    'otp_max_per_hour' => (int) env('AUTH_OTP_MAX_PER_HOUR', 10),
];
