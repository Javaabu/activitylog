<?php

namespace Javaabu\Activitylog\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use Javaabu\Activitylog\Activitylog;
use Javaabu\Activitylog\Descriptions\Describer;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Routing\ActivitylogRoutes;
use Javaabu\Activitylog\Support\Diff;

/**
 * A log as the pages and the JSON API send it
 *
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    /** Include the changes, properties and rollback state (the show page) */
    public bool $detailed = false;

    public function detailed(bool $detailed = true): static
    {
        $this->detailed = $detailed;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $description = $this->describe();
        $prefix = ActivitylogRoutes::namePrefix($request);
        $user = $request->user();

        return [
            'id' => $this->getKey(),
            'event' => $this->event,
            'log_name' => $this->log_name,
            'tag' => Activitylog::tagData($this->tag()),
            'description' => $description->toString(),
            'description_html' => $description->toHtml(),
            'causer' => $this->party($this->causer_type, $this->causer_id, $this->causer),
            'subject' => $this->party($this->subject_type, $this->subject_id, $this->subject),
            'changed_attributes' => $this->changedAttributes(),
            'changes' => $this->when($this->detailed, fn () => Diff::forActivity($this->resource)->rows()),
            'properties' => $this->when($this->detailed, fn () => $this->resource->customProperties()),
            'ip' => $this->ip,
            'can' => [
                'view' => (bool) $user?->can('view', $this->resource),
                'rollback' => $this->when($this->detailed, fn () => (bool) $user?->can('rollback', $this->resource)),
            ],
            'rollback' => $this->when($this->detailed, fn () => [
                'possible' => $this->canBeRolledBack(),
                'reason' => $this->rollback()->reason(),
            ]),
            'links' => [
                'show' => $this->route("{$prefix}.show"),
                'rollback' => $this->route("{$prefix}.rollback.confirm"),
                'history' => $this->subject_type ? $this->historyUrl($prefix) : null,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{
     *     type: string,
     *     type_label: string,
     *     id: int|string|null,
     *     name: string,
     *     exists: bool,
     *     url: string|null
     * }|null
     */
    protected function party(?string $type, mixed $id, ?Model $model): ?array
    {
        if (! $type) {
            return null;
        }

        $class = Activitylog::morphedModel($type);

        return [
            'type' => $type,
            'type_label' => app(Describer::class)->modelLabel($class ?? $type),
            'id' => $id,
            'name' => $model ? Activitylog::displayName($model) : "#{$id}",
            'exists' => $model !== null,
            'url' => $model ? Activitylog::url($model) : null,
        ];
    }

    protected function route(string $name): ?string
    {
        return Route::has($name) ? route($name, $this->getKey()) : null;
    }

    protected function historyUrl(string $prefix): ?string
    {
        if (! Route::has("{$prefix}.index")) {
            return null;
        }

        return route("{$prefix}.index", [
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
        ]);
    }
}
