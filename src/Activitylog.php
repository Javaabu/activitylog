<?php

namespace Javaabu\Activitylog;

class Activitylog
{
    /**
     * Indicates if migrations will be run.
     *
     * @var bool
     */
    public static $runsMigrations = true;

    /**
     * Configure to not register its migrations.
     *
     * @return static
     */
    public static function ignoreMigrations()
    {
        static::$runsMigrations = false;

        return new static;
    }
}
