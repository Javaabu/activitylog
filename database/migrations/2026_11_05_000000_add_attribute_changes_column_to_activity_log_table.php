<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * spatie/laravel-activitylog v5 keeps model changes in their own column. Logs written
 * by javaabu/activitylog v1 kept them in properties.attributes and properties.old;
 * this moves them across.
 */
return new class extends Migration
{
    public function up(): void
    {
        $connection = config('activitylog.database_connection');
        $table = config('activitylog.table_name', 'activity_log');

        if (! Schema::connection($connection)->hasColumn($table, 'attribute_changes')) {
            Schema::connection($connection)->table($table, function (Blueprint $blueprint) {
                $blueprint->json('attribute_changes')->nullable()->after('causer_id');
            });
        }

        DB::connection($connection)
            ->table($table)
            ->whereNotNull('properties')
            ->whereNull('attribute_changes')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($connection, $table) {
                foreach ($rows as $row) {
                    $properties = json_decode((string) $row->properties, true);

                    if (! is_array($properties)) {
                        continue;
                    }

                    $changes = array_intersect_key($properties, array_flip(['attributes', 'old']));

                    if (! $changes) {
                        continue;
                    }

                    $remaining = array_diff_key($properties, $changes);

                    DB::connection($connection)
                        ->table($table)
                        ->where('id', $row->id)
                        ->update([
                            'attribute_changes' => json_encode($changes),
                            'properties' => $remaining ? json_encode($remaining) : null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        $connection = config('activitylog.database_connection');
        $table = config('activitylog.table_name', 'activity_log');

        DB::connection($connection)
            ->table($table)
            ->whereNotNull('attribute_changes')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($connection, $table) {
                foreach ($rows as $row) {
                    $properties = json_decode((string) $row->properties, true) ?: [];
                    $changes = json_decode((string) $row->attribute_changes, true) ?: [];

                    DB::connection($connection)
                        ->table($table)
                        ->where('id', $row->id)
                        ->update(['properties' => json_encode($properties + $changes)]);
                }
            });

        Schema::connection($connection)->table($table, function (Blueprint $blueprint) {
            $blueprint->dropColumn('attribute_changes');
        });
    }
};
