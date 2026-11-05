---
title: Rolling back
sidebar_position: 5
---

A record can be restored to the version of one of its logs: the values its attributes had right after that log.

On a log's page, users who may roll back see a **Roll back** button. It leads to a confirmation that shows each attribute that would change, with its current value and the value after rolling back. Confirming saves the record and writes one `rolled_back` log with those changes, so the rollback is in the history (and can itself be rolled back).

# How the version is worked out

The logs only hold what changed, so the package works backwards from the record as it is now. For every attribute changed by a later log, the value it had then is the `old` value of the earliest later change. Attributes nobody changed since keep their current value.

| | title | status |
| --- | --- | --- |
| Log 1 `created` | Draft report | draft |
| Log 2 `updated` | Final report | |
| Log 3 `updated` | | published |
| Now | Final report | published |
| **Rolled back to log 1** | Draft report (Log 2's old value) | draft (Log 3's old value) |

Only attributes the model logs today are restored, and never its primary key, hidden attributes or `activitylog.rollback.except`. The record is saved with logging turned off, then the package logs the rollback itself, so there's one `rolled_back` log rather than an `updated` one as well.

# Who may roll back

The `rollback` ability of the [policy](../advanced-usage/permissions-and-policies.md) needs all of:

- the `rollback_logs` permission
- permission to see the log
- `update` on the record (its own policy)
- a log that can be rolled back to (below)

# Logs that can't be rolled back to

`$log->rollback()->reason()` says why, and the pages hide the button:

| Reason | When |
| --- | --- |
| Only logs of created and updated records can be rolled back to. | The log isn't `created`, `updated`, `restored` or `rolled_back` (a `deleted` or `login` log, say) |
| The record no longer exists. | It was force deleted |
| The record is deleted. Restore it first. | It's soft deleted |
| This record can't be rolled back. | The model's `activitylogCanRollback()` said no |
| The record already matches this version. | Nothing would change |
| Rolling back is turned off. | `activitylog.rollback.enabled` is `false` |

A model can refuse:

```php
use Javaabu\Activitylog\Models\Activity;

class Invoice extends Model
{
    use LogsActivity;

    public function activitylogCanRollback(Activity $activity): bool
    {
        return $this->status !== InvoiceStatuses::PAID;
    }
}
```

# In code

```php
$rollback = $log->rollback();          // or Javaabu\Activitylog\Rollback\Rollback::to($log)

$rollback->isPossible();               // $log->canBeRolledBack()
$rollback->reason();                   // null, or why not
$rollback->subject();                  // the record (soft deleted ones too)
$rollback->targetValues();             // ['title' => 'Draft report', 'status' => 'draft']
$rollback->currentValues();
$rollback->changes()->rows();          // [['attribute' => 'title', 'label' => 'Title', 'old' => 'Final report', 'new' => 'Draft report', ...]]

$rolled_back = $rollback->perform($request->user()); // the rolled_back log
```

`perform()` throws `Javaabu\Activitylog\Rollback\RollbackNotPossible` (with the reason as its message) when it isn't possible, and runs in a transaction. It dispatches `Javaabu\Activitylog\Events\ModelRolledBack` with the `subject`, the `version` log, the new `log` and the `causer`.

The `rolled_back` log keeps the version it went back to in its properties: `rolled_back_to` (the log id) and `rolled_back_to_date`.

# Turning it off

```php
// config/activitylog.php
'rollback' => [
    'enabled' => false,
    'except' => ['number'], // or keep it on, but never restore these attributes
],
```

With it off, `Route::activitylog()` leaves out the rollback routes. Pass `'rollback' => false` to leave them out for one registration only.
