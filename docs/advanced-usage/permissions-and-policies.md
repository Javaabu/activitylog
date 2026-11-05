---
title: Permissions and policies
sidebar_position: 1
---

The package registers four permissions with [javaabu/permissions](https://github.com/Javaabu/permissions), in an "Activity logs" group, and a policy for the activity model.

| Permission | Lets a user |
| --- | --- |
| `view_logs` | See the logs of records they may see, and the logs they caused |
| `view_all_logs` | See every log (v1's name, kept) |
| `rollback_logs` | Roll back records they may update |
| `export_logs` | Export the logs they may see |

Create them for every guard in `permissions.guards`, then give them to roles:

```bash
php artisan permissions:sync
```

```php
$role->givePermissionTo(['view_logs', 'export_logs']);
```

# The policy

`Javaabu\Activitylog\Policies\ActivityPolicy` extends javaabu/permissions' `ResourcePolicy`:

| Ability | Allowed when |
| --- | --- |
| `viewAny` | `view_logs` or `view_all_logs` |
| `viewAll` | `view_all_logs` |
| `view` | `view_all_logs`; or `view_logs`, and the log is about a record the user may see, or they caused it |
| `rollback` | `rollback_logs`, `view`, `update` on the record, and the log [can be rolled back to](../basic-usage/rolling-back.md#logs-that-cant-be-rolled-back-to) |
| `export` | `export_logs` and `viewAny` |
| `create`, `update`, `delete`, `restore`, `forceDelete`, `viewTrash` | Never: logs aren't edited from the app |

```php
$user->can('rollback', $log);
$user->can('export', Activity::class);
```

# Which records' logs a user sees

With `view_logs`, a user sees the logs of a model when they may `viewAny` it (its own policy). A model can decide for itself:

```php
class Invoice extends Model
{
    use LogsActivity;

    public static function activitylogViewableBy(Model $user): bool
    {
        return $user->can('view_invoice_history');
    }
}
```

The `visibleTo()` scope applies the same rules to any query:

```php
Activity::visibleTo($request->user())->latest()->paginate();
```

The list of subject types a user may see is worked out once per request. Call `Activitylog::forgetViewableSubjectTypes()` after changing a user's permissions in the same request (tests, mostly).

# Overriding the policy

Any of these, from most to least specific:

```php
// AppServiceProvider::boot()
Gate::policy(\Javaabu\Activitylog\Models\Activity::class, \App\Policies\ActivityPolicy::class);
```

```php
// config/permissions.php
'policies' => [
    \Javaabu\Activitylog\Models\Activity::class => \App\Policies\ActivityPolicy::class,
],
```

```php
// config/activitylog.php
'policy' => \App\Policies\ActivityPolicy::class,
```

Extend the package's policy to change one ability:

```php
namespace App\Policies;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Activitylog\Policies\ActivityPolicy as BaseActivityPolicy;

class ActivityPolicy extends BaseActivityPolicy
{
    public function export(Model $user): bool
    {
        return $this->viewAll($user) && parent::export($user);
    }
}
```

To register your own permissions instead, set `activitylog.register_permissions` to `false`; the package then registers neither the permissions nor the policy.

# Several guards

Nothing assumes the `web` guard or `App\Models\User`: causers and subjects are morphs, and abilities are checked on whichever user is logged in. Register the routes for each guard, and sync the permissions for each guard in `permissions.guards`:

```php
Route::activitylog(['guard' => 'web_admin', 'prefix' => 'admin/logs', 'name' => 'admin.logs']);
Route::activitylog(['guard' => 'web', 'prefix' => 'my/activity', 'name' => 'my.activity']);
```

```php
// config/permissions.php
'guards' => ['web_admin', 'web'],
```
