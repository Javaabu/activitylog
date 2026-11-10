# Tags and descriptions

## Tags

Each log is tagged by its `event` (or, without one, its description). Built-ins (`Javaabu\Activitylog\Enums\ActivityTags`): `created`, `updated`, `deleted`, `restored`, `rolled_back`, `login`, `logout`, `failed_login`, `lockout`, `password_reset`, `password_reset_link_sent`, `password_changed`, `two_factor_enabled`, `two_factor_disabled`, `passkey_added`, `passkey_removed`, `role_updated`, `permissions_updated`, `exported`, `default`.

Colours: `primary`, `secondary`, `success`, `info`, `warning`, `danger`. Icons: the package's (`activity`, `key-round`, `lock`, `log-in`, `log-out`, `mail`, `rotate-ccw`, `shield`, `shield-alert`, `shield-check`, `shield-off`, `undo-2`, `users`), javaabu/forms' bundled icons (`plus`, `pencil`, `trash-2`, `download`, `circle-check`, ...), or icon-font classes.

```php
namespace App\Enums;

use Javaabu\Activitylog\Contracts\Tag;

enum InvoiceTags: string implements Tag
{
    case PAID = 'paid';

    public function key(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return __('Paid');
    }

    public function color(): string
    {
        return 'success';
    }

    public function icon(): string
    {
        return 'circle-check';
    }
}

// AppServiceProvider::boot()
Activitylog::registerTags(InvoiceTags::class);
```

Or in config (`activitylog.tags`), or per model:

```php
public static function activitylogTags(): array
{
    return ['voided' => 'deleted'];
}
```

## Descriptions

Placeholders: `:causer`, `:subject`, `:type`, `:attribute`, `:attributes`, `:count`, `:old`, `:new`, `:tag`, `:date`, `:description`. Templates go through `__()`.

```php
Activitylog::describe('paid', ':causer marked :type :subject as paid', Invoice::class);

Activitylog::describe('sent', fn (Activity $log, array $values) => __(':causer sent :subject to :email', [
    'causer' => $values['causer'],
    'subject' => $values['subject'],
    'email' => $log->getProperty('email'),
]));
```

Per model, for anything:

```php
public function describeActivity(Activity $activity): ?string
{
    return $activity->event === 'voided'
        ? __('Invoice :number was voided', ['number' => $this->number])
        : null;
}
```

Attribute labels come from the model's `activitylogAttributeLabels()`, then `validation.attributes.*`, then the attribute name. Enum values use the enum's `label()`.

In Blade, `{{ $log->describe() }}` escapes values and wraps them in `<bdi>`; use `(string) $log->describe()` for plain text.
