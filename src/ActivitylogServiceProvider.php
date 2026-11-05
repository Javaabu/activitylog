<?php

namespace Javaabu\Activitylog;

use Illuminate\Support\ServiceProvider;
use Javaabu\Activitylog\Descriptions\Describer;
use Javaabu\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class ActivitylogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/activitylog.php', 'activitylog');

        $this->useActivityModel();

        $this->app->singleton(Describer::class, function () {
            $class = config('activitylog.describer') ?: Describer::class;

            return new $class;
        });
    }

    /**
     * spatie/laravel-activitylog merges the same config key; when its defaults were
     * merged first, swap its model for ours
     */
    protected function useActivityModel(): void
    {
        if (config('activitylog.activity_model') === SpatieActivity::class) {
            config(['activitylog.activity_model' => Activity::class]);
        }
    }

    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__ . '/../lang');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'activitylog');

        if ($this->app->runningInConsole()) {
            $this->registerMigrations();
            $this->registerPublishing();
        }
    }

    protected function registerMigrations(): void
    {
        if (Activitylog::$runs_migrations) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../config/activitylog.php' => config_path('activitylog.php'),
        ], 'activitylog-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'activitylog-migrations');
    }
}
