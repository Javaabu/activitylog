<?php

namespace Javaabu\Activitylog\Tests\Http;

use Illuminate\Support\Facades\Route;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Tests\TestCase;
use Javaabu\Activitylog\Tests\TestSupport\Models\Post;
use PHPUnit\Framework\Attributes\Test;

class RollbackPagesTest extends TestCase
{
    use InteractsWithLogs;

    protected Post $record;

    protected Activity $version;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpLogs();

        $this->record = $this->makePost(['title' => 'ރިޕޯޓު']);
        $this->version = Activity::query()->latest('id')->first();
        $this->record->update(['title' => 'ފައިނަލް']);
    }

    #[Test]
    public function the_show_page_links_to_the_rollback_for_users_who_may(): void
    {
        $this->actingAs($this->userWith(['view_all_logs', 'rollback_logs', 'edit_posts']))
            ->get("/logs/{$this->version->id}")
            ->assertOk()
            ->assertSee(url("/logs/{$this->version->id}/rollback"), false);
    }

    #[Test]
    public function the_confirmation_shows_the_current_and_restored_values(): void
    {
        $this->actingAs($this->userWith(['view_all_logs', 'rollback_logs', 'edit_posts']))
            ->get("/logs/{$this->version->id}/rollback")
            ->assertOk()
            ->assertSee('Roll back to this version')
            ->assertSee('Current value')
            ->assertSeeInOrder(['ފައިނަލް', 'ރިޕޯޓު'])
            ->assertSee('action="' . url("/logs/{$this->version->id}/rollback") . '"', false);
    }

    #[Test]
    public function rolling_back_restores_the_record_and_shows_the_new_log(): void
    {
        $user = $this->userWith(['view_all_logs', 'rollback_logs', 'edit_posts']);

        $response = $this->actingAs($user)->post("/logs/{$this->version->id}/rollback");

        $log = Activity::query()->latest('id')->first();

        $response->assertRedirect(url("/logs/{$log->id}"))->assertSessionHas('success');

        $this->assertEquals('ރިޕޯޓު', $this->record->refresh()->title);
        $this->assertEquals('rolled_back', $log->event);
        $this->assertTrue($log->causer->is($user));
    }

    #[Test]
    public function rolling_back_answers_json(): void
    {
        $this->actingAs($this->userWith(['view_all_logs', 'rollback_logs', 'edit_posts']))
            ->postJson("/logs/{$this->version->id}/rollback")
            ->assertCreated()
            ->assertJsonPath('data.event', 'rolled_back')
            ->assertJsonPath('data.tag.key', 'rolled_back')
            ->assertJsonPath('data.changes.0.new', 'ރިޕޯޓު');
    }

    #[Test]
    public function rolling_back_needs_the_permission_and_update_on_the_record(): void
    {
        foreach ([['view_all_logs', 'edit_posts'], ['view_all_logs', 'rollback_logs']] as $permissions) {
            $user = $this->userWith($permissions);

            $this->actingAs($user)->get("/logs/{$this->version->id}/rollback")->assertForbidden();
            $this->actingAs($user)->post("/logs/{$this->version->id}/rollback")->assertForbidden();
        }

        $this->assertEquals('ފައިނަލް', $this->record->refresh()->title);
    }

    #[Test]
    public function logs_that_cant_be_rolled_back_to_are_refused(): void
    {
        $latest = Activity::query()->latest('id')->first();

        $this->actingAs($this->userWith(['view_all_logs', 'rollback_logs', 'edit_posts']))
            ->get("/logs/{$latest->id}/rollback")
            ->assertForbidden();
    }

    #[Test]
    public function the_rollback_routes_can_be_left_out(): void
    {
        Route::activitylog(['prefix' => 'audit', 'name' => 'audit', 'rollback' => false]);

        $routes = app('router')->getRoutes();
        $routes->refreshNameLookups();

        $this->assertTrue($routes->hasNamedRoute('audit.index'));
        $this->assertFalse($routes->hasNamedRoute('audit.rollback'));
    }
}
