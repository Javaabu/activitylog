---
title: Requirements
sidebar_position: 1.1
---

- PHP 8.4 or 8.5
- Laravel 12 or 13
- [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog) v5, [javaabu/permissions](https://github.com/Javaabu/permissions) v3, [javaabu/filters](https://github.com/Javaabu/filters) v1, [javaabu/exports](https://github.com/Javaabu/exports) v2, [javaabu/forms](https://github.com/Javaabu/forms) v2, [javaabu/stencil](https://github.com/Javaabu/stencil) v1 and [javaabu/manifest](https://github.com/Javaabu/manifest) v1, installed with this package
- For the log pages: Vite with manifest's plugin. The Blade pages need no npm packages from activitylog. The Inertia React pages (and the components you import from `@javaabu/activitylog`) need `@javaabu/forms`, `@javaabu/onu`, React 18.3 or 19 and `@inertiajs/react` 2 or 3 in the app. They're declared for manifest's pages scope, so `php artisan manifest:check` asks for them only when your `package.json` has an Inertia adapter.

Optional:

| Package | For |
| --- | --- |
| `inertiajs/inertia-laravel` ^2.0 | The Inertia React pages (`activitylog.views` = `inertia`). See [Log pages](basic-usage/log-pages.md). |
| `javaabu/translatable` ^3.0 | Changes to translated models shown per language. See [Translated models](advanced-usage/translatable.md). |

javaabu/helpers is no longer needed.
