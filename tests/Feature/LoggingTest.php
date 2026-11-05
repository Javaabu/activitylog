<?php

namespace Javaabu\Activitylog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Tests\TestCase;
use Javaabu\Activitylog\Tests\TestSupport\Models\Post;
use PHPUnit\Framework\Attributes\Test;

class LoggingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_logs_model_create_events_with_the_ip(): void
    {
        $post = Post::create(['title' => 'ރިޕޯޓު', 'slug' => 'report']);

        /** @var Activity $log */
        $log = $post->activitiesAsSubject()->first();

        $this->assertInstanceOf(Activity::class, $log);

        $this->assertDatabaseHas('activity_log', [
            'id' => $log->id,
            'ip' => '127.0.0.1',
            'event' => 'created',
            'subject_type' => 'post',
            'subject_id' => $post->id,
        ]);

        $this->assertEquals('ރިޕޯޓު', $log->attribute_changes['attributes']['title']);
    }
}
