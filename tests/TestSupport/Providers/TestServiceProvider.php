<?php

namespace Javaabu\Activitylog\Tests\TestSupport\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Javaabu\Activitylog\Tests\TestSupport\Models\Admin;
use Javaabu\Activitylog\Tests\TestSupport\Models\Article;
use Javaabu\Activitylog\Tests\TestSupport\Models\Page;
use Javaabu\Activitylog\Tests\TestSupport\Models\Post;
use Javaabu\Activitylog\Tests\TestSupport\Models\User;

class TestServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database');

        View::addLocation(__DIR__ . '/../views');

        Relation::morphMap([
            'user' => User::class,
            'admin' => Admin::class,
            'post' => Post::class,
            'article' => Article::class,
            'page' => Page::class,
        ]);
    }
}
