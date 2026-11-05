<?php

namespace Javaabu\Activitylog;

class Activitylog
{
    /** Whether the package loads its migrations. Turn off with ignoreMigrations(). */
    public static bool $runs_migrations = true;

    /**
     * Don't load the package's migrations; publish them with `--tag=activitylog-migrations` instead
     */
    public static function ignoreMigrations(): void
    {
        static::$runs_migrations = false;
    }
}
