<?php

namespace Javaabu\Activitylog\Tests;

use Javaabu\Activitylog\Models\Activity;
use Javaabu\Helpers\HelpersServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Javaabu\Activitylog\ActivitylogServiceProvider;

abstract class TestCase extends BaseTestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('app.key', 'base64:yWa/ByhLC/GUvfToOuaPD7zDwB64qkc/QkaQOrT5IpE=');

        $this->app['config']->set('session.serialization', 'php');

        $this->app['config']->set('activitylog.activity_model', Activity::class);

    }

    protected function getPackageProviders($app)
    {
        return [
            HelpersServiceProvider::class,
            \Spatie\Activitylog\ActivitylogServiceProvider::class,
            ActivitylogServiceProvider::class,
        ];
    }
}
