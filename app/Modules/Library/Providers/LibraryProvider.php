<?php

namespace App\Modules\Library\Providers;

use App\Modules\Library\Domain\Ports\LibraryRepositoryInterface;
use App\Modules\Library\Infrastructure\Persistence\Eloquent\EloquentLibraryRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class LibraryProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LibraryRepositoryInterface::class, EloquentLibraryRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Modules/Library/Infrastructure/Persistence/Database/Migrations'));

        Route::middleware('api')
            ->prefix('api')
            ->group(app_path('Modules/Library/routes/router.php'));
    }
}
