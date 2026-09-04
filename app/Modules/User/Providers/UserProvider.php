<?php

namespace App\Modules\User\Providers;

use App\Modules\User\Domain\Ports\FriendshipRepositoryInterface;
use App\Modules\User\Domain\Ports\UserAccountRepositoryInterface;
use App\Modules\User\Domain\Ports\UserProfileRepositoryInterface;
use App\Modules\User\Infrastructure\Persistence\Eloquent\EloquentFriendshipRepository;
use App\Modules\User\Infrastructure\Persistence\Eloquent\EloquentUserAccountRepository;
use App\Modules\User\Infrastructure\Persistence\Eloquent\EloquentUserProfileRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class UserProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(UserAccountRepositoryInterface::class, EloquentUserAccountRepository::class);
        $this->app->singleton(UserProfileRepositoryInterface::class, EloquentUserProfileRepository::class);
        $this->app->singleton(FriendshipRepositoryInterface::class, EloquentFriendshipRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Modules/User/Infrastructure/Persistence/Database/Migrations'));

        Route::middleware('api')
            ->prefix('api')
            ->group(app_path('Modules/User/routes/User.php'));
    }
}
