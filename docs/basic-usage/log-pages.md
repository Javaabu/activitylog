---
title: Log pages
sidebar_position: 4
---

`Route::activitylog()` adds the activity log to your app:

| Route | Name | Page |
| --- | --- | --- |
| `GET /logs` | `logs.index` | The logs the user may see, newest first, with filters and an Export button |
| `GET /logs?subject_type=post&subject_id=3` | `logs.index` | One record's history |
| `GET /logs/{log}` | `logs.show` | A log: its description, who, when, the IP address, the changes and its properties |
| `GET /logs/{log}/rollback` | `logs.rollback.confirm` | What rolling back to the log would change ([Rolling back](rolling-back.md)) |
| `POST /logs/{log}/rollback` | `logs.rollback` | Roll back |

Every route answers JSON when the request expects it (`Accept: application/json`), and a Blade or Inertia React page otherwise.

# Registering the routes

Register them inside your route group, so they get your middleware:

```php
// routes/web.php
Route::middleware('web')->group(function () {
    Route::activitylog();
});
```

| Option | Default | What |
| --- | --- | --- |
| `prefix` | `logs` | The URL prefix |
| `name` | `logs` | The route name prefix: `{name}.index`, `{name}.show`, ... |
| `guard` | `null` (the default guard) | The guard the routes are for; the middleware becomes `auth:{guard}` |
| `middleware` | `['auth']` or `['auth:{guard}']` | Replaces the auth middleware |
| `controller` | `activitylog.controllers.logs` | A controller class for these routes |
| `rollback` | `activitylog.rollback.enabled` | Whether to add the rollback routes |

For an admin panel on its own guard:

```php
Route::middleware('web')->group(function () {
    Route::activitylog([
        'guard' => 'web_admin',
        'prefix' => 'admin/logs',
        'name' => 'admin.logs',
        'middleware' => ['auth:web_admin', 'password.confirm'],
    ]);
});
```

You can register them more than once, for different guards. Links on the pages follow the name prefix of the route that rendered them.

# Who sees what

