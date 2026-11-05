---
name: javaabu-activitylog
description: Log model changes and other activity with javaabu/activitylog (spatie/laravel-activitylog v5) in a Laravel app, and show, roll back and export the logs. Use whenever the project depends on javaabu/activitylog and the task adds logging to a model, chooses which attributes are logged, logs a custom event, reads a model's or user's logs, shows a record's history, registers or customizes the activity log pages, adds tags or readable descriptions, rolls a record back, exports logs, or changes who may see logs.
license: MIT
metadata:
  author: Javaabu
---

# Activity logs with javaabu/activitylog

The package is a layer over spatie/laravel-activitylog v5: everything spatie does works, with safer defaults, tags, readable descriptions, log pages, rollback and exports on top. The docs ship with the package: `vendor/javaabu/activitylog/llms.txt` lists every page in `vendor/javaabu/activitylog/docs/`.

## Logging a model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Invoice extends Model
{
    use LogsActivity;

    protected $fillable = ['number', 'customer_id', 'status', 'total', 'internal_notes'];

    // Only when the defaults aren't right
    public function getActivitylogOptions(): LogOptions
    {
        return $this->defaultActivitylogOptions()
            ->logExcept([...$this->activitylogExceptAttributes(), 'internal_notes'])
            ->dontLogIfAttributesChangedOnly(['views', 'updated_at']);
    }

    // Optional: how it reads in descriptions and pages
    public static function activitylogLabel(): string
    {
        return __('Invoice');
    }

    public function activitylogDisplayName(): string
    {
        return "#{$this->number}";
    }

    public function activitylogUrl(): ?string
    {
        return route('admin.invoices.show', $this);
    }
}
```

The defaults: `$fillable` attributes (all attributes without `$fillable`), only what changed, never hidden attributes, the key, timestamps or `activitylog.sensitive_attributes` (passwords, tokens, secrets), and no log when nothing (or only timestamps) changed. Often no `getActivitylogOptions()` is needed.

## Logging anything else

```php
activity()
    ->performedOn($invoice)
    ->causedBy($request->user())
    ->event('sent')                                  // always set an event: it picks the tag and description
    ->withProperties(['email' => $invoice->email])   // custom data
    ->log('sent');

activity()->performedOn($user)->event('role_updated')
    ->withChanges(['attributes' => ['role' => 'editor'], 'old' => ['role' => 'viewer']]) // changes go in withChanges()
    ->log('role_updated');
```

## Reading logs

```php
$invoice->activitiesAsSubject()->latest('id')->get();    // spatie v5 names
Activity::visibleTo($request->user())->tagged('updated')->paginate();

$log->newValues();      // ['status' => 'paid']
$log->oldValues();      // ['status' => 'draft']
$log->tag();            // Tag: key(), label(), color(), icon()
$log->describe();       // "Ahmed updated the status of Invoice #12 from Draft to Paid" (reader's locale)
```

## Pages, history, rollback, exports

```php
// routes/web.php, inside the app's group
Route::activitylog(['guard' => 'web_admin', 'prefix' => 'admin/logs', 'name' => 'admin.logs']);
```

```blade
<x-activitylog::history :subject="$invoice" route="admin.logs" />
```

```php
// Inertia: send it with the page, render <ActivityHistory history={history} /> from @javaabu/activitylog
'history' => Activitylog::history($invoice, $request->user(), route: 'admin.logs'),
```

```php
if ($user->can('rollback', $log)) {
    $log->rollback()->perform($user);   // restores the version right after $log, logs `rolled_back`
}
```

Permissions: `view_logs` (logs of records the user may `viewAny`, and their own), `view_all_logs`, `rollback_logs` (plus `update` on the record), `export_logs`. Run `php artisan permissions:sync`. Exports go through javaabu/exports (`activity_logs`).

## Tags and descriptions for your events

```php
// AppServiceProvider::boot()
Activitylog::registerTag('sent', label: 'Sent', color: 'info', icon: 'mail');
Activitylog::describe('sent', ':causer sent :type :subject', Invoice::class);
```

Add both strings to `lang/dv.json`. See [references/tags-and-descriptions.md](references/tags-and-descriptions.md).

## Rules

- Never add v1's static properties; override `getActivitylogOptions()` from `defaultActivitylogOptions()`.
- `logExcept()` replaces the list: spread `$this->activitylogExceptAttributes()` into it.
- Use spatie v5 APIs: `activitiesAsSubject`, `activitiesAsCauser`, `getProperty()`, `dontLogEmptyChanges()`, `Spatie\Activitylog\Support\LogOptions`. There's no `LogBatch`, `changes` property or `getExtraProperty()`.
- Don't put sentences in `->log()` for model-like events; set an `event` and register a description, so it's translated for each reader.
- Don't query `subject_type`/`subject_id` by hand for a history, and don't restore old values by hand; use `history()`/`<x-activitylog::history>` and `rollback()`.
- Always scope log queries shown to users with `visibleTo($user)`.
- Don't publish spatie's config or migration; this package's replace them.

See [references/common-mistakes.md](references/common-mistakes.md) for more.
