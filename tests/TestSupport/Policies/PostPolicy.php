<?php

namespace Javaabu\Activitylog\Tests\TestSupport\Policies;

use Javaabu\Permissions\Policies\ResourcePolicy;

class PostPolicy extends ResourcePolicy
{
    protected string $resource = 'posts';
}
