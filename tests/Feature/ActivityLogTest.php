<?php

namespace Javaabu\Activitylog\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Tests\TestCase;
use Javaabu\Activitylog\Traits\LogsActivity;
use Javaabu\Activitylog\Tests\InteractsWithDatabase;

class Category extends Model
{
    use LogsActivity;

    protected $guarded = [];
}

class CategoryIgnoreTimestamps extends Model
{

    use LogsActivity;

    protected static array $ignoreChangedAttributes = ['updated_at', 'created_at'];

    protected $table = 'categories';

    protected $guarded = [];
}

class CategoryIgnoreHiddenSlug extends Model
{

    use LogsActivity;

    protected $table = 'categories';

    protected $guarded = [];

    protected $hidden = ['slug'];

}

class ActivityLogTest extends TestCase
{
    use InteractsWithDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runMigrations();
    }

    /** @test */
    public function it_logs_the_ip(): void
    {
        $category = new Category(['name' => 'Apple', 'slug' => 'apple']);
        $category->save();

        /** @var Activity $log */
        $log = $category->activities()->first();
        $changes = $log->changes();

        $old = $changes->get('old');
        $new = $changes->get('attributes');

        $this->assertDatabaseHas('activity_log', [
            'id' => $log->id,
            'ip' => '127.0.0.1',
            'description' => 'created',
            'subject_type' => 'Javaabu\\Activitylog\\Tests\\Feature\\Category',
            'subject_id' => $category->id,
        ]);

        $this->assertNull($old);

        $this->assertEquals('Apple', $new['name']);
        $this->assertEquals('apple', $new['slug']);
    }

    /** @test */
    public function it_logs_model_create_events(): void
    {
        $category = new Category(['name' => 'Apple', 'slug' => 'apple']);
        $category->save();

        /** @var Activity $log */
        $log = $category->activities()->first();
        $changes = $log->changes();

        $old = $changes->get('old');
        $new = $changes->get('attributes');

        $this->assertDatabaseHas('activity_log', [
            'id' => $log->id,
            'description' => 'created',
            'subject_type' => 'Javaabu\\Activitylog\\Tests\\Feature\\Category',
            'subject_id' => $category->id,
        ]);

        $this->assertNull($old);

        $this->assertEquals('Apple', $new['name']);
        $this->assertEquals('apple', $new['slug']);
    }

    /** @test */
    public function it_logs_model_update_events(): void
    {
        $category = new Category(['name' => 'Apple', 'slug' => 'apple']);
        $category->save();

        $category->name = 'Orange';
        $category->slug = 'orange';
        $category->save();

        /** @var Activity $log */
        $log = $category->activities()->latest('id')->first();
        $changes = $log->changes();

        $old = $changes->get('old');
        $new = $changes->get('attributes');

        $this->assertDatabaseHas('activity_log', [
            'id' => $log->id,
            'description' => 'updated',
            'subject_type' => 'Javaabu\\Activitylog\\Tests\\Feature\\Category',
            'subject_id' => $category->id,
        ]);

        $this->assertEquals('Apple', $old['name']);
        $this->assertEquals('apple', $old['slug']);

        $this->assertEquals('Orange', $new['name']);
        $this->assertEquals('orange', $new['slug']);
    }

    /** @test */
    public function it_ignores_changes_to_attributes_marked_to_ignore(): void
    {
        $category = new CategoryIgnoreTimestamps(['name' => 'Apple', 'slug' => 'apple']);
        $category->save();

        $category->created_at = now()->subDay();
        $category->save();

        $this->assertDatabaseMissing('activity_log', [
            'description' => 'updated',
            'subject_type' => 'Javaabu\\Activitylog\\Tests\\Feature\\CategoryIgnoreTimestamps',
            'subject_id' => $category->id,
        ]);
    }

    /** @test */
    public function it_does_not_log_hidden_attributes(): void
    {
        $category = new CategoryIgnoreHiddenSlug(['name' => 'Apple', 'slug' => 'apple']);
        $category->save();

        /** @var Activity $log */
        $log = $category->activities()->first();
        $changes = $log->changes();

        $old = $changes->get('old');
        $new = $changes->get('attributes');

        $this->assertDatabaseHas('activity_log', [
            'id' => $log->id,
            'description' => 'created',
            'subject_type' => 'Javaabu\\Activitylog\\Tests\\Feature\\CategoryIgnoreHiddenSlug',
            'subject_id' => $category->id,
        ]);

        $this->assertNull($old);

        $this->assertArrayHasKey('name', $new);
        $this->assertArrayNotHasKey('slug', $new);
    }
}
