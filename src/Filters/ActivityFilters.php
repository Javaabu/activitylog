<?php

namespace Javaabu\Activitylog\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Javaabu\Activitylog\Activitylog;
use Javaabu\Activitylog\Descriptions\Describer;
use Javaabu\Activitylog\Support\Translations;
use Javaabu\Filters\DateRangeFilter;
use Javaabu\Filters\Filters;
use Javaabu\Filters\MultiSelectFilter;
use Javaabu\Filters\SelectFilter;
use Javaabu\Filters\Sort;
use Javaabu\Filters\TextFilter;

/**
 * The filters of the log pages and exports: search, tag, type, log name and date, plus hidden
 * subject and causer filters for a model's history and a user's activity
 *
 * Replace it with a subclass in `config('activitylog.filters')`.
 */
class ActivityFilters extends Filters
{
    protected ?string $default_sort = 'created_at';

    protected string $default_direction = 'desc';

    /** Whose logs the options are for; with it, the type options are limited to what they may see */
    protected ?Model $user = null;

    /** @var array<string, string>|null */
    protected ?array $log_names = null;

    public function forUser(?Model $user): static
    {
        $this->user = $user;

        return $this;
    }

    protected function setUp(): void
    {
        $this->model(Activitylog::activityModel());
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('search')
                ->label(__('Search'))
                ->placeholder(__('Search logs...'))
                ->thaana()
                ->query(fn (Builder $query, string $value) => $this->search($query, $value)),

            MultiSelectFilter::make('tag')
                ->label(__('Tag'))
                ->options(fn () => $this->tagOptions())
                ->query(fn (Builder $query, array $value) => $query->tagged($value)),

            SelectFilter::make('subject_type')
                ->label(__('Type'))
                ->options(fn () => $this->subjectTypeOptions()),

            SelectFilter::make('log_name')
                ->label(__('Log'))
                ->options(fn () => $this->logNameOptions())
                ->hidden(fn () => count($this->logNameOptions()) < 2),

            DateRangeFilter::make('created_at')
                ->label(__('Date')),

            TextFilter::make('subject_id')
                ->label(__('Record'))
                ->hidden()
                ->query(fn (Builder $query, string $value) => $query->whereIn('subject_id', $this->subjectKeys($value))),

            TextFilter::make('causer_type')
                ->label(__('User type'))
                ->hidden()
                ->query(fn (Builder $query, string $value) => $query->where('causer_type', $value)),

            TextFilter::make('causer_id')
                ->label(__('User'))
                ->hidden()
                ->query(fn (Builder $query, string $value) => $query->where('causer_id', $value)),
        ];
    }

    protected function sorts(): array
    {
        return [
            Sort::make('created_at')->label(__('Date'))->defaultDirection('desc'),
        ];
    }

    /**
     * The record and, for a DB-translatable model, its other language rows
     *
     * @return array<int, mixed>
     */
    protected function subjectKeys(string $id): array
    {
        $model = Activitylog::morphedModel($this->getValue('subject_type'));

        if (! $model || ! Translations::isDbTranslatable($model)) {
            return [$id];
        }

        $subject = $model::query()->find($id);

        return $subject ? Translations::relatedKeys($subject) : [$id];
    }

    /**
     * Matches the stored description, event and log name, and values in the changes and properties
     */
    protected function search(Builder $query, string $value): Builder
    {
        $like = '%' . addcslashes($value, '!%_') . '%';
        $escaped = '%' . addcslashes(substr((string) json_encode($value), 1, -1), '!%_') . '%';

        return $query->where(function (Builder $query) use ($like, $escaped) {
            foreach (['description', 'event', 'log_name'] as $column) {
                $query->orWhereRaw("{$query->getGrammar()->wrap($column)} like ? escape '!'", [$like]);
            }

            foreach (['attribute_changes', 'properties'] as $column) {
                $wrapped = $query->getGrammar()->wrap($column);

                $query->orWhereRaw("{$wrapped} like ? escape '!'", [$like])
                    ->orWhereRaw("{$wrapped} like ? escape '!'", [$escaped]);
            }
        });
    }

    /**
     * The tags of the logs there are, by label
     *
     * @return array<string, string>
     */
    protected function tagOptions(): array
    {
        $query = Activitylog::newActivityModel()->newQuery();

        $events = (clone $query)->whereNotNull('event')->distinct()->pluck('event');

        $descriptions = (clone $query)
            ->whereNull('event')
            ->distinct()
            ->pluck('description')
            ->filter(fn (?string $description) => Activitylog::hasTag($description));

        return $events->merge($descriptions)
            ->unique()
            ->mapWithKeys(fn (string $key) => [$key => Activitylog::tag($key)->label()])
            ->sort()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function subjectTypeOptions(): array
    {
        $describer = app(Describer::class);

        $types = Activitylog::newActivityModel()
            ->newQuery()
            ->whereNotNull('subject_type')
            ->distinct()
            ->pluck('subject_type');

        if ($this->user && ! $this->user->can('viewAll', Activitylog::activityModel())) {
            $types = $types->intersect(Activitylog::viewableSubjectTypes($this->user));
        }

        return $types
            ->mapWithKeys(fn (string $type) => [$type => $describer->modelLabel(Activitylog::morphedModel($type) ?? $type)])
            ->sort()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function logNameOptions(): array
    {
        return $this->log_names ??= Activitylog::newActivityModel()
            ->newQuery()
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name', 'log_name')
            ->all();
    }
}
