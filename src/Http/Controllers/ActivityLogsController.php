<?php

namespace Javaabu\Activitylog\Http\Controllers;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Javaabu\Activitylog\Activitylog;
use Javaabu\Activitylog\Descriptions\Describer;
use Javaabu\Activitylog\Exports\ActivityLogExport;
use Javaabu\Activitylog\Filters\ActivityFilters;
use Javaabu\Activitylog\Http\Resources\ActivityResource;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Rollback\RollbackNotPossible;
use Javaabu\Activitylog\Routing\ActivitylogRoutes;
use Javaabu\Activitylog\Support\Diff;
use Javaabu\Activitylog\Views\ViewRenderer;
use Javaabu\Exports\Enums\Formats;
use Symfony\Component\HttpFoundation\Response;

/**
 * The log pages: the index (and a model's history), a log with its changes, and rolling back
 *
 * Every action answers JSON when the request expects it, and a Blade or Inertia page
 * otherwise. Override it with `config('activitylog.controllers.logs')`, the `controller`
 * option of `Route::activitylog()`, or by binding a subclass in the container.
 */
class ActivityLogsController extends Controller
{
    public function __construct(
        protected ViewRenderer $views,
    ) {}

    public function index(Request $request): View|Responsable|JsonResponse|RedirectResponse|Response
    {
        $this->authorizeAbility($request, 'viewAny', Activitylog::activityModel());

        $filters = $this->filters($request);

        if ($format = $this->downloadFormat($request)) {
            return $this->export($request, $filters, $format);
        }

        $per_page = min(max($request->integer('per_page', config('activitylog.per_page', 20)), 1), 100);

        $logs = $filters->apply($this->query($request))
            ->with(['causer', 'subject'])
            ->paginate($per_page)
            ->withQueryString();

        if ($request->expectsJson()) {
            return ActivityResource::collection($logs)->response();
        }

        $subject = $this->historySubject($filters);

        return $this->views->render('logs.index', [
            'logs' => $this->views->isInertia() ? ActivityResource::collection($logs) : $logs,
            'filters' => $filters,
            'subject' => $subject,
            'can_export' => $this->canExport($request),
            'export_formats' => $this->exportFormats(),
            'routes' => $this->routeNames($request),
            'strings' => $this->views->isInertia() ? $this->strings() : null,
            'title' => $subject ? __('History of :subject', ['subject' => $subject['name']]) : __('Activity log'),
        ]);
    }

    public function show(Request $request, string $log): View|Responsable|JsonResponse
    {
        $log = $this->findLog($request, $log);

        $this->authorizeAbility($request, 'view', $log);

        $resource = (new ActivityResource($log))->detailed();

        if ($request->expectsJson()) {
            return $resource->response();
        }

        return $this->views->render('logs.show', [
            'log' => $this->views->isInertia() ? $resource->resolve($request) : $log,
            'changes' => Diff::forActivity($log)->rows(),
            'properties' => $log->customProperties(),
            'can_rollback' => (bool) $request->user()?->can('rollback', $log),
            'routes' => $this->routeNames($request),
            'strings' => $this->views->isInertia() ? $this->strings() : null,
            'title' => __('Log #:id', ['id' => $log->getKey()]),
        ]);
    }

    /**
     * What rolling back to the log would change, and a button to do it
     */
    public function confirmRollback(Request $request, string $log): View|Responsable|JsonResponse
    {
        $log = $this->findLog($request, $log);

        $this->authorizeAbility($request, 'rollback', $log);

        $changes = $log->rollback()->changes()->rows();

        if ($request->expectsJson()) {
            return response()->json(['data' => ['log' => (new ActivityResource($log))->resolve($request), 'changes' => $changes]]);
        }

        return $this->views->render('logs.rollback', [
            'log' => $this->views->isInertia() ? (new ActivityResource($log))->detailed()->resolve($request) : $log,
            'changes' => $changes,
            'routes' => $this->routeNames($request),
            'strings' => $this->views->isInertia() ? $this->strings() : null,
            'title' => __('Roll back to log #:id', ['id' => $log->getKey()]),
        ]);
    }

    public function rollback(Request $request, string $log): RedirectResponse|JsonResponse
    {
        $log = $this->findLog($request, $log);

        $this->authorizeAbility($request, 'rollback', $log);

        try {
            $rollback_log = $log->rollback()->perform($request->user());
        } catch (RollbackNotPossible $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 409);
            }

