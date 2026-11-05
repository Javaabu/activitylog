<?php

namespace Javaabu\Activitylog\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A guarded model (no $fillable) that customizes its options from the defaults
 */
class Note extends Model
{
    use LogsActivity;

    protected $table = 'posts';

    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return $this->defaultActivitylogOptions()
            ->dontLogIfAttributesChangedOnly(['views', 'created_at', 'updated_at'])
            ->useLogName('notes');
    }
}