The pages need `view_logs` or `view_all_logs`. With `view_all_logs`, a user sees every log. With `view_logs`, they see the logs of records they may see (`viewAny` on the record's model) and the logs they caused. See [Permissions and policies](../advanced-usage/permissions-and-policies.md).

# Filters

The index has a [javaabu/filters](https://github.com/Javaabu/filters) bar:

| Filter | Query string | What |
| --- | --- | --- |
| Search | `search` | The description, event and log name, and values in the changes and properties (Thaana too) |
| Tag | `tag[]` | One or more [tags](tags.md) |
| Type | `subject_type` | The kind of record, by its morph type |
| Log | `log_name` | Shown when there's more than one log name |
| Date | `created_at_from`, `created_at_to` | A date range |
| (hidden) | `subject_id` | With `subject_type`: one record's history |
| (hidden) | `causer_type`, `causer_id` | What one user did |

```blade
<a href="{{ route('admin.logs.index', ['causer_type' => $user->getMorphClass(), 'causer_id' => $user->id]) }}">
    {{ __('Activity') }}
</a>
```

To change the filters, extend `Javaabu\Activitylog\Filters\ActivityFilters` and set `activitylog.filters`:

```php
namespace App\Filters;

use Javaabu\Activitylog\Filters\ActivityFilters as BaseActivityFilters;
use Javaabu\Filters\SelectFilter;

class ActivityFilters extends BaseActivityFilters
{
    protected function filters(): array
    {
        return [
            ...parent::filters(),
            SelectFilter::make('ip')->label(__('IP address'))->options(fn () => ['127.0.0.1' => 'localhost']),
        ];
    }
}
```

# A record's history on your own pages

## Blade

```blade
<x-activitylog::history :subject="$invoice" />

{{-- more logs, a title, and the route name prefix the log routes were registered with --}}
<x-activitylog::history :subject="$invoice" :limit="20" :title="__('Invoice history')" route="admin.logs" />
```

It shows the latest logs the current user may see, in a card, with an "All activity" link to the record's history page when the user may see the log pages.

## Inertia React

Send the history with the page, and render it with `ActivityHistory`:

```php
use Javaabu\Activitylog\Activitylog;

return Inertia::render('Invoices/Show', [
    'invoice' => $invoice,
    'history' => Activitylog::history($invoice, $request->user(), limit: 10, route: 'admin.logs'),
]);
```

```tsx
import { ActivityHistory, type ActivityHistoryData } from "@javaabu/activitylog"

export default function Show({ history }: { history: ActivityHistoryData }) {
  return <ActivityHistory history={history} />
}
```

## In PHP

```php
Activitylog::historyQuery($invoice, $user)->paginate();  // the logs the user may see, newest first
Activity::visibleTo($user)->forSubject($invoice)->get(); // the same, with spatie's scope
```

# Components

| Blade | React (`@javaabu/activitylog`) | What |
| --- | --- | --- |
| `<x-activitylog::tag :tag="$log->tag()" />` | `<ActivityTag tag={log.tag} />` | A [tag](tags.md) |
| `{{ $log->describe() }}` | `<ActivityDescription log={log} />` | The [description](descriptions.md), with `<bdi>` values |
| `@include('activitylog::logs.partials.table', ['logs' => $logs, 'routes' => $routes])` | `<ActivityTable logs={logs} strings={strings} />` | A table of logs |
| `@include('activitylog::logs.partials.diff', ['changes' => $rows])` | `<ActivityDiff changes={rows} strings={strings} />` | Attribute changes |
| `@include('activitylog::logs.partials.details', ['log' => $log, 'routes' => $routes])` | `<ActivityDetails log={log} strings={strings} />` | Tag, date, user, record, IP, log name |
| `<x-activitylog::history :subject="$model" />` | `<ActivityHistory history={history} />` | A record's latest logs |

The React components take the JSON of `ActivityResource` (`ActivityData` in TypeScript) and translated strings (`Activitylog::strings()`, `ActivitylogStrings`). The types are exported from `@javaabu/activitylog`.

# The JSON API

```bash
curl -H "Accept: application/json" https://example.test/admin/logs?tag[]=updated
```

The index returns a paginated collection (`per_page` up to 100, default `activitylog.per_page`); the show route returns one log with `changes`, `properties` and `rollback`:

```json
{
    "data": {
        "id": 12,
        "event": "updated",
        "log_name": "default",
        "tag": { "key": "updated", "label": "Updated", "color": "info", "icon": "pencil", "svg": "<path .../>" },
        "description": "Ahmed updated the status of Invoice #12 from Draft to Paid",
        "description_html": "<bdi>Ahmed</bdi> updated the <bdi>status</bdi> of <bdi>Invoice</bdi> <bdi>#12</bdi> from <bdi>Draft</bdi> to <bdi>Paid</bdi>",
        "causer": { "type": "admin", "type_label": "Admin", "id": 1, "name": "Ahmed", "exists": true, "url": null },
        "subject": { "type": "invoice", "type_label": "Invoice", "id": 12, "name": "#12", "exists": true, "url": "https://example.test/admin/invoices/12" },
        "changed_attributes": ["status"],
        "changes": [{ "attribute": "status", "label": "Status", "old": "Draft", "new": "Paid", "changed": true }],
        "properties": {},
        "ip": "203.0.113.7",
        "can": { "view": true, "rollback": true },
        "rollback": { "possible": true, "reason": null },
        "links": { "show": "...", "rollback": "...", "history": "..." },
        "created_at": "2026-11-05T09:30:00+00:00",
        "created_at_for_humans": "2 hours ago",
        "created_at_formatted": "November 5, 2026 9:30 AM"
    }
}
```

A model's `activitylogUrl()` gives the `url` of a causer or subject, which the pages link to:

```php
public function activitylogUrl(): ?string
{
    return route('admin.invoices.show', $this);
}
```

# Choosing Blade or Inertia React

```dotenv
ACTIVITYLOG_VIEWS=inertia
```

Both need a little setup in the app; see [Overriding controllers and views](../advanced-usage/overriding-controllers-and-views.md#setting-up-the-pages), which also lists every page's props.
