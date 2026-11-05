<?php

namespace Javaabu\Activitylog\Tests\Http;

use Inertia\ServiceProvider as InertiaServiceProvider;
use Inertia\Testing\AssertableInertia;
use Javaabu\Activitylog\Activitylog;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class InertiaPagesTest extends TestCase
{
    use InteractsWithLogs;

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), InertiaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('activitylog.views', 'inertia');
        $app['config']->set('inertia.testing.ensure_pages_exist', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpLogs();
    }

    #[Test]
    public function the_index_renders_the_inertia_page(): void
    {
        $this->actingAs($this->userWith(['view_all_logs', 'export_logs'], ['name' => 'އަހުމަދު']));

        $post = $this->makePost();
        $log = $post->activitiesAsSubject()->first();

        $this->get('/logs')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('activitylog::Logs/Index')
                ->where('title', 'Activity log')
                ->where('logs.data.0.id', $log->id)
                ->where('logs.data.0.tag.key', 'created')
                ->where('logs.data.0.tag.color', 'success')
                ->where('logs.data.0.causer.name', 'އަހުމަދު')
                ->where('logs.data.0.links.show', url("/logs/{$log->id}"))
                ->has('logs.data.0.description_html')
                ->has('logs.data.0.created_at_for_humans')
                ->where('filters.id', 'filter')
                ->where('filters.action', url('/logs'))
                ->where('can_export', true)
                ->where('export_formats.csv', 'CSV')
                ->where('subject', null)
                ->where('urls.index', url('/logs'))
                ->where('strings.no_logs', 'No activity yet')
                ->has('logs.meta.last_page'));
    }

    #[Test]
    public function the_index_renders_a_records_history(): void
    {
        $this->actingAs($this->userWith(['view_all_logs']));

        $post = $this->makePost(['title' => 'ފައިނަލް']);

        $this->get("/logs?subject_type=post&subject_id={$post->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('activitylog::Logs/Index')
                ->where('title', 'History of ފައިނަލް')
                ->where('subject.name', 'ފައިނަލް')
                ->where('subject.type_label', 'Post'));
    }

    #[Test]
    public function the_show_page_renders_the_inertia_page(): void
    {
        $this->actingAs($this->userWith(['view_all_logs', 'rollback_logs', 'edit_posts']));

        $post = $this->makePost(['title' => 'Draft']);
        $version = $post->activitiesAsSubject()->first();
        $post->update(['title' => 'ފައިނަލް']);

        $this->get("/logs/{$version->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('activitylog::Logs/Show')
                ->where('log.id', $version->id)
                ->where('log.changes.0.attribute', 'title')
                ->where('log.rollback.possible', true)
                ->where('log.can.rollback', true)
                ->where('changes.0.new', 'Draft')
                ->where('can_rollback', true)
                ->where('urls.rollback', url("/logs/{$version->id}/rollback")));
    }

    #[Test]
    public function the_rollback_page_renders_the_inertia_page(): void
    {
        $this->actingAs($this->userWith(['view_all_logs', 'rollback_logs', 'edit_posts']));

        $post = $this->makePost(['title' => 'Draft']);
        $version = $post->activitiesAsSubject()->first();
        $post->update(['title' => 'ފައިނަލް']);

        $this->get("/logs/{$version->id}/rollback")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('activitylog::Logs/Rollback')
                ->where('changes.0.old', 'ފައިނަލް')
                ->where('changes.0.new', 'Draft')
                ->where('strings.rollback_title', 'Roll back to this version'));
    }

    #[Test]
    public function history_gives_a_records_logs_for_the_react_component(): void
    {
        $user = $this->userWith(['view_all_logs']);
        $this->actingAs($user);

        $post = $this->makePost();
        $post->update(['title' => 'ފައިނަލް']);
        $this->makePost(['title' => 'Other', 'slug' => 'other']);

        $history = Activitylog::history($post, $user, limit: 1);

        $this->assertCount(1, $history['logs']);
        $this->assertEquals('updated', $history['logs'][0]['event']);
        $this->assertEquals(url('/logs/' . Activity::query()->where('event', 'updated')->first()->id), $history['logs'][0]['links']['show']);
        $this->assertEquals(url("/logs?subject_type=post&subject_id={$post->id}"), $history['url']);
        $this->assertEquals('History', $history['strings']['history']);

        $this->assertNull(Activitylog::history($post, $this->userWith([]))['url']);
        $this->assertCount(0, Activitylog::history($post, $this->userWith([]))['logs']);
    }
}
