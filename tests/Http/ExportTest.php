<?php

namespace Javaabu\Activitylog\Tests\Http;

use Illuminate\Support\Facades\Storage;
use Javaabu\Activitylog\Exports\ActivityLogExport;
use Javaabu\Activitylog\Tests\TestCase;
use Javaabu\Activitylog\Tests\TestSupport\Models\Admin;
use Javaabu\Exports\Exports;
use Javaabu\Exports\Models\Export;
use PHPUnit\Framework\Attributes\Test;

class ExportTest extends TestCase
{
    use InteractsWithLogs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpLogs();

        Storage::fake('exports');
        config(['exports.disk' => 'exports']);
    }

    protected function defineRoutes($router): void
    {
        $router->middleware('web')->group(function ($router) {
            $router->activitylog();
            $router->exports(['prefix' => 'queued-exports', 'name' => 'queued-exports']);
        });
    }

    #[Test]
    public function the_export_is_registered_with_javaabu_exports(): void
    {
        $this->assertEquals(ActivityLogExport::class, Exports::exportClass('activity_logs'));
    }

    #[Test]
    public function the_filtered_logs_download_as_a_spreadsheet(): void
    {
        $this->actingAs($this->userWith(['view_all_logs', 'export_logs'], ['name' => 'އަހުމަދު']));

        $post = $this->makePost(['title' => 'Draft']);
        $post->update(['title' => 'ފައިނަލް']);
        activity()->log('login');

        $response = $this->get('/logs?download=csv&tag[]=updated')->assertOk();

        $csv = $response->baseResponse->getFile()->getContent();

        $this->assertStringContainsString('Date,Tag,Description,User,Type,Record,Changes,"IP address",Log', $csv);
        $this->assertStringContainsString('އަހުމަދު updated the title of Post ފައިނަލް from Draft to ފައިނަލް', $csv);
        $this->assertStringContainsString('Title: Draft → ފައިނަލް', $csv);
        $this->assertStringNotContainsString('logged in', $csv);
    }

    #[Test]
    public function exporting_needs_the_permission(): void
    {
        $this->actingAs($this->userWith(['view_all_logs']))
            ->get('/logs?download=csv')
            ->assertForbidden();
    }

    #[Test]
    public function the_index_shows_the_export_button_only_to_users_who_may_export(): void
    {
        $this->actingAs($this->userWith(['view_all_logs', 'export_logs']))
            ->get('/logs')
            ->assertSee('name="download"', false);

        $this->actingAs($this->userWith(['view_all_logs']))
            ->get('/logs')
            ->assertDontSee('name="download"', false);
    }

    #[Test]
    public function exports_only_have_the_logs_the_user_may_see(): void
    {
        $user = $this->userWith(['view_logs', 'export_logs']);

        $this->makePost();
        activity()->performedOn(Admin::factory()->create())->event('updated')->log('updated');
        activity()->causedBy($user)->log('login');

        $rows = iterator_to_array(ActivityLogExport::for($user)->rows());

        $this->assertCount(1, $rows);
        $this->assertEquals('Logged in', $rows[0][1]);
    }

    #[Test]
    public function exports_go_on_the_queue_when_the_exports_routes_are_registered(): void
    {
        config(['activitylog.export.exports_route' => 'queued-exports.index']);

        $user = $this->userWith(['view_all_logs', 'export_logs']);

        $this->makePost();

        $this->actingAs($user)
            ->get('/logs?download=xlsx')
            ->assertRedirect(url('/queued-exports'))
            ->assertSessionHas('success');

        $export = Export::query()->first();

        $this->assertEquals('activity_logs', $export->type);
        $this->assertEquals('Activity log', $export->name);
        $this->assertTrue($export->user->is($user));
    }

    #[Test]
    public function the_exports_api_can_start_a_filtered_log_export(): void
    {
        $user = $this->userWith(['view_all_logs', 'export_logs', 'view_exports']);

        $this->makePost();
        activity()->log('login');

        $this->actingAs($user)
            ->postJson('/queued-exports', ['type' => 'activity_logs', 'format' => 'csv', 'filters' => ['tag' => ['login']]])
            ->assertCreated();

        $export = Export::query()->first();

        $this->assertEquals('completed', $export->refresh()->status->value);
        $this->assertEquals(1, $export->total_rows);
    }
}
