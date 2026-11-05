<?php

namespace Javaabu\Activitylog\Models;

use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Activity as BaseActivity;

/**
 * A log entry
 *
 * @property string|null $ip
 */
class Activity extends BaseActivity
{
    protected static function booted(): void
    {
        static::creating(function (Activity $activity) {
            if (! config('activitylog.log_ip', true)) {
                return;
            }

            $activity->ip ??= request()->ip();
        });
    }

    public function getTable(): string
    {
        return config('activitylog.table_name') ?: parent::getTable();
    }

    public function getConnectionName(): ?string
    {
        return config('activitylog.database_connection') ?: parent::getConnectionName();
    }

    /**
     * The new and old values of the logged attributes
     *
     * Reads spatie v5's `attribute_changes`, falling back to `properties.attributes` and
     * `properties.old`, where v1 logs and packages that log with withProperties() keep them.
     *
     * @return array{
     *     attributes: array<string, mixed>,
     *     old: array<string, mixed>
     * }
     */
    public function changes(): array
    {
        $changes = $this->attribute_changes?->toArray() ?? [];

        if (! Arr::hasAny($changes, ['attributes', 'old'])) {
            $properties = $this->properties?->toArray() ?? [];

            $changes = [
                'attributes' => $properties['attributes'] ?? [],
                'old' => $properties['old'] ?? [],
            ];
        }

        return [
            'attributes' => (array) ($changes['attributes'] ?? []),
            'old' => (array) ($changes['old'] ?? []),
        ];
    }

    /**
     * The new values of the logged attributes
     *
     * @return array<string, mixed>
     */
    public function newValues(): array
    {
        return $this->changes()['attributes'];
    }

    /**
     * The old values of the logged attributes
     *
     * @return array<string, mixed>
     */
    public function oldValues(): array
    {
        return $this->changes()['old'];
    }

    /**
     * The names of the attributes the log has values for
     *
     * @return array<int, string>
     */
    public function changedAttributes(): array
    {
        $changes = $this->changes();

        return array_values(array_unique([
            ...array_keys($changes['attributes']),
            ...array_keys($changes['old']),
        ]));
    }

    public function hasLoggedChanges(): bool
    {
        return $this->changedAttributes() !== [];
    }
}
