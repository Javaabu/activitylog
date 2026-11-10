<?php

namespace Javaabu\Activitylog\Tests\TestSupport\Policies;

class DenyAllPolicy
{
    public function viewAny(): bool
    {
        return false;
    }
}
