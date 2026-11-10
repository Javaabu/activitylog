<?php

namespace Javaabu\Activitylog\Tests\TestSupport\Enums;

use Javaabu\Activitylog\Contracts\Tag;

enum InvoiceTags: string implements Tag
{
    case PAID = 'paid';
    case SENT = 'sent';

    public function key(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::PAID => __('Paid'),
            self::SENT => __('Sent'),
        };
    }

    public function color(): string
    {
        return $this === self::PAID ? 'success' : 'info';
    }

    public function icon(): string
    {
        return $this === self::PAID ? 'circle-check' : 'fa-solid fa-paper-plane';
    }
}
