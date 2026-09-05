<?php

namespace App\Modules\Auth\Infrastructure\Persistence\Eloquent;

use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Auth\Domain\Enums\AuthProviderEnum;
use App\Modules\Auth\Domain\Ports\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\Ports\UserAccountGatewayInterface;
use App\Modules\Auth\Domain\ValueObjects\PasswordValueObject;
use App\Modules\Auth\Domain\ValueObjects\ProviderId\EmailProviderIdValueObject;
use App\Modules\Auth\Infrastructure\Persistence\Eloquent\Models\AuthIdentity;
use App\Shared\Core\Domain\ValueObject\UuidValueObject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class EloquentAuthUserRepository implements AuthUserRepositoryInterface
{
    public function __construct(private UserAccountGatewayInterface $userAccounts) {}

    public function findById(string $userId): ?AuthUser
    {
        return $this->hydrateUserById($userId);
    }

    public function findByEmail(EmailProviderIdValueObject $email): ?AuthUser
    {
        $identityModel = AuthIdentity::query()
            ->where('provider', AuthProviderEnum::Email->value)
            ->where('provider_id', $email->value())
            ->first();

        if ($identityModel !== null) {
            return $this->hydrateUserById($identityModel->user_id);
        }

        $account = $this->userAccounts->findByEmail($email->value());

        return $account !== null ? $this->hydrateUserFromAccount($account) : null;
    }

    public function createWithEmail(
        string $name,
        EmailProviderIdValueObject $email,
        ?PasswordValueObject $password = null,
    ): AuthUser {
        $isExistsIdentity = AuthIdentity::query()
            ->where('provider', AuthProviderEnum::Email->value)
            ->where('provider_id', $email->value())
            ->exists();

        if ($isExistsIdentity) {
            throw new InvalidArgumentException('User with this email already exists');
        }

        $userId = Str::uuid();
        $hashedPasswordVo = $password?->toHash();
        $fallbackPasswordHash = password_hash(Str::random(40), PASSWORD_DEFAULT);
        if (! $fallbackPasswordHash) {
            throw new \RuntimeException('Failed to generate fallback password hash');
        }

        $hashedPassword = $hashedPasswordVo?->value() ?: $fallbackPasswordHash;

        $this->userAccounts->create($userId, $name, $email->value(), $hashedPassword);

        AuthIdentity::query()->create([
            'id' => Str::uuid(),
            'user_id' => $userId,
            'provider' => AuthProviderEnum::Email->value,
            'provider_id' => $email->value(),
            'secret' => $hashedPasswordVo?->value(),
            'verified_at' => null,
        ]);

        $this->createRemoteUser(
            userId: $userId,
            name: $name,
            email: $email,
        );

        if ($user = $this->hydrateUserById($userId)) {
            return $user;
        }

        throw new \RuntimeException('Failed to create user with email identity.');
    }

    private function hydrateUserById(string $userId): ?AuthUser
    {
        $emailIdentity = DB::table('auth_identities')
            ->where('user_id', $userId)
            ->where('provider', AuthProviderEnum::Email->value)
            ->first();

        if ($emailIdentity === null) {
            $account = $this->userAccounts->findById($userId);

            return $account !== null ? $this->hydrateUserFromAccount($account) : null;
        }

        $account = $this->userAccounts->findById($userId);
        $remoteUser = $this->fetchRemoteUserById($userId);
        $resolvedName = trim((string) ($remoteUser['name'] ?? ($account['name'] ?? '')));
        $resolvedEmail = trim((string) ($remoteUser['email'] ?? ($account['email'] ?? '')));
        $resolvedPasswordHash = trim((string) ($emailIdentity->secret ?? ($account['password_hash'] ?? '')));

        $emailValueObject = EmailProviderIdValueObject::fromString(
            $resolvedEmail !== '' ? $resolvedEmail : (string) $emailIdentity->provider_id,
        );

        try {
            return new AuthUser(
                id: new UuidValueObject($userId),
                name: $resolvedName !== '' ? $resolvedName : 'User',
                email: $emailValueObject,
                password: $resolvedPasswordHash !== ''
                    ? PasswordValueObject::fromHash($resolvedPasswordHash)
                    : null,
                isPremium: self::isPremium($userId),
            );
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    private static function isPremium(string $userId): bool
    {
        return (bool) DB::table('users')->where('id', $userId)->value('is_premium');
    }

    /**
     * @return array{id: string, name: string, email: string}|null
     */
    private function fetchRemoteUserById(string $userId): ?array
    {
        if (! $baseUrl = config('services.user_service.base_url', '')) {
            return null;
        }

        try {
            $response = Http::timeout((int) config('services.user_service.timeout_seconds', 3))
                ->acceptJson()
                ->withHeaders($this->internalApiHeaders())
                ->get(rtrim($baseUrl, '/').'/internal/users/'.urlencode($userId));

            if (! $response->successful()) {
                return null;
            }

            $payload = $response->json();
            if (! is_array($payload)) {
                return null;
            }

            $data = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : $payload;

            return [
                'id' => trim((string) ($data['id'] ?? $userId)),
                'name' => (string) ($data['name'] ?? ''),
                'email' => (string) ($data['email'] ?? ''),
            ];
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function createRemoteUser(
        string $userId,
        string $name,
        ?EmailProviderIdValueObject $email,
    ): void {
        if (! $baseUrl = trim((string) config('services.user_service.base_url', ''))) {
            return;
        }

        try {
            Http::timeout((int) config('services.user_service.timeout_seconds', 3))
                ->acceptJson()
                ->withHeaders($this->internalApiHeaders())
                ->post(rtrim($baseUrl, '/').'/internal/users', [
                    'id' => $userId,
                    'name' => $name,
                    'email' => $email?->value(),
                ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function internalApiHeaders(): array
    {
        if (! $token = trim((string) config('services.internal_api.token', ''))) {
            return [];
        }

        return ['X-Internal-Token' => $token];
    }

    /**
     * @param  array{id: string, name: string, email: string, password_hash: string}  $account
     */
    private function hydrateUserFromAccount(array $account): ?AuthUser
    {
        try {
            return new AuthUser(
                id: new UuidValueObject($account['id']),
                name: trim($account['name']) ?: 'User',
                email: EmailProviderIdValueObject::fromString($account['email']),
                password: $account['password_hash'] !== ''
                    ? PasswordValueObject::fromHash($account['password_hash'])
                    : null,
                isPremium: self::isPremium($account['id']),
            );
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
