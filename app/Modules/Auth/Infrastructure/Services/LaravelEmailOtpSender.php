<?php

namespace App\Modules\Auth\Infrastructure\Services;

use App\Modules\Auth\Domain\Ports\EmailOtpSenderInterface;
use App\Modules\Auth\Domain\ValueObjects\ProviderId\EmailProviderIdValueObject;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

final class LaravelEmailOtpSender implements EmailOtpSenderInterface
{
    public function sendCode(string $service, EmailProviderIdValueObject $email, string $code, ?string $locale = null): string
    {
        $serviceKey = mb_strtolower(trim($service));
        $servicePath = 'auth_email.services.'.$serviceKey;
        $mailer = trim((string) config($servicePath.'.mailer', config('auth_email.default_mailer', config('mail.default', 'log'))));
        $subject = (string) config($servicePath.'.subject', 'Your login code');
        $fallbackTemplate = (string) config($servicePath.'.body_template', 'Your verification code is :code');
        $fromAddress = trim((string) config($servicePath.'.from.address', config('mail.from.address', 'hello@example.com')));
        $fromName = (string) config($servicePath.'.from.name', config('mail.from.name', config('app.name', 'Laravel')));

        if ($mailer === '') {
            throw new InvalidArgumentException('Email OTP mailer is not configured');
        }

        $language = $this->normalizeLocale($locale);
        $message = Lang::get('auth.otp_code_message', ['code' => $code], $language);

        if ($message === 'auth.otp_code_message') {
            $message = str_replace(':code', $code, $fallbackTemplate);
        }

        Mail::mailer($mailer)
            ->raw($message, static function (Message $mail) use ($email, $subject, $fromAddress, $fromName): void {
                $mail->to($email->value())
                    ->subject($subject)
                    ->from($fromAddress, $fromName);
            });

        return $mailer;
    }

    private function normalizeLocale(?string $locale): ?string
    {
        if (! is_string($locale) || trim($locale) === '') {
            return null;
        }

        $value = mb_strtolower(trim(explode(',', $locale)[0]));
        $parts = preg_split('/[-_]/', $value) ?: [];
        $base = isset($parts[0]) ? trim((string) $parts[0]) : '';

        return $base !== '' ? $base : null;
    }
}
