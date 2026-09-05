<?php

namespace App\Modules\Catalog\Providers;

use App\Modules\Catalog\Domain\Ports\CatalogRepositoryInterface;
use App\Modules\Catalog\Infrastructure\Console\Commands\CleanDataCommand;
use App\Modules\Catalog\Infrastructure\Console\Commands\ImportIrregularVerbsCommand;
use App\Modules\Catalog\Infrastructure\Console\Commands\ImportWordsCommand;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\EloquentCatalogRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class CatalogProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CatalogRepositoryInterface::class, EloquentCatalogRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Modules/Catalog/Infrastructure/Persistence/Database/Migrations'));

        Route::middleware('api')
            ->prefix('api')
            ->group(app_path('Modules/Catalog/routes/router.php'));

        $this->commands([ImportWordsCommand::class, ImportIrregularVerbsCommand::class, CleanDataCommand::class]);
    }
}
