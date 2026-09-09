<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpAbuseProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_code_is_throttled(): void
    {
        config(['auth_rate_limits.otp_request_per_minute' => 3]);
        config(['auth_rate_limits.otp_resend_cooldown_seconds' => 0]);
        config(['auth_rate_limits.otp_max_per_hour' => 100]);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/email/request-code', ['email' => 'spam@example.com'])
                ->assertOk();
        }

        $this->postJson('/api/email/request-code', ['email' => 'spam@example.com'])
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'rate_limited');
    }

    public function test_verify_code_is_throttled(): void
    {
        config(['auth_rate_limits.otp_verify_per_minute' => 3]);
        config(['auth_rate_limits.otp_max_attempts' => 100]);

        $this->postJson('/api/email/request-code', ['email' => 'guess@example.com'])
            ->assertOk();

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/email/verify-code', ['email' => 'guess@example.com', 'code' => '000000'])
                ->assertStatus(422);
        }

        $this->postJson('/api/email/verify-code', ['email' => 'guess@example.com', 'code' => '000000'])
            ->assertStatus(429)
            ->assertJsonPath('error', 'rate_limited');
    }

    public function test_code_burns_after_too_many_wrong_attempts(): void
    {
        config(['auth_rate_limits.otp_max_attempts' => 3]);
        config(['auth_rate_limits.otp_verify_per_minute' => 100]);
        config(['auth_rate_limits.otp_resend_cooldown_seconds' => 0]);

        $code = $this->postJson('/api/email/request-code', ['email' => 'burn@example.com'])
            ->assertOk()
            ->json('data.debug_code');

        $this->assertNotEmpty($code);

        $this->postJson('/api/email/verify-code', ['email' => 'burn@example.com', 'code' => '000001'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'code_invalid');
        $this->postJson('/api/email/verify-code', ['email' => 'burn@example.com', 'code' => '000002'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'code_invalid');
        $this->postJson('/api/email/verify-code', ['email' => 'burn@example.com', 'code' => '000003'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'code_burned');

        // Even the right code is dead now — re-request is required.
        $this->postJson('/api/email/verify-code', ['email' => 'burn@example.com', 'code' => $code])
            ->assertStatus(422)
            ->assertJsonPath('error', 'code_invalid');

        // Fresh code works again.
        $fresh = $this->postJson('/api/email/request-code', ['email' => 'burn@example.com'])
            ->assertOk()
            ->json('data.debug_code');

        $this->postJson('/api/email/verify-code', ['email' => 'burn@example.com', 'code' => $fresh])
            ->assertOk();
    }

    public function test_password_login_is_throttled(): void
    {
        config(['auth_rate_limits.password_per_minute' => 3]);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'wrongpass1']);
        }

        $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'wrongpass1'])
            ->assertStatus(429)
            ->assertJsonPath('error', 'rate_limited');
    }

    public function test_resend_cooldown_blocks_immediate_second_request(): void
    {
        config(['auth_rate_limits.otp_resend_cooldown_seconds' => 60]);
        config(['auth_rate_limits.otp_max_per_hour' => 100]);
        config(['auth_rate_limits.otp_request_per_minute' => 100]);

        $this->postJson('/api/email/request-code', ['email' => 'cooldown@example.com'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['expires_in', 'resend_after']]);

        // Bypass attempt via direct second call (like curl, no frontend timer).
        $this->postJson('/api/email/request-code', ['email' => 'cooldown@example.com'])
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'rate_limited')
            ->assertJsonStructure(['retry_after']);
    }

    public function test_hourly_cap_blocks_mail_bombing(): void
    {
        config(['auth_rate_limits.otp_resend_cooldown_seconds' => 0]);
        config(['auth_rate_limits.otp_max_per_hour' => 2]);
        config(['auth_rate_limits.otp_request_per_minute' => 100]);

        $this->postJson('/api/email/request-code', ['email' => 'hourly@example.com'])->assertOk();
        $this->postJson('/api/email/request-code', ['email' => 'hourly@example.com'])->assertOk();

        $this->postJson('/api/email/request-code', ['email' => 'hourly@example.com'])
            ->assertStatus(429)
            ->assertJsonPath('error', 'rate_limited');
    }
}
