<?php

namespace Javaabu\Activitylog\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Activitylog\Traits\LogsActivity;
use Javaabu\Translatable\Contracts\Translatable;
use Javaabu\Translatable\DbTranslatable\IsDbTranslatable;

/**
 * One row per language (javaabu/translatable)
 */
class Page extends Model implements Translatable
{
    use IsDbTranslatable;
    use LogsActivity;

    protected $fillable = [
        'title',
        'body',
        'lang',
    ];

    public function getTranslatables(): array
    {
        return ['title', 'body'];
    }
}
