<?php

namespace App\Modules\Auth\Application\UseCases\Auth\LoginByEmailOtp;

use App\Modules\Auth\Application\DTO\AuthResult;
use App\Modules\Auth\Application\Exceptions\InvalidCredentialsException;
use App\Modules\Auth\Application\Exceptions\InvalidOtpCodeException;
use App\Modules\Auth\Domain\Ports\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\Ports\OtpCodeStoreInterface;
use App\Modules\Auth\Domain\Ports\TokenIssuerInterface;
use App\Modules\Auth\Domain\ValueObjects\ProviderId\EmailProviderIdValueObject;

final readonly class VerifyEmailCodeUseCase
{
    public function __construct(
        private OtpCodeStoreInterface $otpStore,
        private AuthUserRepositoryInterface $users,
        private TokenIssuerInterface $tokenIssuer,
    ) {}

    public function handle(VerifyEmailCodeCommand $command): AuthResult
    {
        $email = EmailProviderIdValueObject::fromString($command->email);

        $valid = $this->otpStore->verifyAndForget($this->otpKey($email), $command->code);
        if (! $valid) {
            throw new InvalidOtpCodeException('Invalid or expired OTP code');
        }

        $user = $this->users->findByEmail($email);
        if ($user === null) {
            throw new InvalidCredentialsException('User is not registered');
        }

        $token = $this->tokenIssuer->issue($user);

        return new AuthResult(
            token: $token['token'],
            expiresAt: $token['expires_at'],
            expiresIn: $token['expires_in'],
            user: $user,
        );
    }

    private function otpKey(EmailProviderIdValueObject $email): string
    {
        return 'email:'.$email->value();
    }
}
