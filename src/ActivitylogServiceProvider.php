<?php

namespace Javaabu\Activitylog;

use Illuminate\Support\ServiceProvider;

class ActivitylogServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // declare publishes
        if ($this->app->runningInConsole()) {
            $this->registerMigrations();

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'activitylog-migrations');

            $this->publishes([
                __DIR__ . '/../config/activitylog.php' => config_path('activitylog.php'),
            ], 'activitylog-config');
        }
    }


    /**
     * Register migration files.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        if (Activitylog::$runsMigrations) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // merge package config with user defined config
        $this->mergeConfigFrom(__DIR__ . '/../config/activitylog.php', 'activitylog');
    }
}
