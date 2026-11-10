---
title: Configuration
sidebar_position: 8
---

```bash
php artisan vendor:publish --tag=activitylog-config
```

`config/activitylog.php` holds spatie/laravel-activitylog's options and the package's own, under the same `activitylog` key. Publish this file rather than spatie's. Every option has a default; the common case needs none of them.

# spatie/laravel-activitylog's options

| Option | Default | What |
| --- | --- | --- |
| `enabled` | `env('ACTIVITYLOG_ENABLED', true)` | Turn logging off. v1's `ACTIVITY_LOGGER_ENABLED` still works. |
| `default_log_name` | `'default'` | The log name when none is given |
| `default_auth_driver` | `null` | The guard the causer comes from; `null` is the current one |
| `clean_after_days` | `365` | What `activitylog:clean` deletes |
| `include_soft_deleted_subjects` | `false` | Whether `$log->subject` finds soft-deleted records. Rollback and the pages find them either way. |
| `activity_model` | `Javaabu\Activitylog\Models\Activity` | The model. A custom one must extend the package's. |
| `default_except_attributes` | `[]` | Never logged, for every model, including models using spatie's trait directly |
| `buffer.enabled` | `env('ACTIVITYLOG_BUFFER_ENABLED', false)` | Insert logs in bulk after the response |
| `actions.log_activity`, `actions.clean_log` | spatie's actions | Replace how logs are saved and cleaned |

# The package's options

| Option | Default | What |
| --- | --- | --- |
| `table_name` | `env('ACTIVITYLOG_TABLE_NAME', 'activity_log')` | The table (v1's `ACTIVITY_LOGGER_TABLE_NAME` still works) |
| `database_connection` | `env('ACTIVITYLOG_DB_CONNECTION')` | The connection; `null` is the default |
| `sensitive_attributes` | `password`, `remember_token`, `*_token`, `*_secret`, `two_factor_recovery_codes`, `api_key` | Left out by `LogsActivity`'s default options, with the model's hidden attributes. Patterns allowed. |
| `log_ip` | `true` | Save the request's IP address with each log |
| `tags` | `[]` | Tags to add or change: `key => ['label' => ..., 'color' => ..., 'icon' => ...]`. See [Tags](../basic-usage/tags.md). |
| `descriptions` | `[]` | Description templates: `event => template`, or `Model::class => [event => template]`. See [Descriptions](../basic-usage/descriptions.md). |
| `describer` | `Describer::class` | The class that writes descriptions |
| `rollback.enabled` | `true` | Allow rolling back, and register the rollback routes |
| `rollback.except` | `[]` | Attributes never restored |
| `export.enabled` | `true` | The Export button, and the `activity_logs` export |
| `export.queue` | `true` | Queue exports when `export.exports_route` exists; otherwise download them |
| `export.exports_route` | `'exports.index'` | Where to send users after queuing an export |
| `export.class` | `ActivityLogExport::class` | The export |
| `views` | `env('ACTIVITYLOG_VIEWS', 'blade')` | `blade` or `inertia` |
| `layout` | `'layouts.app'` | The layout the Blade pages extend |
| `layout_section` | `'content'` | The section they fill |
| `per_page` | `20` | Logs per page (a `per_page` query parameter can ask for up to 100) |
| `controllers.logs` | `ActivityLogsController::class` | The controller `Route::activitylog()` uses |
| `filters` | `ActivityFilters::class` | The filters of the index and exports |
| `policy` | `ActivityPolicy::class` | The policy registered for the activity model |
| `register_permissions` | `true` | Register the permissions and the policy with javaabu/permissions |
