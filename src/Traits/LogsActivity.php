<?php

namespace Javaabu\Activitylog\Traits;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity as SpatieLogsActivity;

trait LogsActivity
{
    use SpatieLogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        /** @var Model $this */
        return LogOptions::defaults()
            ->logOnly(static::$logAttributes ?? ['*'])
            ->dontLogIfAttributesChangedOnly(static::$ignoreChangedAttributes ?? [])
            ->logExcept(static::$logExceptAttributes ?? [])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function canViewActivityLogs(Authorizable $user): bool
    {
        return $user->can('create', static::class);
    }
}
