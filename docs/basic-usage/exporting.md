---
title: Exporting logs
sidebar_position: 6
---

Users with `export_logs` see an **Export** button on the log index. It exports the logs they're looking at (the same filters, and only the logs they may see) with [javaabu/exports](https://github.com/Javaabu/exports), in any format exports has enabled.

| Column | |
| --- | --- |
| Date | In the app timezone, as javaabu/exports formats dates |
| Tag | The tag label |
| Description | The readable description |
| User | The causer |
| Type | The record's model label |
| Record | The record's name |
| Changes | One line per attribute: `Status: Draft → Paid` |
| IP address | |
| Log | The log name |

Headings and values are translated, so an export made in Dhivehi is in Dhivehi.

# Queued or straight away

When javaabu/exports' routes are registered (`Route::exports()`, with `exports.index` as the index route name), the export runs on the queue: the user is sent to the exports page and notified when the file is ready. Otherwise the file downloads straight away.

```php
// config/activitylog.php
'export' => [
    'enabled' => true,
    'queue' => true,                    // false: always download straight away
    'exports_route' => 'exports.index', // e.g. 'admin.exports.index'
    'class' => \Javaabu\Activitylog\Exports\ActivityLogExport::class,
],
```

# From the exports page and API

The export is registered with javaabu/exports as `activity_logs`, so it's in the exports page's list for users who may run it, and the exports API takes the filters:

```bash
curl -X POST https://example.test/exports \
    -H "Accept: application/json" \
    -d type=activity_logs -d format=csv -d "filters[tag][]=login"
```

# In code

```php
use Javaabu\Activitylog\Exports\ActivityLogExport;

// the logs $user may see, filtered like the index (the filters' query parameters)
$export = ActivityLogExport::for($user, ['tag' => ['updated'], 'created_at_from' => '2026-11-01']);

return $export->download(null, 'xlsx');
$export->queue($user, 'csv');
```

To change the columns, extend it and override `headings()` and `map()`, then set `activitylog.export.class`.

Turn exporting off with `activitylog.export.enabled` = `false`.
