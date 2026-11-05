<?php

namespace Javaabu\Activitylog\Tests;

use Javaabu\Activitylog\ActivitylogServiceProvider;
use Javaabu\Activitylog\Tests\TestSupport\Models\Admin;
use Javaabu\Activitylog\Tests\TestSupport\Models\User;
use Javaabu\Activitylog\Tests\TestSupport\Providers\TestServiceProvider;
use Javaabu\Permissions\Models\Permission;
use Javaabu\Permissions\Models\Role;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\Activitylog\ActivitylogServiceProvider as SpatieActivitylogServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:yWa/ByhLC/GUvfToOuaPD7zDwB64qkc/QkaQOrT5IpE=');
        $app['config']->set('permission.models.permission', Permission::class);
        $app['config']->set('permission.models.role', Role::class);
        $app['config']->set('permissions.guards', ['web', 'web_admin']);

        $app['config']->set('auth.guards', [
            'web' => [
                'driver' => 'session',
                'provider' => 'users',
            ],
            'web_admin' => [
                'driver' => 'session',
                'provider' => 'admins',
            ],
        ]);

        $app['config']->set('auth.providers', [
            'users' => [
                'driver' => 'eloquent',
                'model' => User::class,
            ],
            'admins' => [
                'driver' => 'eloquent',
                'model' => Admin::class,
            ],
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            SpatieActivitylogServiceProvider::class,
            ActivitylogServiceProvider::class,
            TestServiceProvider::class,
        ];
    }
}
