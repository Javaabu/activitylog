<?php

namespace Javaabu\Activitylog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Activitylog\Exceptions\UnsupportedLogProperty;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Tests\TestCase;
use Javaabu\Activitylog\Tests\TestSupport\Enums\PostStatuses;
use Javaabu\Activitylog\Tests\TestSupport\Models\Legacy\LegacyPost;
use Javaabu\Activitylog\Tests\TestSupport\Models\Note;
use Javaabu\Activitylog\Tests\TestSupport\Models\Post;
use Javaabu\Activitylog\Tests\TestSupport\Models\User;
use Javaabu\Activitylog\Traits\LogsActivity;
use PHPUnit\Framework\Attributes\Test;

class LoggingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_logs_model_create_events_with_the_ip(): void
    {
        $post = Post::create(['title' => 'ރިޕޯޓު', 'slug' => 'report']);

        $log = $post->activitiesAsSubject()->first();

        $this->assertInstanceOf(Activity::class, $log);

        $this->assertDatabaseHas('activity_log', [
            'id' => $log->id,
            'ip' => '127.0.0.1',
            'event' => 'created',
            'description' => 'created',
            'subject_type' => 'post',
            'subject_id' => $post->id,
        ]);

        $this->assertEquals('ރިޕޯޓު', $log->newValues()['title']);
        $this->assertEquals([], $log->oldValues());
    }

    #[Test]
    public function it_does_not_save_the_ip_when_turned_off(): void
    {
        config(['activitylog.log_ip' => false]);

        $post = Post::create(['title' => 'Report', 'slug' => 'report']);

        $this->assertNull($post->activitiesAsSubject()->first()->ip);
    }

    #[Test]
    public function it_logs_the_fillable_attributes_by_default_without_hidden_ones_or_timestamps(): void
    {
        $post = Post::create(['title' => 'Report', 'slug' => 'report', 'secret' => 'hunter2', 'status' => PostStatuses::DRAFT]);

        $values = $post->activitiesAsSubject()->first()->newValues();

        $this->assertArrayHasKey('title', $values);
        $this->assertEquals('draft', $values['status']);
        $this->assertArrayNotHasKey('secret', $values);
        $this->assertArrayNotHasKey('created_at', $values);
        $this->assertArrayNotHasKey('updated_at', $values);
    }

    #[Test]
    public function it_logs_only_the_attributes_that_changed(): void
    {
        $post = Post::create(['title' => 'Draft', 'slug' => 'draft', 'body' => 'ހެލޯ']);

        $post->update(['title' => 'ފައިނަލް', 'body' => 'ހެލޯ']);

        $log = $post->activitiesAsSubject()->latest('id')->first();

        $this->assertEquals('updated', $log->event);
        $this->assertEquals(['title' => 'ފައިނަލް'], $log->newValues());
        $this->assertEquals(['title' => 'Draft'], $log->oldValues());
        $this->assertEquals(['title'], $log->changedAttributes());
    }

    #[Test]
    public function it_skips_logs_when_only_timestamps_or_hidden_attributes_changed(): void
    {
        $post = Post::create(['title' => 'Report', 'slug' => 'report']);

        $post->update(['updated_at' => now()->addDay()]);
        $post->update(['secret' => 'new secret']);

        $this->assertEquals(1, $post->activitiesAsSubject()->count());
    }

    #[Test]
    public function it_never_logs_sensitive_attributes(): void
    {
        $user = new class extends User
        {
            use LogsActivity;

            protected $table = 'users';

            protected $hidden = [];
        };

        $user->fill(['name' => 'Ahmed', 'email' => 'ahmed@example.com', 'password' => 'secret-password', 'remember_token' => 'token'])->save();

        $values = Activity::query()->latest('id')->first()->newValues();

        $this->assertEquals('Ahmed', $values['name']);
        $this->assertArrayNotHasKey('password', $values);
        $this->assertArrayNotHasKey('remember_token', $values);
    }

    #[Test]
    public function it_logs_every_attribute_of_a_model_without_fillable(): void
    {
        $note = Note::create(['title' => 'Note', 'slug' => 'note', 'views' => 1]);

        $log = $note->activitiesAsSubject()->first();

        $this->assertEquals('notes', $log->log_name);
        $this->assertEquals('Note', $log->newValues()['title']);
        $this->assertArrayHasKey('body', $log->newValues());
        $this->assertArrayNotHasKey('id', $log->newValues());
        $this->assertArrayNotHasKey('updated_at', $log->newValues());
    }

    #[Test]
    public function models_can_start_their_options_from_the_defaults(): void
    {
        $note = Note::create(['title' => 'Note', 'slug' => 'note', 'views' => 1]);

        $note->update(['views' => 2]);

        $this->assertEquals(1, $note->activitiesAsSubject()->count());
    }

    #[Test]
    public function it_logs_deleted_and_restored_events(): void
    {
        $post = Post::create(['title' => 'Report', 'slug' => 'report']);

        $post->delete();
        $post->restore();

        $this->assertEquals(['created', 'deleted', 'restored'], $post->activitiesAsSubject()->orderBy('id')->pluck('event')->all());

        $deleted = $post->activitiesAsSubject()->where('event', 'deleted')->first();

        $this->assertEquals('Report', $deleted->oldValues()['title']);
    }

    #[Test]
    public function it_refuses_the_static_properties_of_v1(): void
    {
        $this->expectException(UnsupportedLogProperty::class);
        $this->expectExceptionMessage('logExcept([...])');

        LegacyPost::create(['title' => 'Report', 'slug' => 'report']);
    }

    #[Test]
    public function it_reads_changes_that_were_logged_as_properties(): void
    {
        $post = Post::create(['title' => 'Report', 'slug' => 'report']);

        $log = activity()
            ->performedOn($post)
            ->withProperties([
                'attributes' => ['role' => 'editor'],
                'old' => ['role' => 'viewer'],
                'reason' => 'promotion',
            ])
            ->log('role_updated');

        $this->assertEquals(['role' => 'editor'], $log->newValues());
        $this->assertEquals(['role' => 'viewer'], $log->oldValues());
        $this->assertTrue($log->hasLoggedChanges());
    }

    #[Test]
    public function logs_without_changes_have_none(): void
    {
        $log = activity()->log('logged_in');

        $this->assertFalse($log->hasLoggedChanges());
        $this->assertEquals([], $log->changedAttributes());
    }
}
