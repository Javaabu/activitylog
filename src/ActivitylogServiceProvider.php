<?php

namespace Javaabu\Activitylog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Javaabu\Activitylog\Descriptions\Describer;
use Javaabu\Activitylog\Exports\ActivityLogExport;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Policies\ActivityPolicy;
use Javaabu\Activitylog\Routing\ActivitylogRoutes;
use Javaabu\Exports\Exports;
use Javaabu\Manifest\Concerns\RegistersAssets;
use Javaabu\Manifest\Package;
use Javaabu\Permissions\Permissions;
use Spatie\Activitylog\Actions\LogActivityAction;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class ActivitylogServiceProvider extends ServiceProvider
{
    use RegistersAssets;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/activitylog.php', 'activitylog');

        $this->useActivityModel();

        $this->app->singleton(Describer::class, function () {
            $class = config('activitylog.describer') ?: Describer::class;

            return new $class;
        });
    }

    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__ . '/../lang');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'activitylog');

        $this->registerIpLogging();
        $this->registerFrontendAssets();
        $this->registerRouteMacros();
        $this->registerPermissions();
        $this->registerExport();

        if ($this->app->runningInConsole()) {
            $this->registerMigrations();
            $this->registerPublishing();
        }
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

    /**
     * Logs saved through spatie's buffer are bulk inserted without model events, so the IP is
     * also set just before logging
     */
    protected function registerIpLogging(): void
    {
        if (Activitylog::$records_ip_before_logging) {
            return;
        }

        Activitylog::$records_ip_before_logging = true;

        LogActivityAction::beforeLogging(function (Model $activity) {
            if ($activity instanceof Activity) {
                $activity->recordIp();
            }
        });
    }

    /**
     * The Inertia pages (activitylog::Logs/Index) and the components apps can import as
     * `@javaabu/activitylog`. The Blade pages register no scripts, so every npm package is
     * for the pages: Blade-only apps aren't asked to install React.
     */
    protected function registerFrontendAssets(): void
    {
        $this->registerAssets('activitylog', __DIR__ . '/../resources', function (Package $package) {
            $package
                ->js()
                ->pages()
                ->npm([
                    '@javaabu/forms' => 'github:Javaabu/react-forms#next-gen',
                    '@javaabu/onu' => '^0.2.1',
                ], peer_dependencies: [
                    'react' => '^18.3 || ^19.0',
                    'react-dom' => '^18.3 || ^19.0',
                    '@inertiajs/react' => '^2.0 || ^3.0',
                ], for: 'pages');
        });
    }

    protected function registerRouteMacros(): void
    {
        Route::macro('activitylog', function (array $options = []) {
            ActivitylogRoutes::register($this, $options);
        });
    }

    /**
     * view_logs, view_all_logs, rollback_logs and export_logs, and the default policy, which
     * the app can override
     */
    protected function registerPermissions(): void
    {
        if (! config('activitylog.register_permissions', true)) {
            return;
        }

        Permissions::register('logs', [
            'view_logs' => 'View activity logs of records you can see',
            'view_all_logs' => 'View all activity logs',
            'rollback_logs' => 'Roll back records from their activity logs',
            'export_logs' => 'Export activity logs',
        ], label: 'Activity logs');

        Permissions::policy(Activitylog::activityModel(), config('activitylog.policy', ActivityPolicy::class));
    }

    /**
     * Let javaabu/exports' API and pages start the activity log export as `activity_logs`
     */
    protected function registerExport(): void
    {
        if (! config('activitylog.export.enabled', true)) {
            return;
        }

        Exports::register('activity_logs', config('activitylog.export.class') ?: ActivityLogExport::class);
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

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/activitylog'),
        ], 'activitylog-views');

        $this->publishes([
            __DIR__ . '/../lang' => lang_path('vendor/activitylog'),
        ], 'activitylog-translations');
    }
}
