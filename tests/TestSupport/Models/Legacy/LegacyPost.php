<?php

namespace Javaabu\Activitylog\Tests\TestSupport\Models\Legacy;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Activitylog\Traits\LogsActivity;

/**
 * Still configured the v1 way
 */
class LegacyPost extends Model
{
    use LogsActivity;

    protected static array $logExceptAttributes = ['secret'];

    protected $table = 'posts';

    protected $guarded = [];
}
