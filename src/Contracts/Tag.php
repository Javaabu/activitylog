<?php

namespace Javaabu\Activitylog\Contracts;

/**
 * How a kind of log is shown: a label, a colour and an icon
 *
 * Colours are javaabu/forms status colours: primary, secondary, success, info, warning, danger.
 * Icons are a name from the package's icon set (see Javaabu\Activitylog\Support\Icons), a bundled
 * javaabu/forms icon, or icon-font CSS classes.
 */
interface Tag
{
    /** The event or description the tag is for, e.g. `created` */
    public function key(): string;

    /** The translated label */
    public function label(): string;

    public function color(): string;

    public function icon(): string;
}
