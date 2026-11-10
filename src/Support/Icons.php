<?php

namespace Javaabu\Activitylog\Support;

use Javaabu\Forms\Support\Icons\Svg;

/**
 * The SVG of a tag icon: the package's own set first, then javaabu/forms' bundled icons
 */
class Icons
{
    /** @var array<string, string>|null */
    protected static ?array $icons = null;

    /**
     * The inner SVG markup, or null when the name is an icon-font class instead
     */
    public static function svg(string $name): ?string
    {
        $icons = static::$icons ??= require __DIR__ . '/../../resources/icons/icons.php';

        if (isset($icons[$name])) {
            return $icons[$name];
        }

        return Svg::get($name);
    }

    public static function has(string $name): bool
    {
        return static::svg($name) !== null;
    }
}
