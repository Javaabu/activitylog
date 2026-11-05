<?php

namespace Javaabu\Activitylog\Tests\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Activitylog\Tests\TestSupport\Models\Admin;
use Javaabu\Activitylog\Tests\TestSupport\Models\Post;
use Javaabu\Activitylog\Tests\TestSupport\Models\User;
use Javaabu\Activitylog\Tests\TestSupport\Policies\PostPolicy;
use Javaabu\Permissions\Permissions;

trait InteractsWithLogs
{
    use RefreshDatabase;

    protected function setUpLogs(): void
    {
        Permissions::resource('posts', 'posts', ['view', 'edit']);
        Permissions::policy(Post::class, PostPolicy::class);

        $this->artisan('permissions:sync');
    }

    protected function defineRoutes($router): void
    {
        $router->middleware('web')->group(function ($router) {
            $router->activitylog();
            $router->activitylog(['guard' => 'web_admin', 'prefix' => 'admin/logs', 'name' => 'admin.logs']);
        });
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function userWith(array $permissions, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);

        if ($permissions) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function adminWith(array $permissions): Admin
    {
        $admin = Admin::factory()->create();

        if ($permissions) {
            $admin->givePermissionTo($permissions);
        }

        return $admin;
    }

    protected function makePost(array $attributes = []): Post
    {
        return Post::create(array_merge(['title' => 'ރިޕޯޓު', 'slug' => 'report'], $attributes));
    }
}
