<?php

namespace Javaabu\Activitylog\Tests\Http;

use Illuminate\Support\Facades\Blade;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Tests\TestCase;
use Javaabu\Activitylog\Tests\TestSupport\Models\Admin;
use PHPUnit\Framework\Attributes\Test;

class LogPagesTest extends TestCase
{
    use InteractsWithLogs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpLogs();
    }

    #[Test]
    public function guests_are_sent_to_log_in(): void
    {
        $this->getJson('/logs')->assertUnauthorized();
    }

    #[Test]
    public function users_without_a_log_permission_may_not_see_the_logs(): void
    {
        $this->actingAs($this->userWith(['view_posts']))
            ->get('/logs')
            ->assertForbidden();
    }

    #[Test]
    public function the_index_lists_every_log_for_users_who_may_view_all(): void
    {
        $user = $this->userWith(['view_all_logs'], ['name' => 'އަހުމަދު']);
        $this->actingAs($user);

        $post = $this->makePost();
        $admin = Admin::factory()->create();
        activity()->performedOn($admin)->event('updated')->log('updated');

        $this->get('/logs')
            ->assertOk()
            ->assertSee('Activity log')
            ->assertSee('data-activity-log="' . $post->activitiesAsSubject()->first()->id . '"', false)
            ->assertSee('އަހުމަދު')
            ->assertSee('data-activity-tag="created"', false)
            ->assertSee('id="filter"', false);

        $this->getJson('/logs')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function users_with_view_logs_see_logs_of_records_they_may_see_and_their_own(): void
    {
        $user = $this->userWith(['view_logs', 'view_posts']);
        $this->actingAs($user);

        $post = $this->makePost();
        $own = activity()->causedBy($user)->log('login');

        auth()->logout();
        $admin = Admin::factory()->create();
        $hidden = activity()->performedOn($admin)->event('updated')->log('updated');

        $response = $this->actingAs($user)->getJson('/logs')->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($post->activitiesAsSubject()->first()->id));
        $this->assertTrue($ids->contains($own->id));
        $this->assertFalse($ids->contains($hidden->id));

        $this->getJson("/logs/{$hidden->id}")->assertForbidden();
    }

    #[Test]
    public function users_with_view_logs_but_not_the_records_see_only_their_own(): void
    {
        $user = $this->userWith(['view_logs']);

        $this->makePost();
        $own = activity()->causedBy($user)->log('login');

        $this->actingAs($user)
            ->getJson('/logs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id);
    }

    #[Test]
    public function the_index_filters_by_tag_search_and_date(): void
    {
        $this->actingAs($this->userWith(['view_all_logs']));

        $post = $this->makePost(['body' => 'ހެލޯ']);
        $post->update(['title' => 'ވާރޭ']);
        activity()->log('login');

        $this->getJson('/logs?tag[]=updated')->assertJsonCount(1, 'data')->assertJsonPath('data.0.event', 'updated');
        $this->getJson('/logs?tag[]=login')->assertJsonCount(1, 'data')->assertJsonPath('data.0.tag.key', 'login');
        $this->getJson('/logs?search=' . urlencode('ވާރޭ'))->assertJsonCount(1, 'data');
        $this->getJson('/logs?search=' . urlencode('ހެލޯ'))->assertJsonCount(1, 'data');
        $this->getJson('/logs?created_at_from=' . now()->addDay()->toDateString())->assertJsonCount(0, 'data');
    }

    #[Test]
    public function the_index_shows_the_history_of_one_record(): void
    {
        $this->actingAs($this->userWith(['view_all_logs']));

        $post = $this->makePost(['title' => 'ރިޕޯޓު']);
        $post->update(['title' => 'ފައިނަލް']);
        $this->makePost(['title' => 'Other', 'slug' => 'other']);

        $this->get("/logs?subject_type=post&subject_id={$post->id}")
            ->assertOk()
            ->assertSee('History of ފައިނަލް')
            ->assertSee('All activity');

        $this->getJson("/logs?subject_type=post&subject_id={$post->id}")->assertJsonCount(2, 'data');
    }

    #[Test]
    public function the_show_page_has_the_description_details_and_changes(): void
    {
        $this->actingAs($this->userWith(['view_all_logs'], ['name' => 'Ahmed']));

        $post = $this->makePost(['title' => 'Draft']);
        $post->update(['title' => 'ފައިނަލް']);
        $log = $post->activitiesAsSubject()->latest('id')->first();

        $this->get("/logs/{$log->id}")
            ->assertOk()
            ->assertSee('Log #' . $log->id)
            ->assertSee('<bdi>Ahmed</bdi> updated the <bdi>title</bdi> of <bdi>Post</bdi>', false)
            ->assertSee('data-attribute="title"', false)
            ->assertSee('Draft')
            ->assertSee('ފައިނަލް')
            ->assertSee('127.0.0.1')
            ->assertDontSee('Roll back');

        $this->getJson("/logs/{$log->id}")
            ->assertOk()
            ->assertJsonPath('data.changes.0.attribute', 'title')
            ->assertJsonPath('data.changes.0.old', 'Draft')
            ->assertJsonPath('data.changes.0.new', 'ފައިނަލް')
            ->assertJsonPath('data.subject.type', 'post')
            ->assertJsonPath('data.subject.name', 'ފައިނަލް')
            ->assertJsonPath('data.causer.name', 'Ahmed')
            ->assertJsonPath('data.tag.color', 'info')
            ->assertJsonPath('data.links.history', url("/logs?subject_type=post&subject_id={$post->id}"));
    }

    #[Test]
    public function the_show_page_lists_custom_properties(): void
    {
        $this->actingAs($this->userWith(['view_all_logs']));

        $log = activity()->withProperties(['guard' => 'web', 'reason' => 'ސަބަބު'])->log('login');

        $this->get("/logs/{$log->id}")
            ->assertOk()
            ->assertSee('data-activitylog-properties', false)
            ->assertSee('ސަބަބު');
    }

    #[Test]
    public function a_missing_log_is_not_found(): void
    {
        $this->actingAs($this->userWith(['view_all_logs']))
            ->get('/logs/999')
            ->assertNotFound();
    }

    #[Test]
    public function the_routes_work_for_another_guard_and_prefix(): void
    {
        $admin = $this->adminWith(['view_all_logs']);

        $this->makePost();

        $this->actingAs($admin, 'web_admin')
            ->get('/admin/logs')
            ->assertOk()
            ->assertSee(url('/admin/logs'), false);

        $this->actingAs($admin, 'web_admin')
            ->getJson('/admin/logs')
            ->assertJsonPath('data.0.links.show', url('/admin/logs/' . Activity::first()->id));
    }

    #[Test]
    public function users_of_another_guard_may_not_use_the_admin_routes(): void
    {
        $this->actingAs($this->userWith(['view_all_logs']))
            ->getJson('/admin/logs')
            ->assertUnauthorized();
    }

    #[Test]
    public function the_history_component_lists_a_records_latest_logs(): void
    {
        $this->actingAs($this->userWith(['view_all_logs']));

        $post = $this->makePost();
        $post->update(['title' => 'ފައިނަލް']);

        $html = Blade::render('<x-activitylog::history :subject="$post" :limit="1" />', ['post' => $post]);

        $this->assertStringContainsString('data-activitylog-history="post:' . $post->id . '"', $html);
        $this->assertStringContainsString('data-activity-tag="updated"', $html);
        $this->assertStringNotContainsString('data-activity-tag="created"', $html);
        $this->assertStringContainsString(url("/logs?subject_type=post&amp;subject_id={$post->id}"), $html);
    }
}
