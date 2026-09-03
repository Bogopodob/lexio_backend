<?php

namespace App\Modules\Auth\Infrastructure\Security;

use App\Modules\Auth\Domain\Entities\VerifiedAccessToken;
use App\Modules\Auth\Domain\Ports\AccessTokenVerifierInterface;
use App\Shared\Laravel\Infrastructure\Security\Jwt\Contracts\RevokedTokenStoreInterface;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

final class JwtAccessTokenVerifier implements AccessTokenVerifierInterface
{
    private Configuration $jwt;

    public function __construct(
        private readonly ?RevokedTokenStoreInterface $revokedTokens = null,
    ) {
        $key = (string) config('app.key');
        $cleanKey = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) ?: $key : $key;

        $this->jwt = Configuration::forSymmetricSigner(
            new Sha256,
            InMemory::plainText($cleanKey),
        );
    }

    public function verify(string $token): ?VerifiedAccessToken
    {
        try {
            $parsedToken = $this->jwt->parser()->parse($token);
            if (! $parsedToken instanceof UnencryptedToken) {
                return null;
            }

            if (! $this->jwt->validator()->validate($parsedToken, new SignedWith($this->jwt->signer(), $this->jwt->verificationKey()))) {
                return null;
            }

            $claims = $parsedToken->claims();
            $subject = trim((string) $claims->get('sub', ''));
            if ($subject === '') {
                return null;
            }

            $expiresAt = $claims->get('exp');
            if (! $expiresAt instanceof DateTimeImmutable) {
                return null;
            }

            if ($expiresAt <= new DateTimeImmutable) {
                return null;
            }

            if ($this->revokedTokens !== null && $this->revokedTokens->isRevoked(hash('sha256', $token))) {
                return null;
            }

            return new VerifiedAccessToken(
                subject: $subject,
                email: $this->nullableString($claims->get('email', null)),
                name: $this->nullableString($claims->get('name', null)),
                expiresAt: $expiresAt,
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);

        return $stringValue === '' ? null : $stringValue;
    }
}
