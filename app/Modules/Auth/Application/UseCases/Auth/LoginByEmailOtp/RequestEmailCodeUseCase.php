<?php

namespace App\Modules\Auth\Application\UseCases\Auth\LoginByEmailOtp;

use App\Modules\Auth\Application\DTO\RequestEmailCodeResult;
use App\Modules\Auth\Application\Exceptions\TooManyOtpRequestsException;
use App\Modules\Auth\Domain\Ports\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\Ports\EmailOtpSenderInterface;
use App\Modules\Auth\Domain\Ports\OtpCodeStoreInterface;
use App\Modules\Auth\Domain\ValueObjects\ProviderId\EmailProviderIdValueObject;
use Illuminate\Support\Facades\Cache;
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
     * @throws TooManyOtpRequestsException
     */
    public function handle(RequestEmailCodeCommand $command): RequestEmailCodeResult
    {
        $email = EmailProviderIdValueObject::fromString($command->email);

        // --- Anti-spam: server-side resend cooldown + hourly cap per email ---
        // Frontend countdown can be bypassed via curl, so enforce it here.
        // This also closes brute-force reset: each new code resets the guess
        // counter, so without a cooldown an attacker could get infinite guesses.
        $normalized = mb_strtolower(trim($command->email));
        $fingerprint = sha1($normalized);
        $cooldown = max(0, (int) config('auth_rate_limits.otp_resend_cooldown_seconds', 60));
        $maxPerHour = max(1, (int) config('auth_rate_limits.otp_max_per_hour', 10));
        $now = time();

        $cooldownKey = 'auth:otp:cooldown:'.$fingerprint;
        if ($cooldown > 0) {
            $nextAllowedAt = (int) Cache::get($cooldownKey, 0);
            if ($nextAllowedAt > $now) {
                throw new TooManyOtpRequestsException(
                    message: 'Too many code requests',
                    retryAfter: $nextAllowedAt - $now,
                );
            }
        }

        $hourBucket = gmdate('YmdH', $now);
        $hourKey = 'auth:otp:hour:'.$fingerprint.':'.$hourBucket;
        $sentThisHour = (int) Cache::get($hourKey, 0);
        if ($sentThisHour >= $maxPerHour) {
            $retryAfter = 3600 - ($now % 3600);
            throw new TooManyOtpRequestsException(
                message: 'Too many code requests',
                retryAfter: max(1, $retryAfter),
            );
        }

        $user = $this->users->findByEmail($email);

        $isNewUser = $user === null;

        if ($isNewUser) {
            $this->users->createWithEmail(name: 'User', email: $email);
        }

        $ttl = (int) config('auth_email.otp_ttl_seconds', (int) config('auth_countries.otp_ttl_seconds', 300));
        $code = (string) random_int(100000, 999999);

        $this->otpStore->put($this->otpKey($email), $code, $ttl);

        // Mark request only after code is stored, so failed sends don't burn quota.
        if ($cooldown > 0) {
            Cache::put($cooldownKey, $now + $cooldown, $cooldown);
        }
        // Hourly counter lives until the end of the current hour bucket.
        $ttlHourKey = max(1, 3600 - ($now % 3600));
        Cache::put($hourKey, $sentThisHour + 1, $ttlHourKey);

        $provider = $this->emailOtpSender->sendCode('login', $email, $code, $command->locale, $isNewUser);

        return new RequestEmailCodeResult(
            ttl: $ttl,
            debugCode: config('app.debug', false) ? $code : null,
            deliveryChannel: 'email',
            deliveryProvider: $provider,
            resendAfter: $cooldown,
        );
    }

    private function otpKey(EmailProviderIdValueObject $email): string
    {
        return 'email:'.$email->value();
    }
}
