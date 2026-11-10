---
title: Overriding controllers and views
sidebar_position: 2
---

# Setting up the pages

## Blade

The Blade pages use [javaabu/forms](https://github.com/Javaabu/forms) and [javaabu/filters](https://github.com/Javaabu/filters) components, so they follow your `forms.framework`. They extend your layout:

```php
// config/activitylog.php
'layout' => 'layouts.app',     // the view the pages @extends
'layout_section' => 'content', // the section they fill
```

The filter bar's date pickers and selects come through [javaabu/manifest](https://github.com/Javaabu/manifest); your app needs manifest's Vite plugin:

```js
// vite.config.js
import manifest from "./vendor/javaabu/manifest/dist/vite.js"

export default defineConfig({
    plugins: [
        laravel({ input: ["resources/css/app.css", "resources/js/app.js"] }),
        manifest(),
    ],
})
```

The pages use Tailwind utilities from Onu's theme (logical ones only, so they work right-to-left). Let Tailwind see them:

```css
/* resources/css/app.css */
@source "../../vendor/javaabu/activitylog/resources/views";
```

## Inertia React

The pages are `activitylog::Logs/Index`, `activitylog::Logs/Show` and `activitylog::Logs/Rollback`, written in TypeScript with [@javaabu/forms](https://github.com/Javaabu/react-forms), [@javaabu/onu](https://www.npmjs.com/package/@javaabu/onu) and javaabu/filters' `FilterBar`. javaabu/manifest ships them to your app; resolve them with `resolvePage()`:

```tsx
// resources/js/app.tsx
import { createInertiaApp } from "@inertiajs/react"
import { resolvePage } from "@javaabu/manifest/inertia"
import { createRoot } from "react-dom/client"

createInertiaApp({
    resolve: (name) => resolvePage(name, import.meta.glob("./pages/**/*.tsx")),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />)
    },
})
```

```bash
composer require inertiajs/inertia-laravel
php artisan manifest:check --scope=pages   # prints the install command for anything the pages need
```

Wrap your app in `FormsProvider` (with the Onu and Inertia adapters) as @javaabu/forms describes, so the filter bar and the rollback form submit as Inertia visits. The pages read `flash.success` and `flash.error` from your shared props for messages; share them from the session:

```php
// HandleInertiaRequests::share()
'flash' => [
    'success' => fn () => $request->session()->get('success'),
    'error' => fn () => $request->session()->get('error'),
],
```

# Overriding views

## Blade

Publish the views and edit the copies:

```bash
php artisan vendor:publish --tag=activitylog-views
```

| View | What |
| --- | --- |
| `activitylog::logs.index` | The index |
| `activitylog::logs.show` | A log |
| `activitylog::logs.rollback` | The rollback confirmation |
| `activitylog::logs.partials.table`, `row` | The table of logs |
| `activitylog::logs.partials.details` | Tag, date, user, record, IP, log name |
| `activitylog::logs.partials.diff` | Attribute changes |
| `activitylog::logs.partials.alerts` | Flash messages |
| `activitylog::components.tag` | `<x-activitylog::tag>` |
| `activitylog::components.history` | `<x-activitylog::history>` |

## Inertia React

Put a page with the same name in your app, and point a [custom controller](#overriding-the-controller) at it, or build your own from the exported components (`ActivityTable`, `ActivityDiff`, `ActivityDetails`, `ActivityTag`, `ActivityDescription`, `ActivityHistory`).

# Page props

Every page gets `title`, `routes` (route names under the prefix it was registered with: `index`, `show`, `rollback.confirm`, `rollback`), `urls` (`index`, and `show` and `rollback` for a log) and, for Inertia, `strings` (`Activitylog::strings()`, translated).

| Page | Props |
| --- | --- |
| `logs.index` / `Logs/Index` | `logs` (a paginator of `Activity`; for Inertia, `ActivityResource` JSON), `filters` (the `ActivityFilters`), `subject` (`{type, id, name, type_label}` on a record's history, otherwise `null`), `can_export`, `export_formats` (`{xlsx: "Excel (XLSX)", ...}`) |
| `logs.show` / `Logs/Show` | `log` (the `Activity`; for Inertia, its detailed JSON), `changes` (diff rows), `properties`, `can_rollback` |
| `logs.rollback` / `Logs/Rollback` | `log`, `changes` (current → restored diff rows) |

The TypeScript types are `LogsIndexProps`, `LogsShowProps` and `LogsRollbackProps`, exported from `@javaabu/activitylog`.

# Overriding the controller

Extend `Javaabu\Activitylog\Http\Controllers\ActivityLogsController`, then use it everywhere:

```php
// config/activitylog.php
'controllers' => [
    'logs' => \App\Http\Controllers\Admin\ActivityLogsController::class,
],
```

or for one registration:

```php
Route::activitylog(['controller' => \App\Http\Controllers\Admin\ActivityLogsController::class]);
```

or by binding a subclass in the container. Useful methods to override:

| Method | What |
| --- | --- |
| `query(Request $request)` | The base query (default: `visibleTo($user)`) the filters are applied to |
| `filters(Request $request)` | The filters (default: `activitylog.filters`) |
| `findLog(Request $request, string $id)` | Finds a log |
| `historySubject(ActivityFilters $filters)` | The record whose history the index shows |
| `canExport(Request $request)` / `export(...)` | Exporting |
| `strings()` | The Inertia strings |

```php
namespace App\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Javaabu\Activitylog\Http\Controllers\ActivityLogsController as BaseController;

class ActivityLogsController extends BaseController
{
    protected function query(Request $request): Builder
    {
        return parent::query($request)->where('log_name', '!=', 'system');
    }
}
```

# The view stack

`Javaabu\Activitylog\Views\ViewRenderer` renders `logs.index` as the Blade view `activitylog::logs.index`, or the Inertia page `activitylog::Logs/Index`, by `activitylog.views`. Bind your own subclass to change how pages are rendered.
