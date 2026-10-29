<?php

namespace App\Providers;

use App\Interfaces\AiSettingRepositoryInterface;
use App\Interfaces\ArchiveRepositoryInterface;
use App\Models\Archive;
use App\Observers\ArchiveObserver;
use App\Repositories\AiSettingRepository;
use App\Repositories\ArchiveRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ArchiveRepositoryInterface::class,
            ArchiveRepository::class
        );

        $this->app->bind(
            AiSettingRepositoryInterface::class,
            AiSettingRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Archive::observe(ArchiveObserver::class);
    }
}
