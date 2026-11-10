<?php

namespace Javaabu\Activitylog\Tests\TestSupport\Enums;

enum PostStatuses: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('Draft'),
            self::PUBLISHED => __('Published'),
        };
    }
}
