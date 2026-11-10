<?php

namespace Javaabu\Activitylog\Exceptions;

use LogicException;

/**
 * A model still uses one of the static properties javaabu/activitylog v1 read
 */
class UnsupportedLogProperty extends LogicException
{
    public static function make(string $model, string $property): self
    {
        $replacement = match ($property) {
            'logAttributes' => 'logOnly([...])',
            'ignoreChangedAttributes' => 'dontLogIfAttributesChangedOnly([...])',
            'logExceptAttributes' => 'logExcept([...])',
            default => '...',
        };

        return new self(
            "{$model}::\${$property} is no longer read by javaabu/activitylog v2. Remove it and override getActivitylogOptions(): "
            . "return \$this->defaultActivitylogOptions()->{$replacement}; (see docs/upgrading.md)"
        );
    }
}
