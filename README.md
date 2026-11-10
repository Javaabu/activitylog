# Activity Log

[![Tests](https://github.com/Javaabu/activitylog/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/Javaabu/activitylog/actions/workflows/tests.yml?query=branch%3Amain)
[![codecov](https://codecov.io/gh/Javaabu/activitylog/branch/main/graph/badge.svg)](https://codecov.io/gh/Javaabu/activitylog/tree/main)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/javaabu/activitylog.svg)](https://packagist.org/packages/javaabu/activitylog)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)

Activity logs for Laravel, built on [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog) v5.

- Sensible defaults: add `LogsActivity` and changed fillable attributes are logged, never hidden attributes, passwords or timestamps. Customize with spatie's `getActivitylogOptions()`.
- Tags with colours and icons, and readable descriptions in the reader's language: "Ahmed updated the status of Invoice #12 from Draft to Paid".
- An activity log, a record's history and each log's changes, in Blade or Inertia React, with filters.
- Roll a record back to the version of a log, with a confirmation that shows the diff.
- Export the filtered logs with javaabu/exports.
- A policy and permissions through javaabu/permissions, for any guard and user model.
- Dhivehi translations and right-to-left pages.

```php
use Javaabu\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use LogsActivity;
}

// routes/web.php
Route::activitylog(['guard' => 'web_admin', 'prefix' => 'admin/logs', 'name' => 'admin.logs']);
```

```blade
<x-activitylog::history :subject="$invoice" route="admin.logs" />
```

## Documentation

You'll find the documentation on [https://docs.javaabu.com/activitylog](https://docs.javaabu.com/activitylog), and in [`docs/`](docs/intro.md). AI assistants can start from [`llms.txt`](llms.txt). Upgrading from v1? Read [`docs/upgrading.md`](docs/upgrading.md).

Find yourself stuck using the package? Found a bug? Do you have general questions or suggestions for improving this package? Feel free to create an [issue](../../issues) on GitHub, we'll try to address it as soon as possible.

If you've found a bug regarding security please mail [info@javaabu.com](mailto:info@javaabu.com) instead of using the issue tracker.

## Testing

You can run the tests with

``` bash
composer test
pnpm install && pnpm test   # the React pages
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email [info@javaabu.com](mailto:info@javaabu.com) instead of using the issue tracker.

## Credits

- [Javaabu Pvt. Ltd.](https://github.com/javaabu)
- [Arushad Ahmed (@dash8x)](http://arushad.org)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
