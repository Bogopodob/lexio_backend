<?php

namespace App\Modules\Auth\Infrastructure\Security;

use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Auth\Domain\Ports\TokenIssuerInterface;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\RegisteredClaims;

final class JwtTokenIssuer implements TokenIssuerInterface
{
    private Configuration $jwt;

    public function __construct()
    {
        $key = (string) config('app.key');
        $cleanKey = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) ?: $key : $key;

        $this->jwt = Configuration::forSymmetricSigner(
            new Sha256,
            InMemory::plainText($cleanKey),
        );
    }

    public function issue(AuthUser $user): array
    {
        $now = new DateTimeImmutable('@'.time());
        $now = $now->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $ttl = (int) config('auth_countries.jwt_ttl_seconds', 3600);
        $expiresAt = $now->modify('+'.$ttl.' seconds');

        $token = $this->jwt->builder()
            ->issuedBy((string) config('app.url'))
            ->permittedFor((string) config('app.url'))
            ->relatedTo((string) $user->id)
            ->identifiedBy(bin2hex(random_bytes(16)))
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($expiresAt)
            ->withClaim('email', (string) $user->email)
            ->withClaim('name', $user->name)
            ->getToken($this->jwt->signer(), $this->jwt->signingKey());

        return [
            'token' => $token->toString(),
            'expires_at' => $expiresAt,
            'expires_in' => max(0, $expiresAt->getTimestamp() - $now->getTimestamp()),
            RegisteredClaims::SUBJECT => (string) $user->id,
        ];
    }
}
