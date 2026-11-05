<?php

namespace Javaabu\Activitylog\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Javaabu\Activitylog\Models\Activity;

/**
 * A model was rolled back to the version of a log
 */
class ModelRolledBack
{
    use Dispatchable;

    /**
     * @param  Activity  $version  the log the model was rolled back to
     * @param  Activity|null  $log  the `rolled_back` log written for it (null when logging is off)
     */
    public function __construct(
        public Model $subject,
        public Activity $version,
        public ?Activity $log,
        public ?Model $causer,
    ) {}
}
