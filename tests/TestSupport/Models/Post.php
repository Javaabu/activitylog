<?php

namespace Javaabu\Activitylog\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Javaabu\Activitylog\Tests\TestSupport\Enums\PostStatuses;
use Javaabu\Activitylog\Tests\TestSupport\Factories\PostFactory;
use Javaabu\Activitylog\Traits\LogsActivity;

class Post extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'body',
        'status',
        'secret',
        'views',
        'user_id',
        'published_at',
    ];

    protected $hidden = [
        'secret',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatuses::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function newFactory(): PostFactory
    {
        return new PostFactory;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
