<?php

namespace Javaabu\Activitylog\Support;

use Illuminate\Contracts\Support\Arrayable;
use Javaabu\Activitylog\Activitylog;
use Javaabu\Activitylog\Descriptions\Describer;
use Javaabu\Activitylog\Models\Activity;

/**
 * The attribute-by-attribute changes of a log, or between two sets of values, for display
 *
 * @implements Arrayable<int, array<string, mixed>>
 */
class Diff implements Arrayable
{
    /**
     * @param  array<int, string>  $attributes
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @param  class-string|null  $model
     */
    public function __construct(
        public readonly array $attributes,
        public readonly array $old,
        public readonly array $new,
        public readonly ?string $model = null,
    ) {}

    public static function forActivity(Activity $activity): self
    {
        $model = Activitylog::morphedModel($activity->subject_type);

        [$attributes, $old, $new] = Translations::expand(
            $model,
            $activity->changedAttributes(),
            $activity->oldValues(),
            $activity->newValues(),
        );

        return new self($attributes, $old, $new, $model);
    }

    /**
     * @param  array<string, mixed>  $from
     * @param  array<string, mixed>  $to
     * @param  class-string|null  $model
     */
    public static function between(array $from, array $to, ?string $model = null): self
    {
        return new self(array_keys($to), $from, $to, $model);
    }

    /**
     * The diff with a JSON-translatable model's `translations` shown per language and field
     */
    public function expanded(): self
    {
        [$attributes, $old, $new] = Translations::expand($this->model, $this->attributes, $this->old, $this->new);

        return new self($attributes, $old, $new, $this->model);
    }

    public function isEmpty(): bool
    {
        return $this->attributes === [];
    }

    /**
     * One row per attribute: its label, and the old and new values as text (null when the log
     * has no value on that side, e.g. the old values of a created model)
     *
     * @return array<int, array{
     *     attribute: string,
     *     label: string,
     *     old: string|null,
     *     new: string|null,
     *     changed: bool
     * }>
     */
    public function rows(): array
    {
        $describer = app(Describer::class);
        $rows = [];

        foreach ($this->attributes as $attribute) {
            $has_old = array_key_exists($attribute, $this->old);
            $has_new = array_key_exists($attribute, $this->new);

            $rows[] = [
                'attribute' => $attribute,
                'label' => str($describer->attributeLabel($this->model, $attribute))->ucfirst()->toString(),
                'old' => $has_old ? $describer->formatValue($this->model, $attribute, $this->old[$attribute], false) : null,
                'new' => $has_new ? $describer->formatValue($this->model, $attribute, $this->new[$attribute], false) : null,
                'changed' => ! $has_old || ! $has_new || $this->old[$attribute] != $this->new[$attribute],
            ];
        }

        return $rows;
    }

    public function toArray(): array
    {
        return $this->rows();
    }
}
