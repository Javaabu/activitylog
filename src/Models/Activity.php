<?php

namespace Javaabu\Activitylog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Javaabu\Activitylog\Activitylog;
use Javaabu\Activitylog\Contracts\Tag;
use Javaabu\Activitylog\Descriptions\Describer;
use Javaabu\Activitylog\Descriptions\Description;
use Javaabu\Activitylog\Rollback\Rollback;
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

    /**
     * The properties without the logged changes and the rollback bookkeeping
     *
     * @return array<string, mixed>
     */
    public function customProperties(): array
    {
        return Arr::except($this->properties?->toArray() ?? [], ['attributes', 'old', 'rolled_back_to', 'rolled_back_to_date']);
    }

    public function hasLoggedChanges(): bool
    {
        return $this->changedAttributes() !== [];
    }

    /**
     * The tag the log is shown with
     *
     * The subject model's activitylogTags() map wins, then the tag registered for the event, then
     * (for logs without an event, such as javaabu/auth's) the tag registered for the description.
     */
    public function tag(): Tag
    {
        $key = $this->tagKey();

        $model = Activitylog::morphedModel($this->subject_type);

        if ($model && method_exists($model, 'activitylogTags')) {
            $tag = $model::activitylogTags()[$this->event ?: $this->description] ?? null;

            if ($tag instanceof Tag) {
                return $tag;
            }

            if (is_string($tag)) {
                return Activitylog::tag($tag);
            }
        }

        return Activitylog::tag($key);
    }

    /**
     * The event, or the description when the log has no event
     */
    public function tagKey(): ?string
    {
        if ($this->event) {
            return $this->event;
        }

        return Activitylog::hasTag($this->description) ? $this->description : null;
    }

    /**
     * Logs with a tag: the event, or the description when there's no event
     *
     * @param  string|array<int, string>  $tags
     */
    public function scopeTagged(Builder $query, string|array $tags): Builder
    {
        $tags = Arr::wrap($tags);

        return $query->where(function (Builder $query) use ($tags) {
            $query->whereIn('event', $tags)
                ->orWhere(function (Builder $query) use ($tags) {
                    $query->whereNull('event')->whereIn('description', $tags);
                });
        });
    }

    /**
     * A readable sentence for the log, in the current locale
     */
    public function describe(): Description
    {
        return app(Describer::class)->describe($this);
    }

    public function isCausedBy(Model $user): bool
    {
        return $this->causer_type === $user->getMorphClass()
            && (string) $this->causer_id === (string) $user->getKey();
    }

    /**
     * The logs a user may see: everything with view_all_logs; with view_logs, logs about
     * models they may see and logs they caused; otherwise none
     */
    public function scopeVisibleTo(Builder $query, ?Model $user): Builder
    {
        if (! $user || ! $user->can('viewAny', static::class)) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can('viewAll', static::class)) {
            return $query;
        }

        $types = Activitylog::viewableSubjectTypes($user);

        return $query->where(function (Builder $query) use ($types, $user) {
            $query->whereIn('subject_type', $types)
                ->orWhere(function (Builder $query) use ($user) {
                    $query->where('causer_type', $user->getMorphClass())
                        ->where('causer_id', $user->getKey());
                });
        });
    }

    /**
     * The rollback of the log's subject to the version right after this log
     */
    public function rollback(): Rollback
    {
        return Rollback::to($this);
    }

    public function canBeRolledBack(): bool
    {
        return $this->rollback()->isPossible();
    }
}
