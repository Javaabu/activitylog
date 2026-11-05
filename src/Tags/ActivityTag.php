<?php

namespace Javaabu\Activitylog\Tags;

use Javaabu\Activitylog\Contracts\Tag;

/**
 * A tag defined in config or registered at runtime
 *
 * ```php
 * Activitylog::registerTag(new ActivityTag('published', 'Published', 'success', 'circle-check'));
 * ```
 */
readonly class ActivityTag implements Tag
{
    /**
     * @param  string  $label  translated when read, so it can be a translation key
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $color = 'secondary',
        public string $icon = 'activity',
    ) {}

    /**
     * @param  array{label?: string, color?: string, icon?: string}  $definition
     */
    public static function fromArray(string $key, array $definition, ?Tag $base = null): self
    {
        return new self(
            $key,
            $definition['label'] ?? $base?->label() ?? static::defaultLabel($key),
            $definition['color'] ?? $base?->color() ?? 'secondary',
            $definition['icon'] ?? $base?->icon() ?? 'activity',
        );
    }

    /**
     * A tag for an event nothing registered: `invoice_sent` is labelled "Invoice sent"
     */
    public static function fallback(string $key): self
    {
        return new self($key, static::defaultLabel($key));
    }

    public static function defaultLabel(string $key): string
    {
        return str($key)->replace(['_', '-', '.'], ' ')->lower()->ucfirst()->toString();
    }

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return __($this->label);
    }

    public function color(): string
    {
        return $this->color;
    }

    public function icon(): string
    {
        return $this->icon;
    }
}
