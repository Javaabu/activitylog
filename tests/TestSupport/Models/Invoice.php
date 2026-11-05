<?php

namespace Javaabu\Activitylog\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Tests\TestSupport\Enums\PostStatuses;
use Javaabu\Activitylog\Traits\LogsActivity;

/**
 * A model with its own labels and descriptions
 */
class Invoice extends Model
{
    use LogsActivity;

    protected $table = 'posts';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => PostStatuses::class,
        ];
    }

    public static function activitylogLabel(): string
    {
        return __('Invoice');
    }

    public static function activitylogAttributeLabels(): array
    {
        return [
            'title' => __('Number'),
        ];
    }

    public function activitylogDisplayName(): string
    {
        return "#{$this->id}";
    }

    public function describeActivity(Activity $activity): ?string
    {
        if ($activity->event === 'voided') {
            return __('Invoice :number was voided', ['number' => $this->id]);
        }

        return null;
    }
}
