<?php

namespace Javaabu\Activitylog\Exports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Javaabu\Activitylog\Activitylog;
use Javaabu\Activitylog\Descriptions\Describer;
use Javaabu\Activitylog\Filters\ActivityFilters;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Support\Diff;
use Javaabu\Exports\ModelExport;

/**
 * The logs a user may see, filtered like the log pages, as a spreadsheet: date, tag,
 * description, user, record, changes, IP address and log name
 *
 * Registered with javaabu/exports as `activity_logs`. Replace it with a subclass in
 * `config('activitylog.export.class')`.
 */
class ActivityLogExport extends ModelExport
{
    /**
     * The logs the user may see, with the filters of the log pages applied
     *
     * @param  array<string, mixed>  $parameters  the filters' query parameters (`$filters->getQueryParameters()`)
     */
    public static function for(?Model $user, array $parameters = []): static
    {
        $class = config('activitylog.filters') ?: ActivityFilters::class;

        $query = $class::make()
            ->fromQuery($parameters)
            ->forUser($user)
            ->apply(Activitylog::activityModel()::query()->visibleTo($user));

        return new static($query);
    }

    /**
     * For javaabu/exports' API: the filters come in `filters`
     */
    public static function fromRequest(Request $request): static
    {
        return static::for($request->user(), $request->array('filters'));
    }

    public function modelClass(): string
    {
        return Activitylog::activityModel();
    }

    public function authorize(?Model $user): bool
    {
        return $user !== null && $user->can('export', $this->modelClass());
    }

    public function label(): string
    {
        return __('Activity log');
    }

    public function filename(): string
    {
        return 'activity-log';
    }

    public function eagerLoads(): array
    {
        return ['causer', 'subject'];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('Date'),
            __('Tag'),
            __('Description'),
            __('User'),
            __('Type'),
            __('Record'),
            __('Changes'),
            __('IP address'),
            __('Log'),
        ];
    }

    /**
     * @param  Activity  $model
     * @return array<int, mixed>
     */
    public function map(Model $model): array
    {
        $subject = $model->subject_type
            ? ($model->subject ? Activitylog::displayName($model->subject) : "#{$model->subject_id}")
            : null;

        return [
            $model->created_at ? $this->formatDate($model, 'created_at', $model->created_at) : null,
            $model->tag()->label(),
            $model->describe()->toString(),
            $model->causer ? Activitylog::displayName($model->causer) : null,
            $model->subject_type ? app(Describer::class)->modelLabel(Activitylog::morphedModel($model->subject_type) ?? $model->subject_type) : null,
            $subject,
            $this->changesText($model),
            $model->ip,
            $model->log_name,
        ];
    }

    /**
     * "Status: Draft → Paid", one attribute per line
     */
    protected function changesText(Activity $log): string
    {
        return collect(Diff::forActivity($log)->rows())
            ->map(function (array $row) {
                if ($row['old'] === null) {
                    return "{$row['label']}: {$row['new']}";
                }

                if ($row['new'] === null) {
                    return "{$row['label']}: {$row['old']}";
                }

                return "{$row['label']}: {$row['old']} → {$row['new']}";
            })
            ->implode("\n");
    }
}
