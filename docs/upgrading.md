---
title: Upgrading from v1
sidebar_position: 1.25
---

v2 moves to spatie/laravel-activitylog v5, replaces the static model properties with spatie's `getActivitylogOptions()`, drops javaabu/helpers, and adds tags, readable descriptions, log pages, rollback and exports.

# Requirements

PHP 8.4 or 8.5, and Laravel 12 or 13.

```bash
composer require javaabu/activitylog:^2.0
php artisan migrate
php artisan permissions:sync
```

# The database

v1's three migrations stay as they were. v2 adds one that:

1. adds the `attribute_changes` column spatie v5 keeps model changes in, and
2. moves `attributes` and `old` out of each log's `properties` into it, in chunks of 500.

Its `down()` moves them back. Large tables take a while; run it in a maintenance window if you have millions of logs.

The `batch_uuid` column is no longer used (spatie v5 dropped batches). It stays, so nothing is lost; drop it yourself when you no longer need it:

```php
Schema::table('activity_log', fn (Blueprint $table) => $table->dropColumn('batch_uuid'));
```

If you published v1's migrations, publish v2's too (`--tag=activitylog-migrations`) and copy the new one.

# Models: static properties become LogOptions

v1 read three static properties. v2 reads spatie's `getActivitylogOptions()`, and throws `Javaabu\Activitylog\Exceptions\UnsupportedLogProperty` if a model still has one of the old properties, so nothing is logged differently by accident (dropping `$logExceptAttributes` silently could start logging a password).

| v1 | v2 |
| --- | --- |
| `protected static array $logAttributes = ['name', 'slug'];` | `->logOnly(['name', 'slug'])` |
| `protected static array $ignoreChangedAttributes = ['updated_at'];` | `->dontLogIfAttributesChangedOnly(['updated_at'])` |
| `protected static array $logExceptAttributes = ['password'];` | `->logExcept([...$this->activitylogExceptAttributes(), 'password'])` |

Before:

```php
use Javaabu\Activitylog\Traits\LogsActivity;

class Category extends Model
{
    use LogsActivity;

    protected static array $logAttributes = ['name', 'slug'];
    protected static array $ignoreChangedAttributes = ['created_at', 'updated_at'];
    protected static array $logExceptAttributes = ['secret'];
}
```

After:

```php
use Javaabu\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Category extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return $this->defaultActivitylogOptions()
            ->logOnly(['name', 'slug']);
    }
}
```

Often you can just delete the properties: the defaults already skip timestamps, hidden attributes and passwords. See [Logging model events](basic-usage/logging-model-events.md#what-is-logged-by-default).

## What the defaults changed

| | v1 | v2 |
| --- | --- | --- |
| Attributes | Every attribute | `$fillable`, or every attribute when there's no `$fillable` |
| Hidden attributes | Logged unless listed in `$logExceptAttributes` | Never logged |
| Passwords, tokens, secrets | Logged unless listed | Never logged (`activitylog.sensitive_attributes`) |
| Primary key, `created_at`, `updated_at`, `deleted_at` | Logged | Not logged |
| Timestamp-only changes | Logged | Skipped |

The trait keeps its namespace, `Javaabu\Activitylog\Traits\LogsActivity`.

If you overrode `getActivitylogOptions()` in v1, change the `LogOptions` import:

```php
use Spatie\Activitylog\LogOptions;          // v1 (spatie v4)
use Spatie\Activitylog\Support\LogOptions;  // v2 (spatie v5)
```

and `dontSubmitEmptyLogs()` to `dontLogEmptyChanges()`.

## canViewActivityLogs()

v1's `canViewActivityLogs(Authorizable $user)` on models (by default `$user->can('create', Model::class)`) is gone. With `view_logs`, a user now sees the logs of models they may `viewAny`. To decide yourself, add a static method:

```php
public static function activitylogViewableBy(Model $user): bool
{
    return $user->can('create', static::class);
}
```

# spatie v4 → v5

Code that uses spatie directly needs spatie's own [upgrade guide](https://github.com/spatie/laravel-activitylog/blob/v5/UPGRADING.md). The changes you're most likely to meet:

| v1 (spatie v4) | v2 (spatie v5) |
| --- | --- |
| `$model->activities` | `$model->activitiesAsSubject` |
| `$user->actions` | `$user->activitiesAsCauser` |
| `$activity->changes()` / `$activity->changes` | `$activity->changes()` still works on the package's model (it reads `attribute_changes`, and `properties` for older logs); `$activity->attribute_changes` is spatie's |
| `$activity->getExtraProperty('x')` | `$activity->getProperty('x')` |
| `Spatie\Activitylog\Traits\LogsActivity` | `Spatie\Activitylog\Models\Concerns\LogsActivity` (or keep using the package's trait) |
| `CauserResolver::setCauser($user)` | `Activity::defaultCauser($user)` |
| `LogBatch` | Removed; group logs with a property |
| `tapActivity()` on models | `beforeActivityLogged()` |

# Config

Republish the config, or rename these keys in yours:

| v1 | v2 |
| --- | --- |
| `delete_records_older_than_days` | `clean_after_days` |
| `subject_returns_soft_deleted_models` | `include_soft_deleted_subjects` |
| `ACTIVITY_LOGGER_*` env variables | `ACTIVITYLOG_*` (the old ones still work) |

`activity_model` must be `Javaabu\Activitylog\Models\Activity` or a subclass. `table_name` and `database_connection` still work (spatie v5 dropped them; the package keeps them).

# The Activity model

| v1 | v2 |
| --- | --- |
| Implemented javaabu/helpers' `AdminModel` (`admin_url`, `admin_link_name`) | Doesn't. The log pages give each log's URL; use `route('admin.logs.show', $log)` |
| `scopeSearch($query, $search)` matched the description exactly | Removed; the index's search filter searches descriptions and changes |
| `scopeUserVisible()` read the logged-in user | `visibleTo($user)`, with the user passed in |
| `allowedSubjects($user)` | `Activitylog::viewableSubjectTypes($user)` |
| `scopeWithRelations()` | Removed; use `->with(['causer', 'subject'])` |

# SubjectTypes and CauserTypes

`Javaabu\Activitylog\SubjectTypes` and `CauserTypes` are removed. The type filter lists the subject types that have logs, labelled by each model's `activitylogLabel()` or class name; see [Log pages](basic-usage/log-pages.md#filters).

# Permissions

v1 left authorization to the app. v2 registers `view_logs`, `view_all_logs` (the name v1's `userVisible()` checked), `rollback_logs` and `export_logs`, and a policy for the activity model. Run `php artisan permissions:sync` and give them to roles. If you had your own policy for the activity model, keep it registered with `Gate::policy()` or `config('permissions.policies')`; see [Permissions and policies](advanced-usage/permissions-and-policies.md).

# Your log pages

If your app had its own log controller and views (`admin.logs.index`, `admin.logs.show`), you can keep them, or replace them with the package's:

```php
Route::activitylog(['guard' => 'web_admin', 'prefix' => 'admin/logs', 'name' => 'admin.logs']);
```

The route names match v1's `admin.logs.show`.

# javaabu/permissions v3

javaabu/permissions v3 logs role and permission changes through `activity()` when spatie's package is installed. That keeps working on v5, and the package gives those logs tags and descriptions.
