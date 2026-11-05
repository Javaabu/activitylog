---
title: Installation & Setup
sidebar_position: 1.2
---

# Installing

```bash
composer require javaabu/activitylog
```

The service provider is discovered automatically, along with spatie's.

# Migrating

The package loads its migrations, which create the `activity_log` table with the columns spatie v5 uses and an `ip` column:

```bash
php artisan migrate
```

To publish them instead:

```php
// AppServiceProvider::register()
\Javaabu\Activitylog\Activitylog::ignoreMigrations();
```

```bash
php artisan vendor:publish --tag=activitylog-migrations
```

Don't also publish spatie's migration: the package's migrations replace it.

# Logging

Add the trait to the models you want to log. That's all for the common case; see [Logging model events](basic-usage/logging-model-events.md).

```php
use Javaabu\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use LogsActivity;
}
```

# Permissions

The package registers its permissions with javaabu/permissions. Create them, then give them to roles:

```bash
php artisan permissions:sync
```

| Permission | Lets a user |
| --- | --- |
| `view_logs` | See the logs of records they may see, and their own |
| `view_all_logs` | See every log |
| `rollback_logs` | Roll back records they may update |
| `export_logs` | Export the logs they may see |

See [Permissions and policies](advanced-usage/permissions-and-policies.md).

# Routes

The log pages aren't loaded automatically. Register them in your routes file, inside the group with your middleware:

```php
// routes/web.php
Route::middleware('web')->group(function () {
    Route::activitylog();
});

// or for an admin guard
Route::middleware('web')->group(function () {
    Route::activitylog(['guard' => 'web_admin', 'prefix' => 'admin/logs', 'name' => 'admin.logs']);
});
```

See [Log pages](basic-usage/log-pages.md).

# Front end

The pages use javaabu/forms and javaabu/filters components. With javaabu/manifest's Vite plugin set up, the Blade pages need nothing else.

For the Inertia React pages, set the stack and install the front-end dependencies manifest lists:

```dotenv
ACTIVITYLOG_VIEWS=inertia
```

```bash
php artisan manifest:check
```

Your Inertia page resolver needs manifest's `resolvePage()` so `activitylog::Logs/Index` finds the package's page. See [Setting up the pages](advanced-usage/overriding-controllers-and-views.md#setting-up-the-pages).

# Cleaning old logs

spatie's command deletes logs older than `activitylog.clean_after_days` (365):

```php
// routes/console.php
Schedule::command('activitylog:clean')->daily();
```

# Publishing

| Tag | Publishes |
| --- | --- |
| `activitylog-config` | `config/activitylog.php` |
| `activitylog-migrations` | The migrations |
| `activitylog-views` | The Blade views, to `resources/views/vendor/activitylog` |
| `activitylog-translations` | `lang/en.json` and `lang/dv.json`, to `lang/vendor/activitylog` |

```bash
php artisan vendor:publish --tag=activitylog-config
```

The config file is a superset of spatie's: publish this one, not spatie's. See [Configuration](advanced-usage/configuration.md).
