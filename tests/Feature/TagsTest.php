<?php

namespace Javaabu\Activitylog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;
use Javaabu\Activitylog\Activitylog;
use Javaabu\Activitylog\Enums\ActivityTags;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Tags\ActivityTag;
use Javaabu\Activitylog\Tests\TestCase;
use Javaabu\Activitylog\Tests\TestSupport\Enums\InvoiceTags;
use Javaabu\Activitylog\Tests\TestSupport\Enums\PostStatuses;
use Javaabu\Activitylog\Tests\TestSupport\Models\Post;
use PHPUnit\Framework\Attributes\Test;

class TagsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function model_events_get_the_built_in_tags(): void
    {
        $post = Post::create(['title' => 'Report', 'slug' => 'report']);
        $post->delete();

        $tags = $post->activitiesAsSubject()->orderBy('id')->get()->map(fn (Activity $log) => $log->tag());

        $this->assertEquals(ActivityTags::CREATED, $tags[0]);
        $this->assertEquals('success', $tags[0]->color());
        $this->assertEquals('plus', $tags[0]->icon());
        $this->assertEquals(ActivityTags::DELETED, $tags[1]);
    }

    #[Test]
    public function logs_without_an_event_are_tagged_by_their_description(): void
    {
        $login = activity()->log('login');
        $roles = activity()->log('role_updated');
        $other = activity()->log('Something happened');

        $this->assertEquals(ActivityTags::LOGIN, $login->tag());
        $this->assertEquals(ActivityTags::ROLE_UPDATED, $roles->tag());
        $this->assertEquals(ActivityTags::DEFAULT, $other->tag());
    }

    #[Test]
    public function unknown_events_get_a_plain_tag_named_after_them(): void
    {
        $log = activity()->event('invoice_sent')->log('invoice_sent');

        $tag = $log->tag();

        $this->assertEquals('invoice_sent', $tag->key());
        $this->assertEquals('Invoice sent', $tag->label());
        $this->assertEquals('secondary', $tag->color());
        $this->assertEquals('activity', $tag->icon());
    }

    #[Test]
    public function tags_can_be_added_and_changed_in_config(): void
    {
        config(['activitylog.tags' => [
            'published' => ['label' => 'Published', 'color' => 'success', 'icon' => 'circle-check'],
            'deleted' => ['color' => 'warning'],
        ]]);

        Activitylog::forgetTags();

        $published = Activitylog::tag('published');
        $deleted = Activitylog::tag('deleted');

        $this->assertEquals('Published', $published->label());
        $this->assertEquals('success', $published->color());
        $this->assertEquals('warning', $deleted->color());
        $this->assertEquals('trash-2', $deleted->icon());
        $this->assertEquals('Deleted', $deleted->label());
    }

    #[Test]
    public function tags_can_be_registered_from_an_enum_or_one_at_a_time(): void
    {
        Activitylog::registerTags(InvoiceTags::class);
        Activitylog::registerTag('updated', color: 'primary');
        Activitylog::registerTag(new ActivityTag('archived', 'Archived', 'warning', 'trash-2'));

        $this->assertSame(InvoiceTags::PAID, Activitylog::tag('paid'));
        $this->assertEquals('primary', Activitylog::tag('updated')->color());
        $this->assertEquals('pencil', Activitylog::tag('updated')->icon());
        $this->assertEquals('Archived', Activitylog::tag('archived')->label());
        $this->assertTrue(Activitylog::hasTag('sent'));
    }

    #[Test]
    public function only_enums_of_tags_can_be_registered(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Activitylog::registerTags(PostStatuses::class);
    }

    #[Test]
    public function models_can_tag_their_own_events(): void
    {
        $post = Post::create(['title' => 'Report', 'slug' => 'report']);

        $published = activity()->performedOn($post)->event('published')->log('published');
        $archived = activity()->performedOn($post)->event('archived')->log('archived');

        $this->assertEquals('Published', $published->tag()->label());
        $this->assertEquals(ActivityTags::DELETED, $archived->tag());
    }

    #[Test]
    public function tag_labels_are_translated(): void
    {
        app('translator')->addJsonPath(__DIR__ . '/../../lang');
        app()->setLocale('dv');

        $this->assertEquals(__('Created'), ActivityTags::CREATED->label());
        $this->assertNotEquals('', ActivityTags::CREATED->label());
    }

    #[Test]
    public function logs_can_be_queried_by_tag(): void
    {
        $post = Post::create(['title' => 'Report', 'slug' => 'report']);
        $post->update(['title' => 'ރިޕޯޓު']);
        activity()->log('login');

        $this->assertEquals(2, Activity::tagged(['created', 'login'])->count());
        $this->assertEquals(1, Activity::tagged('updated')->count());
    }

    #[Test]
    public function tags_serialize_with_their_svg(): void
    {
        $data = Activitylog::tagData(ActivityTags::ROLLED_BACK);

        $this->assertEquals('rolled_back', $data['key']);
        $this->assertEquals('warning', $data['color']);
        $this->assertStringContainsString('<path', $data['svg']);

        $this->assertNull(Activitylog::tagData(InvoiceTags::SENT)['svg']);
        $this->assertStringContainsString('<path', Activitylog::tagData(ActivityTags::CREATED)['svg']);
    }

    #[Test]
    public function the_tag_component_renders_the_colour_icon_and_label(): void
    {
        $html = Blade::render('<x-activitylog::tag :tag="$tag" />', ['tag' => ActivityTags::DELETED]);

        $this->assertStringContainsString('data-activity-tag="deleted"', $html);
        $this->assertStringContainsString('data-tone="danger"', $html);
        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('Deleted', $html);

        $html = Blade::render('<x-activitylog::tag :tag="$tag" />', ['tag' => InvoiceTags::SENT]);

        $this->assertStringContainsString('<i class="fa-solid fa-paper-plane"', $html);
    }
}
