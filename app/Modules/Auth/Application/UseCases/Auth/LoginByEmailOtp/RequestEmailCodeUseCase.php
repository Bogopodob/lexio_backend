<?php

namespace App\Modules\Auth\Application\UseCases\Auth\LoginByEmailOtp;

use App\Modules\Auth\Application\DTO\RequestEmailCodeResult;
use App\Modules\Auth\Domain\Ports\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\Ports\EmailOtpSenderInterface;
use App\Modules\Auth\Domain\Ports\OtpCodeStoreInterface;
use App\Modules\Auth\Domain\ValueObjects\ProviderId\EmailProviderIdValueObject;
use Random\RandomException;

final readonly class RequestEmailCodeUseCase
{
    public function __construct(
        private AuthUserRepositoryInterface $users,
        private OtpCodeStoreInterface $otpStore,
        private EmailOtpSenderInterface $emailOtpSender,
    ) {}

    /**
     * @throws RandomException
     */
    public function handle(RequestEmailCodeCommand $command): RequestEmailCodeResult
    {
        $email = EmailProviderIdValueObject::fromString($command->email);
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            $this->users->createWithEmail(name: 'User', email: $email);
        }

        $ttl = (int) config('auth_email.otp_ttl_seconds', (int) config('auth_countries.otp_ttl_seconds', 300));
        $code = (string) random_int(100000, 999999);

        $this->otpStore->put($this->otpKey($email), $code, $ttl);
        $provider = $this->emailOtpSender->sendCode('login', $email, $code, $command->locale);

        return new RequestEmailCodeResult(
            ttl: $ttl,
            debugCode: config('app.debug', false) ? $code : null,
            deliveryChannel: 'email',
            deliveryProvider: $provider,
        );
    }

    private function otpKey(EmailProviderIdValueObject $email): string
    {
        return 'email:'.$email->value();
    }
}
