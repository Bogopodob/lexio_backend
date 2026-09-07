<?php

namespace App\Modules\Auth\Infrastructure\Services;

use App\Modules\Auth\Domain\Ports\EmailOtpSenderInterface;
use App\Modules\Auth\Domain\ValueObjects\ProviderId\EmailProviderIdValueObject;
use App\Modules\Auth\Infrastructure\Mail\AuthCodeMail;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

final class LaravelEmailOtpSender implements EmailOtpSenderInterface
{
    public function sendCode(string $service, EmailProviderIdValueObject $email, string $code, ?string $locale = null, bool $isNewUser = false): string
    {
        $serviceKey = mb_strtolower(trim($service));
        $servicePath = 'auth_email.services.'.$serviceKey;
        $mailer = trim((string) config($servicePath.'.mailer', config('auth_email.default_mailer', config('mail.default', 'log'))));
        $fromAddress = trim((string) config($servicePath.'.from.address', config('mail.from.address', 'hello@example.com')));
        $fromName = (string) config($servicePath.'.from.name', config('mail.from.name', 'Lexio'));

        if ($mailer === '') {
            throw new InvalidArgumentException('Email OTP mailer is not configured');
        }

        if ($fromName === '' || $fromName === 'Laravel') {
            $fromName = 'Lexio';
        }

        $ttl = (int) config('auth_email.otp_ttl_seconds', (int) config('auth_countries.otp_ttl_seconds', 300));

        Mail::mailer($mailer)
            ->to($email->value())
            ->send((new AuthCodeMail($code, $isNewUser, $locale, $ttl))->from($fromAddress, $fromName));

        return $mailer;
    }
}
