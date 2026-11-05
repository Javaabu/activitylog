<?php

namespace Javaabu\Activitylog\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Javaabu\Activitylog\Tests\TestSupport\Factories\UserFactory;

class User extends Authenticatable
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function newFactory(): UserFactory
    {
        return new UserFactory;
    }
}
