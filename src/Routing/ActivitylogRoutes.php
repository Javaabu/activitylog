<?php

namespace Javaabu\Activitylog\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Javaabu\Activitylog\Http\Controllers\ActivityLogsController;

/**
 * Registers the log pages for `Route::activitylog()`
 *
 * ```php
 * Route::activitylog();                                                      // /logs, logs.*, auth
 * Route::activitylog(['guard' => 'web_admin', 'prefix' => 'admin/logs', 'name' => 'admin.logs']);
 * ```
 */
class ActivitylogRoutes
{
    /** The route action key that holds the guard the routes were registered for */
    public const string GUARD_ACTION = 'activitylog_guard';

    /**
     * @param  array{
     *     guard?: string|null,
     *     prefix?: string,
     *     name?: string,
     *     middleware?: string|array<int, string>|null,
     *     controller?: class-string|null,
     *     rollback?: bool
     * }  $options
     */
    public static function register(Router $router, array $options = []): void
    {
        $guard = $options['guard'] ?? null;
        $prefix = trim($options['prefix'] ?? 'logs', '/');
        $name = rtrim($options['name'] ?? 'logs', '.');
        $middleware = $options['middleware'] ?? [$guard ? "auth:{$guard}" : 'auth'];
        $controller = $options['controller'] ?? config('activitylog.controllers.logs', ActivityLogsController::class);
        $rollback = $options['rollback'] ?? config('activitylog.rollback.enabled', true);

        $router->group([
            'prefix' => $prefix,
            'as' => "{$name}.",
            'middleware' => $middleware,
            self::GUARD_ACTION => $guard,
        ], function (Router $router) use ($controller, $rollback) {
            $router->get('/', [$controller, 'index'])->name('index');
            $router->get('/{log}', [$controller, 'show'])->whereNumber('log')->name('show');

            if ($rollback) {
                $router->get('/{log}/rollback', [$controller, 'confirmRollback'])->whereNumber('log')->name('rollback.confirm');
                $router->post('/{log}/rollback', [$controller, 'rollback'])->whereNumber('log')->name('rollback');
            }
        });
    }

    /**
     * The guard the current route was registered for, or null for the default guard
     */
    public static function guard(Request $request): ?string
    {
        return $request->route()?->getAction(self::GUARD_ACTION);
    }

    /**
     * The route name prefix of the current route, e.g. `admin.logs`
     */
    public static function namePrefix(Request $request): string
    {
        $name = (string) $request->route()?->getName();

        foreach (['.rollback.confirm', '.rollback', '.index', '.show'] as $suffix) {
            if (str_ends_with($name, $suffix)) {
                return substr($name, 0, -strlen($suffix));
            }
        }

        return 'logs';
    }
}
