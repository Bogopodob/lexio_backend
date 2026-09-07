<?php

namespace App\Modules\Auth\Providers;

use App\Modules\Auth\Domain\Ports\AccessTokenVerifierInterface;
use App\Modules\Auth\Domain\Ports\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\Ports\CountryAuthPolicyResolverInterface;
use App\Modules\Auth\Domain\Ports\EmailOtpSenderInterface;
use App\Modules\Auth\Domain\Ports\OtpCodeStoreInterface;
use App\Modules\Auth\Domain\Ports\TokenIssuerInterface;
use App\Modules\Auth\Domain\Ports\UserAccountGatewayInterface;
use App\Modules\Auth\Infrastructure\Persistence\Adapters\UserRepositoryUserAccountGateway;
use App\Modules\Auth\Infrastructure\Persistence\Eloquent\EloquentAuthUserRepository;
use App\Modules\Auth\Infrastructure\Security\JwtAccessTokenVerifier;
use App\Modules\Auth\Infrastructure\Security\JwtTokenIssuer;
use App\Modules\Auth\Infrastructure\Services\CacheOtpCodeStore;
use App\Modules\Auth\Infrastructure\Services\CacheRevokedTokenStore;
use App\Modules\Auth\Infrastructure\Services\ConfigCountryAuthPolicyResolver;
use App\Modules\Auth\Infrastructure\Services\LaravelEmailOtpSender;
use App\Shared\Laravel\Infrastructure\Security\Jwt\Contracts\RevokedTokenStoreInterface;
use App\Shared\Laravel\Infrastructure\Security\Jwt\Contracts\TokenGeneratorInterface;
use App\Shared\Laravel\Infrastructure\Security\Jwt\JwtTokenGenerator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

final class AuthProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TokenIssuerInterface::class, JwtTokenIssuer::class);
        $this->app->singleton(AccessTokenVerifierInterface::class, JwtAccessTokenVerifier::class);
        $this->app->singleton(UserAccountGatewayInterface::class, UserRepositoryUserAccountGateway::class);
        $this->app->singleton(AuthUserRepositoryInterface::class, EloquentAuthUserRepository::class);
        $this->app->singleton(CountryAuthPolicyResolverInterface::class, ConfigCountryAuthPolicyResolver::class);
        $this->app->singleton(OtpCodeStoreInterface::class, CacheOtpCodeStore::class);
        $this->app->singleton(EmailOtpSenderInterface::class, LaravelEmailOtpSender::class);
        $this->app->singleton(TokenGeneratorInterface::class, JwtTokenGenerator::class);
        $this->app->singleton(RevokedTokenStoreInterface::class, CacheRevokedTokenStore::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Modules/Auth/Infrastructure/Persistence/Database/Migrations'));

        $this->registerRateLimiters();

        Route::middleware('api')
            ->prefix('api')
            ->group(app_path('Modules/Auth/routes/router.php'));
    }

    /**
     * Brute-force protection: all auth endpoints are throttled per IP + email,
     * so one attacker can't burn through the whole keyspace or mail-bomb anyone.
     */
    private function registerRateLimiters(): void
    {
        $emailKey = static fn (Request $request): string => mb_strtolower(trim((string) $request->input('email', '')));

        $tooMany = static fn () => response()->json([
            'success' => false,
            'error' => 'rate_limited',
            'message' => __('api.auth.too_many_attempts'),
        ], 429);

        RateLimiter::for('otp-request', static fn (Request $request) => Limit::perMinute(
            max(1, (int) config('auth_rate_limits.otp_request_per_minute', 5))
        )->by($request->ip().'|'.$emailKey($request))->response($tooMany));

        RateLimiter::for('otp-verify', static fn (Request $request) => Limit::perMinute(
            max(1, (int) config('auth_rate_limits.otp_verify_per_minute', 10))
        )->by($request->ip().'|'.$emailKey($request))->response($tooMany));

        RateLimiter::for('auth-password', static fn (Request $request) => Limit::perMinute(
            max(1, (int) config('auth_rate_limits.password_per_minute', 10))
        )->by($request->ip().'|'.$emailKey($request))->response($tooMany));
    }
}
