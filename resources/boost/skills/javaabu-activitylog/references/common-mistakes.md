# Common mistakes with javaabu/activitylog

## v1 static properties

```php
// Wrong (v1): throws UnsupportedLogProperty
protected static array $logAttributes = ['name'];
protected static array $logExceptAttributes = ['password'];

// Right
public function getActivitylogOptions(): LogOptions
{
    return $this->defaultActivitylogOptions()->logOnly(['name']);
}
```

## spatie v4 names

```php
// Wrong (spatie v4)
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
$model->activities;
$log->changes;
$log->getExtraProperty('ip');
LogOptions::defaults()->dontSubmitEmptyLogs();

// Right (spatie v5)
use Spatie\Activitylog\Support\LogOptions;
use Javaabu\Activitylog\Traits\LogsActivity;
$model->activitiesAsSubject;
$log->changes();           // or newValues() / oldValues()
$log->getProperty('ip');   // the package also keeps $log->ip
LogOptions::defaults()->dontLogEmptyChanges();
```

## Losing the default exclusions

```php
// Wrong: hidden attributes, passwords and timestamps are logged again
return $this->defaultActivitylogOptions()->logExcept(['internal_notes']);

// Right
return $this->defaultActivitylogOptions()->logExcept([...$this->activitylogExceptAttributes(), 'internal_notes']);
```

## Storing sentences

```php
// Wrong: English forever, no tag
activity()->performedOn($invoice)->log("{$user->name} sent invoice {$invoice->number}");

// Right: an event, described when shown
activity()->performedOn($invoice)->event('sent')->log('sent');
Activitylog::describe('sent', ':causer sent :type :subject');
```

## Changes in properties

```php
// Works (read as a fallback), but diffs and rollback treat it as custom data in new code
activity()->withProperties(['attributes' => [...], 'old' => [...]])->log('updated');

// Right
activity()->event('updated')->withChanges(['attributes' => [...], 'old' => [...]])->log('updated');
```

## Unscoped log queries

```php
// Wrong: shows every log to anyone who can open the page
$logs = Activity::latest()->paginate();

// Right
$logs = Activity::visibleTo($request->user())->latest()->paginate();
```

## Hand-made rollback

```php
// Wrong: ignores later changes, hidden attributes and authorization, and isn't logged
$invoice->update($log->oldValues());

// Right
abort_unless($request->user()->can('rollback', $log), 403);
$log->rollback()->perform($request->user());
```

## Loading the routes

The package doesn't load routes. Register them in the app's routes file, inside its middleware group:

```php
Route::middleware('web')->group(fn () => Route::activitylog(['guard' => 'web_admin', 'prefix' => 'admin/logs', 'name' => 'admin.logs']));
```

## Publishing spatie's files

Don't run spatie's `vendor:publish` tags: this package's config is a superset of spatie's (with its own `activity_model`), and its migrations replace spatie's.
