<?php

namespace Javaabu\Activitylog\Tests\TestSupport\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Javaabu\Activitylog\Tests\TestSupport\Enums\PostStatuses;
use Javaabu\Activitylog\Tests\TestSupport\Models\Post;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'body' => $this->faker->paragraph,
            'status' => PostStatuses::DRAFT,
        ];
    }
}
