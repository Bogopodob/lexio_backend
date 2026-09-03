<?php

namespace App\Modules\Auth\Domain\Ports;

use App\Modules\Auth\Domain\ValueObjects\ProviderId\EmailProviderIdValueObject;

interface EmailOtpSenderInterface
{
    /**
     * Send OTP code via email
     *
     * @return string The mailer name used to send the email
     */
    public function sendCode(string $service, EmailProviderIdValueObject $email, string $code, ?string $locale = null): string;
}
