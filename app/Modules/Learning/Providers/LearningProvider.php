<?php

namespace App\Modules\Learning\Providers;

use App\Modules\Learning\Domain\Ports\AchievementRepositoryInterface;
use App\Modules\Learning\Domain\Ports\GoalRepositoryInterface;
use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;
use App\Modules\Learning\Domain\Ports\ProgressRepositoryInterface;
use App\Modules\Learning\Domain\Ports\StreakRepositoryInterface;
use App\Modules\Learning\Infrastructure\Persistence\Eloquent\EloquentAchievementRepository;
use App\Modules\Learning\Infrastructure\Persistence\Eloquent\EloquentGoalRepository;
use App\Modules\Learning\Infrastructure\Persistence\Eloquent\EloquentLanguageProfileRepository;
use App\Modules\Learning\Infrastructure\Persistence\Eloquent\EloquentProgressRepository;
use App\Modules\Learning\Infrastructure\Persistence\Eloquent\EloquentStreakRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class LearningProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LanguageProfileRepositoryInterface::class, EloquentLanguageProfileRepository::class);
        $this->app->singleton(ProgressRepositoryInterface::class, EloquentProgressRepository::class);
        $this->app->singleton(StreakRepositoryInterface::class, EloquentStreakRepository::class);
        $this->app->singleton(GoalRepositoryInterface::class, EloquentGoalRepository::class);
        $this->app->singleton(AchievementRepositoryInterface::class, EloquentAchievementRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Modules/Learning/Infrastructure/Persistence/Database/Migrations'));

        Route::middleware('api')
            ->prefix('api')
            ->group(app_path('Modules/Learning/routes/router.php'));
    }
}
