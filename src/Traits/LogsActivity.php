<?php

namespace Javaabu\Activitylog\Traits;

use Spatie\Activitylog\Models\Concerns\LogsActivity as SpatieLogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait LogsActivity
{
    use SpatieLogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
