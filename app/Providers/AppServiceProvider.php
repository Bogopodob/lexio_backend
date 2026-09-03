<?php

namespace App\Providers;

use App\Modules\Auth\Providers\AuthProvider;
use App\Modules\Catalog\Providers\CatalogProvider;
use App\Modules\Learning\Providers\LearningProvider;
use App\Modules\Library\Providers\LibraryProvider;
use App\Modules\User\Providers\UserProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(AuthProvider::class);
        $this->app->register(UserProvider::class);
        $this->app->register(CatalogProvider::class);
        $this->app->register(LearningProvider::class);
        $this->app->register(LibraryProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Shared/Laravel/Infrastructure/Persistence/Database/Migrations'));
    }
}
