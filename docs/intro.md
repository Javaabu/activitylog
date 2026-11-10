---
title: Introduction
sidebar_position: 1.0
---

# Activity Log

[javaabu/activitylog](https://github.com/Javaabu/activitylog) logs what happens to your models and shows it to the people who need to see it. It's built on [spatie/laravel-activitylog](https://spatie.be/docs/laravel-activitylog/v5) v5, so everything spatie does still works.

- **Sensible defaults.** Add `LogsActivity` to a model and its fillable attributes are logged when they change. Hidden attributes, passwords, tokens and timestamps stay out, and nothing is logged when nothing changed. Customize it with spatie's `getActivitylogOptions()`, starting from the defaults.
- **Tags.** Every log has a tag with a colour, an icon and a translated label: created, updated, deleted, rolled back, logged in, and your own events.
- **Readable descriptions.** "Ahmed updated the status of Invoice #12 from Draft to Paid", in the reader's language, customizable per model and event.
- **Log pages.** An activity log with filters (search, tag, type, date), a record's history, and each log with its changes, in Blade or Inertia React. A history component for your own pages.
- **Rollback.** Restore a record to the version of a log, after a confirmation that shows what will change. Rollbacks are authorized and logged.
- **Exports.** Export the filtered logs to Excel, CSV or ODS with [javaabu/exports](https://github.com/Javaabu/exports).
- **Permissions.** A policy and default permissions through [javaabu/permissions](https://github.com/Javaabu/permissions), for any guard and user model.
- **Dhivehi.** Every string is translated, the pages work right-to-left, and search finds Thaana.

```php
use Javaabu\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use LogsActivity;
}
```

```php
// routes/web.php
Route::middleware('web')->group(function () {
    Route::activitylog(['guard' => 'web_admin', 'prefix' => 'admin/logs', 'name' => 'admin.logs']);
});
```

```blade
{{-- on the invoice page --}}
<x-activitylog::history :subject="$invoice" route="admin.logs" />
```

Start with [Installation & Setup](installation-and-setup.md), then [Logging model events](basic-usage/logging-model-events.md). Coming from v1? Read [Upgrading](upgrading.md).
