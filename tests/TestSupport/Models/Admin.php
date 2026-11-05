<?php

namespace Javaabu\Activitylog\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Javaabu\Activitylog\Tests\TestSupport\Factories\AdminFactory;

class Admin extends Authenticatable
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    protected static function newFactory(): AdminFactory
    {
        return new AdminFactory;
    }
}
