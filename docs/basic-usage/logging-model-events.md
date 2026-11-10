---
title: Logging model events
sidebar_position: 1
---

Add the `LogsActivity` trait to a model to log when it's created, updated, deleted and (with soft deletes) restored:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use LogsActivity;

    protected $fillable = ['number', 'customer_name', 'status', 'total'];
}
```

Each log records the model (the *subject*), the user who made the change (the *causer*, from the current guard), the event, the IP address and the attributes that changed:

```php
$invoice->update(['status' => 'paid']);

$log = $invoice->activitiesAsSubject()->latest('id')->first();

$log->event;        // 'updated'
$log->causer;       // the logged-in user, whichever guard they use
$log->ip;           // '203.0.113.7'
$log->newValues();  // ['status' => 'paid']
$log->oldValues();  // ['status' => 'draft']
```

# What is logged by default

You don't need to configure anything. The trait's defaults are:

| Default | Why |
| --- | --- |
| The model's `$fillable` attributes, or every attribute when it has no `$fillable` | Fillable attributes are what users change |
| Only the attributes that changed (`logOnlyDirty()`) | Logs show the change, not the whole model |
| Never hidden attributes, the primary key, timestamps or `activitylog.sensitive_attributes` | Passwords, tokens and secrets stay out of logs, and timestamps are on the log itself |
| No log when nothing logged changed (`dontLogEmptyChanges()`) | Saving a model without changes adds nothing |
| No log when only timestamps changed (`dontLogIfAttributesChangedOnly()`) | `touch()` adds nothing |

`activitylog.sensitive_attributes` takes patterns, and covers `password`, `remember_token`, `*_token`, `*_secret`, `two_factor_recovery_codes` and `api_key` out of the box.

# Customizing what is logged

Override `getActivitylogOptions()` and start from the defaults with `defaultActivitylogOptions()`. It returns spatie's `LogOptions`, so every [spatie option](https://spatie.be/docs/laravel-activitylog/v5/advanced-usage/logging-model-events) works:

```php
use Spatie\Activitylog\Support\LogOptions;

class Invoice extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return $this->defaultActivitylogOptions()
            ->logOnly(['status', 'total', 'customer.name'])
            ->dontLogIfAttributesChangedOnly(['views', 'updated_at'])
            ->useLogName('billing');
    }
}
```

| To | Call |
| --- | --- |
| Log only some attributes | `->logOnly(['status', 'total'])` |
| Log every attribute | `->logAll()` |
| Log a related model's attribute | `->logOnly(['customer.name'])` |
| Log a key inside a JSON column | `->logOnly(['meta->colour'])` |
| Leave attributes out | `->logExcept([...$this->activitylogExceptAttributes(), 'internal_notes'])` |
| Skip a log when only these changed | `->dontLogIfAttributesChangedOnly(['views', 'updated_at'])` |
| Use another log name | `->useLogName('billing')` |
| Store the raw value instead of the cast one | `->useAttributeRawValues(['total'])` |

`logExcept()` replaces the list, so merge `activitylogExceptAttributes()` back in, as above, to keep hidden and sensitive attributes out.

To choose which events are logged, use spatie's static properties, which v2 still reads:

```php
protected static array $recordEvents = ['created', 'updated'];
// or
protected static array $doNotRecordEvents = ['deleted'];
```

# Logging other things

Use spatie's `activity()` helper for anything that isn't a model event. Set an `event` so the log gets a [tag](tags.md) and a [readable description](descriptions.md):

```php
activity()
    ->performedOn($invoice)
    ->causedBy($request->user())
    ->event('sent')
    ->withProperties(['email' => $invoice->customer_email])
    ->log('sent');
```

To log changes yourself, pass them with `withChanges()`, so diffs and [rollback](rolling-back.md) understand them:

```php
activity()
    ->performedOn($user)
    ->event('role_updated')
    ->withChanges([
        'attributes' => ['role' => 'editor'],
        'old' => ['role' => 'viewer'],
    ])
    ->log('role_updated');
```

Logs that keep changes in `properties.attributes` and `properties.old` (v1 logs, and packages that log with `withProperties()`) are read the same way.

# Turning logging off

```php
// For one model instance
$invoice->disableLogging()->update(['views' => $invoice->views + 1]);

// For a block of code
activity()->withoutLogging(fn () => Invoice::query()->update(['views' => 0]));
```

Set `ACTIVITYLOG_ENABLED=false` to turn it off everywhere.

# Reading logs

| Call | Gives |
| --- | --- |
| `$model->activitiesAsSubject` | The model's logs (spatie) |
| `$user->activitiesAsCauser` | The logs a user caused, with spatie's `CausesActivity` trait |
| `$log->changes()` | `['attributes' => [...], 'old' => [...]]` |
| `$log->newValues()` / `$log->oldValues()` | The new or old values |
| `$log->changedAttributes()` | The names of the attributes the log has values for |
| `$log->hasLoggedChanges()` | Whether it has any |
| `Activity::forSubject($model)`, `causedBy($user)`, `forEvent('updated')`, `inLog('billing')` | Spatie's scopes |
