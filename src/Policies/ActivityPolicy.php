<?php

namespace Javaabu\Activitylog\Policies;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Activitylog\Activitylog;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Permissions\Policies\ResourcePolicy;

/**
 * Who may see, roll back and export activity logs
 *
 * | Ability   | Permission                                                                               |
 * | --------- | ---------------------------------------------------------------------------------------- |
 * | viewAny   | view_logs or view_all_logs                                                               |
 * | viewAll   | view_all_logs                                                                            |
 * | view      | view_all_logs, or view_logs and the log is about a model the user may see, or by them    |
 * | rollback  | rollback_logs, view, and `update` on the subject; the log must be one that can be rolled back |
 * | export    | export_logs and viewAny                                                                  |
 *
 * Logs can't be created, edited or deleted from the app. Override it with
 * `config('activitylog.policy')`, `config('permissions.policies')` or `Gate::policy()`.
 */
class ActivityPolicy extends ResourcePolicy
{
    protected string $resource = 'logs';

    public function viewAny(Model $user): bool
    {
        return $this->allows($user, $this->permission('view'), $this->permission('view_all'));
    }

    public function viewAll(Model $user): bool
    {
        return $this->allows($user, $this->permission('view_all'));
    }

    public function view(Model $user, Model $log): bool
    {
        if ($this->viewAll($user)) {
            return true;
        }

        if (! $this->allows($user, $this->permission('view'))) {
            return false;
        }

        if ($log instanceof Activity && $log->isCausedBy($user)) {
            return true;
        }

        return in_array($log->getAttribute('subject_type'), Activitylog::viewableSubjectTypes($user), true);
    }

    public function rollback(Model $user, Model $log): bool
    {
        if (! $log instanceof Activity) {
            return false;
        }

        if (! $this->allows($user, $this->permission('rollback'))) {
            return false;
        }

        if (! $this->view($user, $log)) {
            return false;
        }

        $subject = $log->subject;

        if (! $subject || ! $user->can('update', $subject)) {
            return false;
        }

        return $log->canBeRolledBack();
    }

    public function export(Model $user): bool
    {
        return $this->allows($user, $this->permission('export')) && $this->viewAny($user);
    }

    public function create(Model $user): bool
    {
        return false;
    }

    public function update(Model $user, Model $model): bool
    {
        return false;
    }

    public function delete(Model $user, Model $model): bool
    {
        return false;
    }

    public function restore(Model $user, Model $model): bool
    {
        return false;
    }

    public function forceDelete(Model $user, Model $model): bool
    {
        return false;
    }

    public function viewTrash(Model $user): bool
    {
        return false;
    }
}
