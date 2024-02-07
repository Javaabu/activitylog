---
title: Installation & Setup
sidebar_position: 1.2
---

You can install the package via composer:

```bash
composer require javaabu/activitylog
```

# Publishing the config file

Publishing the config file is required to make full use of this package:

```bash
php artisan vendor:publish --provider="Javaabu\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"
```

This is the default content of the config file:

```php
<?php

return [

    /*
     * If set to false, no activities will be saved to the database.
     */
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    /*
     * When the clean-command is executed, all recording activities older than
     * the number of days specified here will be deleted.
     */
    'delete_records_older_than_days' => 365,

    /*
     * If no log name is passed to the activity() helper
     * we use this default log name.
     */
    'default_log_name' => 'default',

    /*
     * You can specify an auth driver here that gets user models.
     * If this is null we'll use the current Laravel auth driver.
     */
    'default_auth_driver' => null,

    /*
     * If set to true, the subject returns soft deleted models.
     */
    'subject_returns_soft_deleted_models' => false,

    /*
     * This model will be used to log activity.
     * It should implement the Spatie\Activitylog\Contracts\Activity interface
     * and extend Illuminate\Database\Eloquent\Model.
     */
    'activity_model' => \Javaabu\Activitylog\Models\Activity::class,

    /*
     * This is the name of the table that will be created by the migration and
     * used by the Activity model shipped with this package.
     */
    'table_name' => 'activity_log',

    /*
     * This is the database connection that will be used by the migration and
     * the Activity model shipped with this package. In case it's not set
     * Laravel's database.default will be used instead.
     */
    'database_connection' => env('ACTIVITY_LOGGER_DB_CONNECTION'),
];
```

# Running migrations

This package's service provider registers its own database migration directory, so you should migrate your database after installing the package. The migrations will create customized versions of the spatie activity log tables:

```bash
php artisan migrate
```

# Migration Customization

If you are not going to use this package's default migrations, you should call the `Activitylog::ignoreMigrations` method in the `register` method of your `App\Providers\AppServiceProvider` class.

```php
use Javaabu\Activitylog\Activitylog;

Activitylog::ignoreMigrations();
```

You may export the default migrations using the following Artisan command:

```php
php artisan vendor:publish --provider="Javaabu\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
```

# Registering Subject Types and Causer Types

The package needs to know which model are used as subjects and causers. To register subject types and causer types, you may call the `SubjectTypes::register` and `CauserTypes:register` methods in the `boot` method of your`App\Providers\AppServiceProvider` class.

```php
use Javaabu\Activitylog\CauserTypes;
use Javaabu\Activitylog\SubjectTypes;

CauserTypes::register([    
    \App\Models\User::class,
]);

SubjectTypes::register([
    \App\Models\Category::class,
    \App\Models\User::class,
]);
```
