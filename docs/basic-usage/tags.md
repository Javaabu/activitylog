---
title: Tags
sidebar_position: 2
---

Every log has a tag: a label, a colour and an icon that the log pages, the history component and exports show it with. Tags come from the log's `event`. Logs without an event, such as those from javaabu/auth and javaabu/permissions, are tagged by their description.

```php
$log->tag()->key();    // 'updated'
$log->tag()->label();  // 'Updated' (translated)
$log->tag()->color();  // 'info'
$log->tag()->icon();   // 'pencil'
```

```blade
<x-activitylog::tag :tag="$log->tag()" />
```

```tsx
import { ActivityTag } from "@javaabu/activitylog";

<ActivityTag tag={log.tag} />
```

# Built-in tags

They're the cases of `Javaabu\Activitylog\Enums\ActivityTags`:

| Key | Label | Colour | Icon |
| --- | --- | --- | --- |
| `created` | Created | success | `plus` |
| `updated` | Updated | info | `pencil` |
| `deleted` | Deleted | danger | `trash-2` |
| `restored` | Restored | warning | `rotate-ccw` |
| `rolled_back` | Rolled back | warning | `undo-2` |
| `login` | Logged in | secondary | `log-in` |
| `logout` | Logged out | secondary | `log-out` |
| `failed_login` | Failed login | danger | `shield-alert` |
| `lockout` | Locked out | danger | `lock` |
| `password_reset` | Password reset | warning | `key-round` |
| `password_reset_link_sent` | Password reset link sent | warning | `mail` |
| `password_changed` | Password changed | warning | `key-round` |
| `two_factor_enabled` | Two-factor enabled | success | `shield-check` |
| `two_factor_disabled` | Two-factor disabled | warning | `shield-off` |
| `passkey_added` | Passkey added | success | `key-round` |
| `passkey_removed` | Passkey removed | warning | `key-round` |
| `role_updated` | Role changed | info | `users` |
| `permissions_updated` | Permissions changed | info | `shield` |
| `exported` | Exported | primary | `download` |
| `default` | Activity | secondary | `activity` |

An event nothing registered gets a plain tag named after it: `invoice_sent` is shown as "Invoice sent", secondary, with the `activity` icon. A log with neither an event nor a known description gets `default`.

# Colours and icons

Colours are javaabu/forms status colours: `primary`, `secondary`, `success`, `info`, `warning` and `danger`.

An icon is one of:

- An icon from the package's set: `activity`, `key-round`, `lock`, `log-in`, `log-out`, `mail`, `rotate-ccw`, `shield`, `shield-alert`, `shield-check`, `shield-off`, `undo-2`, `users`.
- An icon bundled with javaabu/forms, such as `plus`, `pencil`, `trash-2`, `download`, `circle-check`, `eye`, `upload` or `file-text`.
- Icon-font CSS classes, such as `fa-solid fa-paper-plane`, rendered as `<i class="...">`.

The SVG icons are from [Lucide](https://lucide.dev).

# Adding and changing tags in config

```php
// config/activitylog.php
'tags' => [
    'published' => ['label' => 'Published', 'color' => 'success', 'icon' => 'circle-check'],
    'deleted' => ['color' => 'warning'], // keeps the built-in label and icon
],
```

Labels are passed through `__()`, so add them to `lang/dv.json` to translate them.

# Registering tags in code

Register a backed enum whose cases implement `Javaabu\Activitylog\Contracts\Tag`, usually in a service provider's `boot()`:

```php
namespace App\Enums;

use Javaabu\Activitylog\Contracts\Tag;

enum InvoiceTags: string implements Tag
{
    case PAID = 'paid';
    case SENT = 'sent';

    public function key(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::PAID => __('Paid'),
            self::SENT => __('Sent'),
        };
    }

    public function color(): string
    {
        return $this === self::PAID ? 'success' : 'info';
    }

    public function icon(): string
    {
        return $this === self::PAID ? 'circle-check' : 'mail';
    }
}
```

```php
use App\Enums\InvoiceTags;
use Javaabu\Activitylog\Activitylog;
use Javaabu\Activitylog\Tags\ActivityTag;

Activitylog::registerTags(InvoiceTags::class);

// or one at a time
Activitylog::registerTag('archived', label: 'Archived', color: 'warning', icon: 'trash-2');
Activitylog::registerTag(new ActivityTag('approved', 'Approved', 'success', 'circle-check'));

// change part of an existing tag
Activitylog::registerTag('updated', color: 'primary');
```

Tags registered in code win over config, and config wins over the built-in tags.

# Tags for one model

A model using `LogsActivity` can map its own events to tags with `activitylogTags()`. The values are a registered tag's key or a `Tag`:

```php
use Javaabu\Activitylog\Tags\ActivityTag;

class Invoice extends Model
{
    use LogsActivity;

    public static function activitylogTags(): array
    {
        return [
            'paid' => new ActivityTag('paid', 'Paid', 'success', 'circle-check'),
            'voided' => 'deleted',
        ];
    }
}
```

Then log the event:

```php
activity()->performedOn($invoice)->event('paid')->log('paid');
```

# Filtering by tag

The `tagged()` scope matches the event, or the description of logs without an event:

```php
Activity::tagged('login')->count();
Activity::tagged(['created', 'updated'])->get();
```

The log pages have a tag filter that uses it.

# API

| Method | Returns |
| --- | --- |
| `Activitylog::tags()` | Every known tag, keyed by key |
| `Activitylog::tag(?string $key)` | The tag for a key, or a fallback tag |
| `Activitylog::findTag(?string $key)` / `hasTag(?string $key)` | The registered tag or `null` / whether there is one |
| `Activitylog::registerTag(Tag\|string $tag, ?string $label, ?string $color, ?string $icon)` | Adds or replaces a tag |
| `Activitylog::registerTags(string\|array $tags)` | Adds an enum's cases or a list of tags |
| `Activitylog::tagData(Tag $tag)` | `{key, label, color, icon, svg}`, as the pages and API send it |
| `Activitylog::forgetTags()` | Forgets registered tags (for tests) |
| `$log->tag()` / `$log->tagKey()` | The log's tag / the event or known description it's tagged by |
