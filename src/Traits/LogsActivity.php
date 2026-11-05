<?php

namespace Javaabu\Activitylog\Traits;

use Illuminate\Support\Str;
use Javaabu\Activitylog\Contracts\Tag;
use Javaabu\Activitylog\Descriptions\Description;
use Javaabu\Activitylog\Exceptions\UnsupportedLogProperty;
use Javaabu\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity as SpatieLogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Logs a model's created, updated, deleted (and restored) events through spatie/laravel-activitylog,
 * with defaults that are safe for most models
 *
 * Customize what is logged by overriding getActivitylogOptions(), usually starting from the defaults:
 *
 * ```php
 * public function getActivitylogOptions(): LogOptions
 * {
 *     return $this->defaultActivitylogOptions()->logOnly(['title', 'status']);
 * }
 * ```
 */
trait LogsActivity
{
    use SpatieLogsActivity {
        buildChanges as spatieBuildChanges;
    }

    /** The static properties v1 read, which v2 replaces with LogOptions */
    protected static array $unsupported_log_properties = [
        'logAttributes',
        'ignoreChangedAttributes',
        'logExceptAttributes',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return $this->defaultActivitylogOptions();
    }

    /**
     * The fillable attributes (or every attribute, when the model has no $fillable) that changed,
     * except hidden and sensitive attributes and timestamps. No log is written when nothing
     * logged changed, or when only timestamps changed.
     */
    public function defaultActivitylogOptions(): LogOptions
    {
        static::ensureNoUnsupportedLogProperties();

        $options = LogOptions::defaults()
            ->logExcept($this->activitylogExceptAttributes())
            ->dontLogIfAttributesChangedOnly($this->activitylogTimestampAttributes())
            ->logOnlyDirty()
            ->dontLogEmptyChanges();

        return $this->getFillable()
            ? $options->logFillable()
            : $options->logAll();
    }

    /**
     * Hidden attributes, the primary key, timestamps and `activitylog.sensitive_attributes` (patterns allowed)
     *
     * @return array<int, string>
     */
    public function activitylogExceptAttributes(): array
    {
        $patterns = config('activitylog.sensitive_attributes', []);

        $sensitive = collect([...array_keys($this->getAttributes()), ...$this->getFillable()])
            ->filter(fn (string $attribute) => Str::is($patterns, $attribute));

        return collect($this->getHidden())
            ->push($this->getKeyName())
            ->merge($this->activitylogTimestampAttributes())
            ->merge($sensitive)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function activitylogTimestampAttributes(): array
    {
        $timestamps = [];

        if ($this->usesTimestamps()) {
            $timestamps[] = $this->getCreatedAtColumn();
            $timestamps[] = $this->getUpdatedAtColumn();
        }

        if (method_exists($this, 'getDeletedAtColumn')) {
            $timestamps[] = $this->getDeletedAtColumn();
        }

        return array_values(array_filter($timestamps));
    }

    /**
     * Tags for this model's logs, by event: a registered tag's key or a Tag
     *
     * ```php
     * public static function activitylogTags(): array
     * {
     *     return ['published' => new ActivityTag('published', 'Published', 'success', 'circle-check')];
     * }
     * ```
     *
     * @return array<string, string|Tag>
     */
    public static function activitylogTags(): array
    {
        return [];
    }

    /**
     * A readable description of a log of this model, or null for the registered and built-in ones
     */
    public function describeActivity(Activity $activity): Description|string|null
    {
        return null;
    }

    /**
     * Labels of attributes in descriptions and diffs, by attribute
     *
     * @return array<string, string>
     */
    public static function activitylogAttributeLabels(): array
    {
        return [];
    }

    /**
     * Spatie compares against the model's original attributes, which lack the database defaults
     * of a model created in the same request (`status` stays unset until it's refreshed). Those
     * would be logged as changing from null, so they're dropped when only dirty values are logged.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function buildChanges(string $processingEvent): array
    {
        $changes = $this->spatieBuildChanges($processingEvent);

        if ($processingEvent !== 'updated' || ! $this->activitylogOptions?->logOnlyDirty) {
            return $changes;
        }

        $original = $this->getRawOriginal();
        $updated = $this->getChanges();

        foreach (array_keys($changes['attributes'] ?? []) as $attribute) {
            if (array_key_exists($attribute, $original) || array_key_exists($attribute, $updated)) {
                continue;
            }

            if (($changes['old'][$attribute] ?? null) !== null) {
                continue;
            }

            unset($changes['attributes'][$attribute], $changes['old'][$attribute]);
        }

        return $changes;
    }

    protected static function ensureNoUnsupportedLogProperties(): void
    {
        foreach (static::$unsupported_log_properties as $property) {
            if (property_exists(static::class, $property)) {
                throw UnsupportedLogProperty::make(static::class, $property);
            }
        }
    }
}
