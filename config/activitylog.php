<?php

use Javaabu\Activitylog\Descriptions\Describer;
use Javaabu\Activitylog\Exports\ActivityLogExport;
use Javaabu\Activitylog\Filters\ActivityFilters;
use Javaabu\Activitylog\Http\Controllers\ActivityLogsController;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Policies\ActivityPolicy;
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

    /*
    |--------------------------------------------------------------------------
    | Tags
    |--------------------------------------------------------------------------
    |
    | Each log is shown with the tag of its event (or, for logs without one,
    | its description): a label, a javaabu/forms status colour and an icon.
    | Add tags for your own events or change the built-in ones (created,
    | updated, deleted, restored, rolled_back, login, ...). Labels are
    | translated. Icons: see docs/basic-usage/tags.md.
    |
    */

    'tags' => [
        // 'published' => ['label' => 'Published', 'color' => 'success', 'icon' => 'circle-check'],
        // 'deleted' => ['color' => 'warning'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Descriptions
    |--------------------------------------------------------------------------
    |
    | Logs are described in the reader's language when they're shown: "Ahmed
    | updated the status of Invoice #12 from Draft to Paid". Describe your own
    | events here, for every model or for one. Templates are translated and
    | take :causer, :subject, :type, :attribute, :attributes, :count, :old,
    | :new, :tag, :date and :description. The describer can be replaced with
    | a subclass.
    |
    */

    'descriptions' => [
        // 'published' => ':causer published :type :subject',
        // App\Models\Invoice::class => ['paid' => ':causer marked :type :subject as paid'],
    ],

    'describer' => Describer::class,

    /*
    |--------------------------------------------------------------------------
    | Rollback
    |--------------------------------------------------------------------------
    |
    | Users with rollback_logs (and update on the record) can restore a record
    | to the version of a log. Attributes in except are never restored.
    |
    */

    'rollback' => [
        'enabled' => true,
        'except' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exports
    |--------------------------------------------------------------------------
    |
    | Users with export_logs can export the filtered logs with javaabu/exports
    | (registered as the `activity_logs` export). When queue is on and the
    | exports routes are registered (Route::exports(), named exports_route),
    | exports run on the queue and the user is sent to the exports page;
    | otherwise the file downloads right away.
    |
    */

    'export' => [
        'enabled' => true,
        'queue' => true,
        'exports_route' => 'exports.index',
        'class' => ActivityLogExport::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | The pages Route::activitylog() renders: blade (javaabu/forms components)
    | or inertia (React pages shipped through javaabu/manifest). Requests that
    | expect JSON always get JSON. The Blade pages extend layout and fill
    | layout_section.
    |
    */

    'views' => env('ACTIVITYLOG_VIEWS', 'blade'),

    'layout' => 'layouts.app',

    'layout_section' => 'content',

    'per_page' => 20,

    /*
    |--------------------------------------------------------------------------
    | Controllers, filters and authorization
    |--------------------------------------------------------------------------
    |
    | Replace the controller or filters with subclasses, or pass `controller`
    | to Route::activitylog(). The policy here is the package default; apps
    | can also override it with config('permissions.policies') or
    | Gate::policy(). Turn off register_permissions to register your own.
    |
    */

    'controllers' => [
        'logs' => ActivityLogsController::class,
    ],

    'filters' => ActivityFilters::class,

    'policy' => ActivityPolicy::class,

    'register_permissions' => true,

];
