<?php

namespace Javaabu\Activitylog\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Activitylog\Traits\LogsActivity;
use Javaabu\Translatable\Contracts\JsonTranslatable;
use Javaabu\Translatable\JsonTranslatable\IsJsonTranslatable;

/**
 * Translated in a JSON column (javaabu/translatable)
 */
class Article extends Model implements JsonTranslatable
{
    use IsJsonTranslatable;
    use LogsActivity;

    protected $fillable = [
        'title',
        'body',
        'translations',
        'lang',
    ];

    protected $casts = [
        'translations' => 'array',
    ];

    public function getTranslatables(): array
    {
        return ['title', 'body'];
    }
}
