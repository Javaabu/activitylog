<?php

namespace Javaabu\Activitylog\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Javaabu\Activitylog\Tests\TestSupport\Factories\AdminFactory;
use Javaabu\Permissions\Traits\HasRoles;

class Admin extends Authenticatable
{
    use HasFactory;
    use HasRoles;

    protected $guarded = [];

    protected string $guard_name = 'web_admin';

    protected $hidden = [
        'password',
    ];

    protected static function newFactory(): AdminFactory
    {
        return new AdminFactory;
    }
}
