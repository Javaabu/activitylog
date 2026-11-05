<?php

use Javaabu\Activitylog\Models\Activity;
use Spatie\Activitylog\Actions\CleanActivityLogAction;
use Spatie\Activitylog\Actions\LogActivityAction;

/*
 * spatie/laravel-activitylog reads this file too: its options come first,
 * then the ones javaabu/activitylog adds.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Turn logging off with ACTIVITYLOG_ENABLED=false. Logs without a log name
    | go to default_log_name. With default_auth_driver null, the causer is the
    | user of the current guard.
    |
    */

    'enabled' => env('ACTIVITYLOG_ENABLED', env('ACTIVITY_LOGGER_ENABLED', true)),

    'default_log_name' => 'default',

    'default_auth_driver' => null,

    /*
    |--------------------------------------------------------------------------
    | Cleaning
    |--------------------------------------------------------------------------
    |
    | `php artisan activitylog:clean` deletes logs older than this many days.
    |
    */

    'clean_after_days' => 365,

    /*
    |--------------------------------------------------------------------------
    | Subjects
    |--------------------------------------------------------------------------
    |
    | Whether $activity->subject finds soft deleted models.
    |
    */

    'include_soft_deleted_subjects' => false,

    /*
    |--------------------------------------------------------------------------
    | The activity model and table
    |--------------------------------------------------------------------------
    |
    | A custom model must extend Javaabu\Activitylog\Models\Activity. The table
    | and connection are the ones v1 used; null connection is the default.
    |
    */

    'activity_model' => Activity::class,

    'table_name' => env('ACTIVITYLOG_TABLE_NAME', env('ACTIVITY_LOGGER_TABLE_NAME', 'activity_log')),

    'database_connection' => env('ACTIVITYLOG_DB_CONNECTION', env('ACTIVITY_LOGGER_DB_CONNECTION')),

    /*
    |--------------------------------------------------------------------------
    | Attributes never logged
    |--------------------------------------------------------------------------
    |
    | default_except_attributes is left out of every model's logs, including
    | models that use spatie's trait directly. sensitive_attributes (patterns
    | allowed) and the model's hidden attributes are left out by the default
    | options of javaabu/activitylog's LogsActivity trait.
    |
    */

    'default_except_attributes' => [],

    'sensitive_attributes' => [
        'password',
        'remember_token',
        '*_token',
        '*_secret',
        'two_factor_recovery_codes',
        'api_key',
    ],

    /*
    |--------------------------------------------------------------------------
    | Buffering and actions (spatie/laravel-activitylog)
    |--------------------------------------------------------------------------
    */

    'buffer' => [
        'enabled' => env('ACTIVITYLOG_BUFFER_ENABLED', false),
    ],

    'actions' => [
        'log_activity' => LogActivityAction::class,
        'clean_log' => CleanActivityLogAction::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | IP addresses
    |--------------------------------------------------------------------------
    |
    | Save the request's IP address with each log.
    |
    */

    'log_ip' => true,

];
