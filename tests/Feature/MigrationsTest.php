<?php

namespace Javaabu\Activitylog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Javaabu\Activitylog\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MigrationsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_v1_changes_out_of_the_properties(): void
    {
        $id = DB::table('activity_log')->insertGetId([
            'description' => 'updated',
            'event' => 'updated',
            'properties' => json_encode([
                'attributes' => ['title' => 'ފައިނަލް'],
                'old' => ['title' => 'Draft'],
                'reason' => 'typo',
            ]),
        ]);

        $untouched = DB::table('activity_log')->insertGetId([
            'description' => 'logged_in',
            'properties' => json_encode(['guard' => 'web']),
        ]);

        $migration = $this->migration();
        $migration->up();

        $row = DB::table('activity_log')->find($id);

        $this->assertEquals(['attributes' => ['title' => 'ފައިނަލް'], 'old' => ['title' => 'Draft']], json_decode($row->attribute_changes, true));
        $this->assertEquals(['reason' => 'typo'], json_decode($row->properties, true));

        $this->assertNull(DB::table('activity_log')->find($untouched)->attribute_changes);

        $migration->down();

        $this->assertFalse(Schema::hasColumn('activity_log', 'attribute_changes'));
        $this->assertEquals('Draft', json_decode(DB::table('activity_log')->find($id)->properties, true)['old']['title']);
    }

    protected function migration(): object
    {
        return require __DIR__ . '/../../database/migrations/2026_11_05_000000_add_attribute_changes_column_to_activity_log_table.php';
    }
}
