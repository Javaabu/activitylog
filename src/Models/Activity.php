<?php

namespace Javaabu\Activitylog\Models;

use Spatie\Activitylog\Models\Activity as BaseActivity;

/**
 * @property string|null $ip
 */
class Activity extends BaseActivity
{
    protected static function booted(): void
    {
        static::creating(function (Activity $activity) {
            if (! config('activitylog.log_ip', true)) {
                return;
            }

            $activity->ip ??= request()->ip();
        });
    }

    public function getTable(): string
    {
        return config('activitylog.table_name') ?: parent::getTable();
    }

    public function getConnectionName(): ?string
    {
        return config('activitylog.database_connection') ?: parent::getConnectionName();
    }
}
