<?php

namespace Javaabu\Activitylog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Javaabu\Activitylog\Events\ModelRolledBack;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Rollback\RollbackNotPossible;
use Javaabu\Activitylog\Tests\TestCase;
use Javaabu\Activitylog\Tests\TestSupport\Enums\PostStatuses;
use Javaabu\Activitylog\Tests\TestSupport\Models\Post;
use Javaabu\Activitylog\Tests\TestSupport\Models\User;
use PHPUnit\Framework\Attributes\Test;

class RollbackTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_restores_the_version_right_after_a_log(): void
    {
        $post = Post::create(['title' => 'ރިޕޯޓު', 'slug' => 'report', 'body' => 'One']);
        $post->update(['title' => 'Second', 'status' => PostStatuses::PUBLISHED]);
        $version = $this->latestLog();
        $post->update(['title' => 'Third', 'body' => 'Two']);
        $post->update(['slug' => 'third']);

        $user = User::factory()->create();

        $log = $version->rollback()->perform($user);

        $post->refresh();

        $this->assertEquals('Second', $post->title);
        $this->assertEquals('report', $post->slug);
        $this->assertEquals('One', $post->body);
        $this->assertEquals(PostStatuses::PUBLISHED, $post->status);

        $this->assertEquals('rolled_back', $log->event);
        $this->assertTrue($log->causer->is($user));
        $this->assertEquals(['title' => 'Second', 'body' => 'One', 'slug' => 'report'], $log->newValues());
        $this->assertEquals(['title' => 'Third', 'body' => 'Two', 'slug' => 'third'], $log->oldValues());
        $this->assertEquals($version->id, $log->getProperty('rolled_back_to'));
    }

    #[Test]
    public function it_writes_one_log_for_the_rollback_and_none_for_the_save(): void
    {
        $post = Post::create(['title' => 'One', 'slug' => 'one']);
        $created = $this->latestLog();
        $post->update(['title' => 'Two']);

        $created->rollback()->perform();

        $this->assertEquals(['created', 'updated', 'rolled_back'], $post->activitiesAsSubject()->orderBy('id')->pluck('event')->all());
        $this->assertEquals('One', $post->refresh()->title);
    }

    #[Test]
    public function a_rollback_can_itself_be_rolled_back(): void
    {
        $post = Post::create(['title' => 'One', 'slug' => 'one']);
        $created = $this->latestLog();
        $post->update(['title' => 'Two']);
        $updated = $this->latestLog();

        $created->rollback()->perform();
        $updated->rollback()->perform();

        $this->assertEquals('Two', $post->refresh()->title);
    }

    #[Test]
    public function it_shows_what_would_change(): void
    {
        $post = Post::create(['title' => 'One', 'slug' => 'one', 'status' => PostStatuses::DRAFT]);
        $created = $this->latestLog();
        $post->update(['status' => PostStatuses::PUBLISHED]);

        $rows = $created->rollback()->changes()->rows();

        $this->assertCount(1, $rows);
        $this->assertEquals('status', $rows[0]['attribute']);
        $this->assertEquals('Published', $rows[0]['old']);
        $this->assertEquals('Draft', $rows[0]['new']);
    }

    #[Test]
    public function it_never_touches_hidden_or_excepted_attributes(): void
    {
        config(['activitylog.rollback.except' => ['slug']]);

        $post = Post::create(['title' => 'One', 'slug' => 'one', 'secret' => 'first']);
        $created = $this->latestLog();
        $post->update(['title' => 'Two', 'slug' => 'two', 'secret' => 'second']);

        $created->rollback()->perform();

        $post->refresh();

        $this->assertEquals('One', $post->title);
        $this->assertEquals('two', $post->slug);
        $this->assertEquals('second', $post->secret);
    }

    #[Test]
    public function it_explains_why_a_log_cant_be_rolled_back_to(): void
    {
        $post = Post::create(['title' => 'One', 'slug' => 'one']);
        $created = $this->latestLog();

        $this->assertEquals('The record already matches this version.', $created->rollback()->reason());
        $this->assertFalse($created->canBeRolledBack());

        $post->update(['title' => 'Two']);
        $this->assertTrue($created->fresh()->canBeRolledBack());

        $post->delete();
        $deleted = $this->latestLog();

        $this->assertEquals('Only logs of created and updated records can be rolled back to.', $deleted->rollback()->reason());
        $this->assertEquals('The record is deleted. Restore it first.', $created->fresh()->rollback()->reason());

        $post->forceDelete();
        $this->assertEquals('The record no longer exists.', $created->fresh()->rollback()->reason());

        $login = activity()->event('login')->log('login');
        $this->assertNotNull($login->rollback()->reason());

        config(['activitylog.rollback.enabled' => false]);
        $this->assertEquals('Rolling back is turned off.', $created->fresh()->rollback()->reason());
    }

    #[Test]
    public function it_refuses_to_perform_an_impossible_rollback(): void
    {
        Post::create(['title' => 'One', 'slug' => 'one']);

        $this->expectException(RollbackNotPossible::class);

        $this->latestLog()->rollback()->perform();
    }

    #[Test]
    public function it_dispatches_an_event(): void
    {
        Event::fake([ModelRolledBack::class]);

        $post = Post::create(['title' => 'One', 'slug' => 'one']);
        $created = $this->latestLog();
        $post->update(['title' => 'Two']);

        $created->rollback()->perform();

        Event::assertDispatched(ModelRolledBack::class, fn (ModelRolledBack $event) => $event->subject->is($post)
            && $event->version->is($created)
            && $event->log?->event === 'rolled_back');
    }

    #[Test]
    public function rollbacks_are_described_with_the_version_date(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 5)->setTime(14, 30));

        $post = Post::create(['title' => 'One', 'slug' => 'one']);
        $created = $this->latestLog();

        $this->travel(1)->hours();
        $post->update(['title' => 'Two']);

        $log = $created->rollback()->perform();

        $this->assertEquals('System rolled back Post One to the version of 5 November 2026, 14:30', (string) $log->describe());
    }

    protected function latestLog(): Activity
    {
        return Activity::query()->latest('id')->first();
    }
}
