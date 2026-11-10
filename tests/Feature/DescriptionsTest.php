<?php

namespace Javaabu\Activitylog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Activitylog\Activitylog;
use Javaabu\Activitylog\Descriptions\Description;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Tests\TestCase;
use Javaabu\Activitylog\Tests\TestSupport\Enums\PostStatuses;
use Javaabu\Activitylog\Tests\TestSupport\Models\Invoice;
use Javaabu\Activitylog\Tests\TestSupport\Models\Post;
use Javaabu\Activitylog\Tests\TestSupport\Models\User;
use PHPUnit\Framework\Attributes\Test;

class DescriptionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $ahmed;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ahmed = User::factory()->create(['name' => 'Ahmed']);
        $this->actingAs($this->ahmed);
    }

    #[Test]
    public function it_describes_created_and_deleted_models(): void
    {
        $post = Post::create(['title' => 'Report', 'slug' => 'report']);
        $post->delete();
        $post->restore();

        $logs = $post->activitiesAsSubject()->orderBy('id')->get();

        $this->assertEquals('Ahmed created Post Report', (string) $logs[0]->describe());
        $this->assertEquals('Ahmed deleted Post Report', (string) $logs[1]->describe());
        $this->assertEquals('Ahmed restored Post Report', (string) $logs[2]->describe());
    }

    #[Test]
    public function it_names_the_attribute_and_the_values_of_a_single_change(): void
    {
        $invoice = Invoice::create(['title' => 'INV-12', 'slug' => 'inv-12', 'status' => PostStatuses::DRAFT]);

        $invoice->update(['status' => PostStatuses::PUBLISHED]);

        $this->assertEquals(
            "Ahmed updated the status of Invoice #{$invoice->id} from Draft to Published",
            (string) $this->latestLog()->describe(),
        );
    }

    #[Test]
    public function it_describes_values_that_were_set_or_cleared(): void
    {
        $post = Post::create(['title' => 'Report', 'slug' => 'report']);

        $post->update(['body' => 'ހެލޯ']);
        $set = $this->latestLog();

        $post->update(['body' => null]);
        $cleared = $this->latestLog();

        $this->assertEquals('Ahmed set the body of Post Report to ހެލޯ', (string) $set->describe());
        $this->assertEquals('Ahmed cleared the body of Post Report', (string) $cleared->describe());
    }

    #[Test]
    public function it_lists_a_few_changed_attributes_and_counts_many(): void
    {
        $invoice = Invoice::create(['title' => 'INV-1', 'slug' => 'inv-1', 'views' => 1]);

        $invoice->update(['title' => 'INV-2', 'slug' => 'inv-2']);
        $this->assertEquals("Ahmed updated the Number and slug of Invoice #{$invoice->id}", (string) $this->latestLog()->describe());

        $invoice->update(['title' => 'INV-3', 'slug' => 'inv-3', 'views' => 2]);
        $this->assertEquals("Ahmed updated the Number, slug and views of Invoice #{$invoice->id}", (string) $this->latestLog()->describe());

        $invoice->update(['title' => 'INV-4', 'slug' => 'inv-4', 'views' => 3, 'body' => 'x']);
        $this->assertEquals("Ahmed updated 4 attributes of Invoice #{$invoice->id}", (string) $this->latestLog()->describe());
    }

    #[Test]
    public function it_uses_the_apps_validation_attribute_names(): void
    {
        app('translator')->addLines(['validation.attributes.slug' => 'web address'], 'en');

        $post = Post::create(['title' => 'Report', 'slug' => 'report']);
        $post->update(['slug' => 'final-report']);

        $this->assertEquals('Ahmed updated the web address of Post Report from report to final-report', (string) $this->latestLog()->describe());
    }

    #[Test]
    public function it_describes_auth_and_permissions_logs(): void
    {
        $login = activity()->causedBy($this->ahmed)->performedOn($this->ahmed)->log('login');

        $role = activity()
            ->causedBy($this->ahmed)
            ->performedOn(User::factory()->create(['name' => 'Aishath']))
            ->withProperties(['attributes' => ['role' => ['editor']], 'old' => ['role' => ['viewer']]])
            ->log('role_updated');

        $this->assertEquals('Ahmed logged in', (string) $login->describe());
        $this->assertEquals('Ahmed changed the role of Aishath from viewer to editor', (string) $role->describe());
    }

    #[Test]
    public function logs_without_a_causer_are_by_the_system(): void
    {
        auth()->logout();

        $post = Post::create(['title' => 'Report', 'slug' => 'report']);

        $this->assertEquals('System created Post Report', (string) $post->activitiesAsSubject()->first()->describe());
    }

    #[Test]
    public function models_can_describe_their_own_logs(): void
    {
        $invoice = Invoice::create(['title' => 'INV-12', 'slug' => 'inv-12']);

        $log = activity()->performedOn($invoice)->event('voided')->log('voided');

        $this->assertEquals("Invoice {$invoice->id} was voided", (string) $log->describe());
    }

    #[Test]
    public function events_can_be_described_for_every_model_or_one(): void
    {
        $post = Post::create(['title' => 'Report', 'slug' => 'report']);
        $invoice = Invoice::create(['title' => 'INV-1', 'slug' => 'inv-1']);

        Activitylog::describe('published', ':causer published :type :subject');
        Activitylog::describe('published', ':causer sent :type :subject', Invoice::class);
        Activitylog::describe('archived', fn (Activity $log, array $values) => "{$values['subject']} was archived by {$values['causer']}");

        $this->assertEquals('Ahmed published Post Report', (string) activity()->performedOn($post)->event('published')->log('published')->describe());
        $this->assertEquals("Ahmed sent Invoice #{$invoice->id}", (string) activity()->performedOn($invoice)->event('published')->log('published')->describe());
        $this->assertEquals('Report was archived by Ahmed', (string) activity()->performedOn($post)->event('archived')->log('archived')->describe());
    }

    #[Test]
    public function events_can_be_described_in_config(): void
    {
        config(['activitylog.descriptions' => [
            'published' => ':causer published :type :subject',
            Post::class => ['featured' => ':causer featured :subject'],
        ]]);

        $post = Post::create(['title' => 'Report', 'slug' => 'report']);

        $this->assertEquals('Ahmed published Post Report', (string) activity()->performedOn($post)->event('published')->log('published')->describe());
        $this->assertEquals('Ahmed featured Report', (string) activity()->performedOn($post)->event('featured')->log('featured')->describe());
    }

    #[Test]
    public function logs_nothing_describes_fall_back_to_their_text_or_tag(): void
    {
        $post = Post::create(['title' => 'Report', 'slug' => 'report']);

        $sentence = activity()->performedOn($post)->log('Sent the report to :subject.title');
        $key = activity()->performedOn($post)->event('invoice_sent')->log('invoice_sent');
        $bare = activity()->log('cache_cleared');

        $this->assertEquals('Sent the report to Report', (string) $sentence->describe());
        $this->assertEquals('Invoice sent: Post Report, by Ahmed', (string) $key->describe());
        $this->assertEquals('Cache cleared, by Ahmed', (string) $bare->describe());
    }

    #[Test]
    public function the_html_wraps_values_in_bdi_and_escapes_them(): void
    {
        $this->ahmed->update(['name' => 'އަހުމަދު <b>']);

        $post = Post::create(['title' => 'Report', 'slug' => 'report']);

        $html = $post->activitiesAsSubject()->first()->describe()->toHtml();

        $this->assertEquals('<bdi>އަހުމަދު &lt;b&gt;</bdi> created <bdi>Post</bdi> <bdi>Report</bdi>', $html);
    }

    #[Test]
    public function long_values_are_shortened(): void
    {
        $post = Post::create(['title' => 'Report', 'slug' => 'report', 'body' => 'short']);
        $post->update(['body' => str_repeat('ބ', 100)]);

        $this->assertStringContainsString(str_repeat('ބ', 60) . '...', (string) $this->latestLog()->describe());
    }

    #[Test]
    public function descriptions_serialize_to_text(): void
    {
        $description = new Description(':causer did it', ['causer' => 'Ahmed']);

        $this->assertEquals('"Ahmed did it"', json_encode($description));
    }

    protected function latestLog(): Activity
    {
        return Activity::query()->latest('id')->first();
    }
}