            return back()->with('error', $exception->getMessage());
        }

        if ($request->expectsJson()) {
            return (new ActivityResource($rollback_log ?? $log))->detailed()->response()->setStatusCode(201);
        }

        return redirect()
            ->route($this->routeName($request, 'show'), $rollback_log ?? $log)
            ->with('success', __('The record was rolled back.'));
    }

    /**
     * The logs the user may see; the filters are applied on top
     */
    protected function query(Request $request): Builder
    {
        return Activitylog::activityModel()::query()->visibleTo($request->user());
    }

    protected function filters(Request $request): ActivityFilters
    {
        $class = config('activitylog.filters') ?: ActivityFilters::class;

        return $class::make()
            ->fromRequest($request)
            ->forUser($request->user())
            ->action(route($this->routeName($request, 'index')));
    }

    protected function findLog(Request $request, string $id): Activity
    {
        return Activitylog::activityModel()::query()
            ->with(['causer', 'subject'])
            ->findOrFail($id);
    }

    /**
     * The model whose history the index shows, when it's filtered to one
     *
     * @return array{type: string, id: string, name: string, type_label: string}|null
     */
    protected function historySubject(ActivityFilters $filters): ?array
    {
        $type = $filters->getValue('subject_type');
        $id = $filters->getValue('subject_id');

        if (! $type || ! $id) {
            return null;
        }

        $model = Activitylog::morphedModel($type);
        $subject = $model ? $model::query()->find($id) : null;

        return [
            'type' => $type,
            'id' => (string) $id,
            'name' => $subject instanceof Model ? Activitylog::displayName($subject) : "#{$id}",
            'type_label' => app(Describer::class)->modelLabel($model ?? $type),
        ];
    }

    protected function downloadFormat(Request $request): ?Formats
    {
        if (! $request->filled('download')) {
            return null;
        }

        $format = Formats::tryFrom(strtolower((string) $request->query('download')));

        return $format && in_array($format, Formats::enabled(), true)
            ? $format
            : Formats::resolve();
    }

    protected function canExport(Request $request): bool
    {
        return config('activitylog.export.enabled', true)
            && (bool) $request->user()?->can('export', Activitylog::activityModel());
    }

    /**
     * Export the filtered logs: on the queue when javaabu/exports' routes are registered (and
     * `activitylog.export.queue` is on), otherwise as a download
     */
    protected function export(Request $request, ActivityFilters $filters, Formats $format): Response
    {
        abort_unless($this->canExport($request), 403);

        $class = config('activitylog.export.class') ?: ActivityLogExport::class;
        $export = $class::for($request->user(), $filters->getQueryParameters());

        $exports_route = config('activitylog.export.exports_route', 'exports.index');

        if (config('activitylog.export.queue', true) && Route::has($exports_route)) {
            $export->queue($request->user(), $format);

            return redirect()
                ->route($exports_route)
                ->with('success', __('Your export has started. We will let you know when it is ready.'));
        }

        return $export->download(null, $format);
    }

    /**
     * The formats the export button offers, value => label
     *
     * @return array<string, string>
     */
    protected function exportFormats(): array
    {
        return collect(Formats::enabled())
            ->mapWithKeys(fn (Formats $format) => [$format->value => $format->label()])
            ->all();
    }

    /**
     * The translated text of the pages, for the Inertia pages (Blade views use __() directly)
     *
     * @return array<string, string>
     */
    protected function strings(): array
    {
        return [
            'title' => __('Activity log'),
            'date' => __('Date'),
            'tag' => __('Tag'),
            'description' => __('Description'),
            'user' => __('User'),
            'record' => __('Record'),
            'ip' => __('IP address'),
            'log_name' => __('Log'),
            'event' => __('Event'),
            'actions' => __('Actions'),
            'view' => __('View'),
            'history' => __('History'),
            'changes' => __('Changes'),
            'attribute' => __('Attribute'),
            'old_value' => __('Old value'),
            'new_value' => __('New value'),
            'current_value' => __('Current value'),
            'restored_value' => __('Value after rolling back'),
            'properties' => __('Details'),
            'no_changes' => __('No changes were logged.'),
            'no_logs' => __('No activity yet'),
            'no_matching_logs' => __('No activity matches these filters.'),
            'rollback' => __('Roll back'),
            'rollback_title' => __('Roll back to this version'),
            'rollback_intro' => __('These attributes will be restored to the values they had after this log. The rollback is logged.'),
            'confirm_rollback' => __('Roll back the record?'),
            'cancel' => __('Cancel'),
            'back' => __('Back to the activity log'),
            'back_to_log' => __('Back to the log'),
            'system' => __('System'),
            'deleted_record' => __('Deleted'),
            'all_activity' => __('All activity'),
            'export' => __('Export'),
        ];
    }

    protected function authorizeAbility(Request $request, string $ability, mixed $arguments): void
    {
        $user = $request->user();

        abort_unless($user && $user->can($ability, $arguments), 403);
    }

    protected function routeName(Request $request, string $action): string
    {
        return ActivitylogRoutes::namePrefix($request) . ".{$action}";
    }

    /**
     * Names of the routes, honouring the prefix the app registered them under
     *
     * @return array<string, string>
     */
    protected function routeNames(Request $request): array
    {
        return collect(['index', 'show', 'rollback.confirm', 'rollback'])
            ->mapWithKeys(fn (string $action) => [$action => $this->routeName($request, $action)])
            ->all();
    }
}
