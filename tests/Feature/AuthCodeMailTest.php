<?php

namespace Tests\Feature;

use App\Modules\Auth\Infrastructure\Mail\AuthCodeMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthCodeMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_email_gets_welcome_letter_with_code(): void
    {
        Mail::fake();

        $this->postJson('/api/email/request-code', ['email' => 'newbie@example.com'])
            ->assertOk()
            ->assertJsonPath('success', true);

        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail): bool {
            return $mail->isNewUser === true
                && mb_strlen($mail->code) === 6
                && str_contains($mail->envelope()->subject, 'Lexio');
        });
    }

    public function test_existing_email_gets_code_only_letter(): void
    {
        config(['auth_rate_limits.otp_resend_cooldown_seconds' => 0]);
        Mail::fake();

        $this->postJson('/api/email/request-code', ['email' => 'regular@example.com'])
            ->assertOk();

        // Second request — user already exists, no welcome block.
        $this->postJson('/api/email/request-code', ['email' => 'regular@example.com'])
            ->assertOk();

        Mail::assertSentCount(2, AuthCodeMail::class);
        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail): bool {
            return $mail->isNewUser === false;
        });
    }

    public function test_letter_renders_html_with_digits(): void
    {
        $mail = new AuthCodeMail('482913', true, 'ru', 300);

        $html = $mail->render();

        $this->assertStringContainsString('Добро пожаловать в Lexio', $html);
        $this->assertStringContainsString('4', $html);
        $this->assertStringContainsString('8', $html);
        $this->assertStringContainsString('Что тебя ждёт', $html);

        $login = new AuthCodeMail('123456', false, 'en', 300);
        $loginHtml = $login->render();

        $this->assertStringContainsString('Welcome back', $loginHtml);
        $this->assertStringNotContainsString('What awaits you', $loginHtml);
    }
}
