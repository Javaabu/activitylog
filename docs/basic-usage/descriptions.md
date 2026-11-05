---
title: Readable descriptions
sidebar_position: 3
---

Every log can be described as a sentence in the reader's language:

```php
(string) $log->describe();
// "Ahmed updated the status of Invoice #12 from Draft to Paid"
// "އަހުމަދު Invoice #12 ގެ ހާލަތު Draft އިން Paid އަށް ބަދަލުކުރެއްވި"
```

Descriptions are worked out when a log is shown, not when it's saved, so they use the reader's locale and the current names of the causer and subject. The stored `description` column stays machine-readable (`updated`, `login`, ...), as spatie writes it.

`describe()` returns a `Javaabu\Activitylog\Descriptions\Description`:

| Use | Gives |
| --- | --- |
| `(string) $log->describe()` or `->toString()` | Plain text |
| `{{ $log->describe() }}` in Blade | HTML: values escaped and wrapped in `<bdi>`, so names in another script read correctly in a right-to-left sentence |
| `->toHtml()` | The same HTML as a string |
| `json_encode($log->describe())` | The plain text |
| `->template` / `->replacements` | The translated template and the values of its placeholders |

# What the sentences say

| Log | Description |
| --- | --- |
| `created` | Ahmed created Post Report |
| `updated`, one attribute | Ahmed updated the status of Invoice #12 from Draft to Paid |
| `updated`, from empty | Ahmed set the body of Post Report to ... |
| `updated`, to empty | Ahmed cleared the body of Post Report |
| `updated`, two or three attributes | Ahmed updated the title, status and body of Post Report |
| `updated`, more | Ahmed updated 5 attributes of Post Report |
| `deleted` / `restored` | Ahmed deleted Post Report |
| `rolled_back` | Ahmed rolled back Post Report to the version of 5 November 2026, 14:30 |
| javaabu/auth's `login`, `logout`, `failed_login`, `lockout`, `password_reset`, `password_reset_link_sent`, `password_changed`, `two_factor_enabled`, `two_factor_disabled`, `passkey_added`, `passkey_removed` | Ahmed logged in, Ahmed changed their password, ... |
| javaabu/permissions' `role_updated` / `permissions_updated` | Ahmed changed the role of Aishath from viewer to editor |
| A log written as a sentence (`->log('Sent the invoice')`) | The sentence itself |
| Anything else | Invoice sent: Invoice #12, by Ahmed |

A log without a causer is by "System". If the causer was deleted, it's by "A deleted user".

# Names, labels and values

| Placeholder | Comes from |
| --- | --- |
| `:causer`, `:subject` | The model's `activitylogDisplayName()`, otherwise its `name`, `title`, `label`, `number` or `email`, otherwise `#` and its key |
| `:type` | The model's static `activitylogLabel()`, otherwise its class name ("Invoice item"), translated |
| `:attribute`, `:attributes` | The model's static `activitylogAttributeLabels()`, otherwise the app's `validation.attributes.*` translations, otherwise the attribute name without `_id` ("published at", "customer"), translated |
| `:old`, `:new` | The values: enum labels (from the model's casts and the enum's `label()` or `getLabel()`), Yes/No, lists, "empty", shortened to 60 characters |
| `:count` | How many attributes changed |
| `:tag` | The [tag](tags.md) label |
| `:date` | For rollbacks, the date of the version rolled back to |
| `:description` | The stored description |

```php
class Invoice extends Model
{
    use LogsActivity;

    public static function activitylogLabel(): string
    {
        return __('Invoice');
    }

    public static function activitylogAttributeLabels(): array
    {
        return [
            'customer_id' => __('customer'),
            'total' => __('total amount'),
        ];
    }

    public function activitylogDisplayName(): string
    {
        return "#{$this->number}";
    }
}
```

# Describing your own events

For every model, or for one, register a template or a closure, usually in a service provider's `boot()`:

```php
use App\Models\Invoice;
use Javaabu\Activitylog\Activitylog;
use Javaabu\Activitylog\Models\Activity;

Activitylog::describe('published', ':causer published :type :subject');

Activitylog::describe('paid', ':causer marked :type :subject as paid', Invoice::class);

Activitylog::describe('sent', function (Activity $log, array $values) {
    return __(':causer sent :subject to :email', [
        'causer' => $values['causer'],
        'subject' => $values['subject'],
        'email' => $log->getProperty('email'),
    ]);
}, Invoice::class);
```

Templates are passed through `__()`, so add them to `lang/dv.json` to translate them. A closure gets the log and the placeholder values, and returns a string or a `Description`.

Or in config:

```php
// config/activitylog.php
'descriptions' => [
    'published' => ':causer published :type :subject',
    App\Models\Invoice::class => [
        'paid' => ':causer marked :type :subject as paid',
    ],
],
```

Descriptions are matched by the log's event, or by its description when it has no event.

# Describing a model's logs yourself

A model using `LogsActivity` can describe any of its logs with `describeActivity()`. Return `null` to use the registered and built-in descriptions:

```php
use Javaabu\Activitylog\Models\Activity;

class Invoice extends Model
{
    use LogsActivity;

    public function describeActivity(Activity $activity): ?string
    {
        if ($activity->event === 'voided') {
            return __('Invoice :number was voided', ['number' => $this->number]);
        }

        return null;
    }
}
```

# The order descriptions are looked up in

1. The subject's `describeActivity()`
2. `Activitylog::describe()` for the subject's model, then `activitylog.descriptions.{Model}`
3. `Activitylog::describe()` for every model, then `activitylog.descriptions`
4. The built-in sentences above
5. The log's own text, if it's a sentence, otherwise ":tag: :type :subject, by :causer"

To change more than that, extend `Javaabu\Activitylog\Descriptions\Describer` and set it as `activitylog.describer`. Its public methods (`describe()`, `replacements()`, `causerName()`, `subjectName()`, `modelLabel()`, `attributeLabel()`, `attributeList()`, `formatValue()`) and the `$max_listed_attributes` and `$max_value_length` properties can all be overridden.
