<?php

namespace Javaabu\Activitylog\Rollback;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Javaabu\Activitylog\Activitylog;
use Javaabu\Activitylog\Events\ModelRolledBack;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Support\Diff;

/**
 * Restores a model's logged attributes to the version right after a log
 *
 * For every attribute changed by a later log, the value it had then is the `old` value of the
 * earliest later change. Only attributes the model logs today are restored, so hidden and
 * sensitive attributes are never touched.
 *
 * ```php
 * $rollback = Rollback::to($log);
 *
 * $rollback->isPossible();    // or ->reason() for why not
 * $rollback->changes();       // a Diff of current → restored values
 * $rollback->perform($user);  // saves the model, logs `rolled_back`, returns that log
 * ```
 */
class Rollback
{
    /** The events whose logs are versions of a model */
    public const array VERSION_EVENTS = ['created', 'updated', 'restored', 'rolled_back'];

    /** @var array<string, mixed>|null */
    protected ?array $target = null;

    protected ?Model $subject = null;

    protected bool $subject_loaded = false;

    public function __construct(
        public readonly Activity $activity,
    ) {}

    public static function to(Activity $activity): static
    {
        return new static($activity);
    }

    public function subject(): ?Model
    {
        if ($this->subject_loaded) {
            return $this->subject;
        }

        $this->subject_loaded = true;

        if (! $this->activity->subject_type || $this->activity->subject_id === null) {
            return null;
        }

        $model = Activitylog::morphedModel($this->activity->subject_type);

        if (! $model) {
            return null;
        }

        $query = $model::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            $query->withTrashed();
        }

        return $this->subject = $query->find($this->activity->subject_id);
    }

    /**
     * Why the model can't be rolled back to this log, or null when it can
     */
    public function reason(): ?string
    {
        if (! config('activitylog.rollback.enabled', true)) {
            return __('Rolling back is turned off.');
        }

        if (! in_array($this->activity->event, static::VERSION_EVENTS, true)) {
            return __('Only logs of created and updated records can be rolled back to.');
        }

        $subject = $this->subject();

        if (! $subject) {
            return __('The record no longer exists.');
        }

        if (method_exists($subject, 'trashed') && $subject->trashed()) {
            return __('The record is deleted. Restore it first.');
        }

        if (method_exists($subject, 'activitylogCanRollback') && ! $subject->activitylogCanRollback($this->activity)) {
            return __('This record can\'t be rolled back.');
        }

        if ($this->changes()->isEmpty()) {
            return __('The record already matches this version.');
        }

        return null;
    }

    public function isPossible(): bool
    {
        return $this->reason() === null;
    }

    /**
     * The logged values the restored attributes would get
     *
     * @return array<string, mixed>
     */
    public function targetValues(): array
    {
        if ($this->target !== null) {
            return $this->target;
        }

        $subject = $this->subject();

        if (! $subject) {
            return $this->target = [];
        }

        $restorable = $this->restorableAttributes($subject);
        $target = array_intersect_key($this->activity->newValues(), array_flip($restorable));

        $later = Activitylog::newActivityModel()
            ->newQuery()
            ->forSubject($subject)
            ->where($this->activity->getKeyName(), '>', $this->activity->getKey())
            ->whereIn('event', ['updated', 'rolled_back'])
            ->orderBy($this->activity->getKeyName())
            ->get();

        $seen = [];

        foreach ($later as $log) {
            foreach ($log->oldValues() as $attribute => $value) {
                if (isset($seen[$attribute]) || ! in_array($attribute, $restorable, true)) {
                    continue;
                }

                $seen[$attribute] = true;
                $target[$attribute] = $value;
            }
        }

        return $this->target = $target;
    }

    /**
     * The current values of the attributes that would change, in the form they're logged in
     *
     * @return array<string, mixed>
     */
    public function currentValues(): array
    {
        $subject = $this->subject();

        if (! $subject) {
            return [];
        }

        $current = method_exists($subject, 'attributesToBeLogged')
            ? $subject::extractChanges($subject)
            : $subject->attributesToArray();

        return array_intersect_key($current, $this->targetValues());
    }

    /**
     * Current → restored values, for the attributes that differ
     */
    public function changes(): Diff
    {
        $current = $this->currentValues();
        $from = [];
        $to = [];

        foreach ($this->targetValues() as $attribute => $value) {
            $now = $current[$attribute] ?? null;

            if ($this->same($now, $value)) {
                continue;
            }

            $from[$attribute] = $now;
            $to[$attribute] = $value;
        }

        return Diff::between($from, $to, $this->subject() ? $this->subject()::class : null);
    }

    /**
     * Save the restored values and log the rollback
     *
     * @throws RollbackNotPossible
     */
    public function perform(?Model $causer = null): ?Activity
    {
        if ($reason = $this->reason()) {
            throw new RollbackNotPossible($reason);
        }

        $subject = $this->subject();
        $diff = $this->changes();

        $log = DB::transaction(function () use ($subject, $diff, $causer) {
            $this->restore($subject, $diff->new);

            return $this->log($subject, $diff, $causer);
        });

        ModelRolledBack::dispatch($subject, $this->activity, $log, $causer);

        return $log;
    }

    /**
     * Set the values and save, without the model's own `updated` log
     *
     * @param  array<string, mixed>  $values
     */
    protected function restore(Model $subject, array $values): void
    {
        $subject->forceFill($values);

        if (method_exists($subject, 'disableLogging')) {
            $subject->disableLogging();
        }

        try {
            $subject->save();
        } finally {
            if (method_exists($subject, 'enableLogging')) {
                $subject->enableLogging();
            }
        }
    }

    protected function log(Model $subject, Diff $diff, ?Model $causer): ?Activity
    {
        $logger = activity()
            ->performedOn($subject)
            ->event('rolled_back')
            ->withChanges([
                'attributes' => $diff->new,
                'old' => $diff->old,
            ])
            ->withProperties([
                'rolled_back_to' => $this->activity->getKey(),
                'rolled_back_to_date' => $this->activity->created_at?->toIso8601String(),
            ]);

        if ($causer) {
            $logger->causedBy($causer);
        }

        if (method_exists($subject, 'getActivitylogOptions')) {
            $logger->useLog($subject->getActivitylogOptions()->logName ?? config('activitylog.default_log_name'));
        }

        $log = $logger->log('rolled_back');

        return $log instanceof Activity ? $log : null;
    }

    /**
     * The attributes the model logs today that are plain columns (not related or JSON paths)
     *
     * @return array<int, string>
     */
    protected function restorableAttributes(Model $subject): array
    {
        $attributes = method_exists($subject, 'attributesToBeLogged')
            ? $subject->attributesToBeLogged()
            : array_keys($subject->getAttributes());

        $columns = array_keys($subject->getAttributes());
        $excluded = array_merge([$subject->getKeyName()], config('activitylog.rollback.except', []));

        return array_values(array_filter($attributes, function (string $attribute) use ($columns, $excluded) {
            if (Str::contains($attribute, ['.', '->'])) {
                return false;
            }

            if (in_array($attribute, $excluded, true)) {
                return false;
            }

            return in_array($attribute, $columns, true);
        }));
    }

    protected function same(mixed $a, mixed $b): bool
    {
        if (is_array($a) || is_array($b)) {
            return json_encode($a) === json_encode($b);
        }

        if ($a === null || $b === null) {
            return $a === $b;
        }

        return (string) $a === (string) $b;
    }
}
