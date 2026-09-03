<?php

namespace App\Shared\Laravel\Infrastructure\Security\Jwt;

use App\Shared\Core\Domain\ValueObject\Email;
use App\Shared\Laravel\Infrastructure\Security\Jwt\Contracts\TokenGeneratorInterface;
use App\Shared\Laravel\Infrastructure\Security\Jwt\DTO\TokenPayloadDTO;
use App\Shared\Laravel\Infrastructure\Security\Jwt\DTO\TokenResultDTO;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Ramsey\Uuid\Uuid;
use Random\RandomException;

class JwtTokenGenerator implements TokenGeneratorInterface
{
    private Configuration $config;

    private string $issuer;

    private string $audience;

    private int $ttlDays = 30;

    public function __construct()
    {
        $key = InMemory::plainText(config('app.key'));

        $this->config = Configuration::forSymmetricSigner(
            new Sha256,
            $key
        );

        $this->issuer = config('app.url');
        $this->audience = config('app.url');
    }

    /**
     * @throws RandomException
     */
    public function generate(TokenPayloadDTO $payload): TokenResultDTO
    {
        $now = Carbon::now();
        $expiresAt = $now->copy()->addDays($this->ttlDays);

        $token = $this->config->builder()
            ->issuedBy($this->issuer)
            ->permittedFor($this->audience)
            ->identifiedBy(Str::uuid())
            ->issuedAt($now->toDateTimeImmutable())
            ->canOnlyBeUsedAfter($now->toDateTimeImmutable())
            ->expiresAt($expiresAt->toDateTimeImmutable())
            ->relatedTo($payload->id->toString())
            ->withClaim('email', $payload->email->value())
            ->withClaim('abilities', $payload->abilities)
            ->withClaim('ip', $payload->ipAddress)
            ->withClaim('ua', $payload->userAgent)
            ->withClaim('iat_timestamp', $now->timestamp)
            ->getToken($this->config->signer(), $this->config->signingKey());

        return new TokenResultDTO(
            token: $token->toString(),
            issuedAt: $now,
            expiresAt: $expiresAt,
            expiresIn: $now->diffInSeconds($expiresAt),
        );
    }

    public function verify(string $token): ?TokenPayloadDTO
    {
        try {
            $parsedToken = $this->config->parser()->parse($token);

            $constraints = [
                new SignedWith(
                    $this->config->signer(),
                    $this->config->signingKey()
                ),

                new StrictValidAt(SystemClock::fromSystemTimezone()),

                new IssuedBy($this->issuer),

                new PermittedFor($this->audience),
            ];

            if (! $this->config->validator()->validate($parsedToken, ...$constraints)) {
                Log::warning('JWT validation failed', [
                    'token_preview' => substr($token, 0, 30).'...',
                ]);

                return null;
            }

            $claims = $parsedToken->claims();

            $issuedAt = Carbon::parse($claims->get('iat'));
            if ($issuedAt->isFuture()) {
                Log::warning('JWT issued in the future', [
                    'iat' => $issuedAt->toIso8601String(),
                ]);

                return null;
            }

            return new TokenPayloadDTO(
                id: Uuid::fromString($claims->get('sub')),
                email: new Email($claims->get('email')),
                abilities: $claims->get('abilities'),
                ipAddress: $claims->get('ip'),
                userAgent: $claims->get('ua'),
                issuedAt: $issuedAt
            );

        } catch (\InvalidArgumentException $e) {
            Log::error('JWT parsing error', [
                'error' => $e->getMessage(),
                'token_preview' => substr($token, 0, 30).'...',
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::error('JWT verification error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return null;
        }
    }

    public function revoke(string $token): void
    {
        // TODO: Реализация через Redis blacklist
        // Для JWT это опционально, т.к. токены короткоживущие
    }
}
