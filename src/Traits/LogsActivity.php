<?php

namespace Javaabu\Activitylog\Traits;

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
            ->dontLogIfAttributesChangedOnly((static::$ignoreChangedAttributes ?? []))
            ->logExcept(array_merge($this->getHidden(), (static::$logExceptAttributes ?? [])))
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
