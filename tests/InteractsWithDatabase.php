<?php

namespace Javaabu\Activitylog\Tests;

trait InteractsWithDatabase
{
    protected function runMigrations()
    {
        include_once __DIR__ . '../../database/migrations/2024_02_05_223412_create_activity_log_table.php';
        include_once __DIR__ . '../../database/migrations/2024_02_05_223413_add_event_column_to_activity_log_table.php';
        include_once __DIR__ . '../../database/migrations/2024_02_05_223414_add_batch_uuid_column_to_activity_log_table.php';
        include_once __DIR__ . '/database/create_categories_table.php';

        (new \CreateActivityLogTable)->up();
        (new \AddEventColumnToActivityLogTable)->up();
        (new \AddBatchUuidColumnToActivityLogTable)->up();
        (new \CreateCategoriesTable)->up();
    }
}
