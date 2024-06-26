<?php

namespace Javaabu\Activitylog\Models;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Javaabu\Helpers\AdminModel\AdminModel;
use Javaabu\Helpers\AdminModel\IsAdminModel;
use Javaabu\Activitylog\SubjectTypes;
use Spatie\Activitylog\Models\Activity as BaseActivity;

class Activity extends BaseActivity implements AdminModel
{
    use IsAdminModel;

    /**
     * Boot functions from laravel.
     */
    public static function boot()
    {
        parent::boot();

        //log ip
        static::creating(function ($log) {
            $log->ip = request_ip();
        });
    }

    /**
     * A search scope
     *
     * @param $query
     * @param $search
     * @return mixed
     */
    public function scopeSearch($query, $search): mixed
    {
        return $query->where('description', $search);
    }

    /**
     * Get the admin url attribute
     */
    public function getAdminUrlAttribute(): string
    {
        return route('admin.logs.show', $this);
    }

    /**
     * Get name attribute
     * @return string
     */
    public function getAdminLinkNameAttribute(): string
    {
        if ($date = $this->created_at) {
            return __(':rel (:time)', ['rel' => $date->diffForHumans(), 'time' => $date->format('j M Y @ H:i')]);
        }

        return __('Log #:id', ['id' => $this->id]);
    }

    /**
     * With relations scope
     *
     * @param $query
     * @return
     */
    public function scopeWithRelations($query)
    {
        return $query->with('causer', 'subject');
    }

    /**
     * User visible scope
     *
     * @param $query
     */
    public function scopeUserVisible($query)
    {
        $user = auth()->user();

        if ($user->can('viewAny', static::class)) {
            if ($user->can('view_all_logs')) {
                // can view all
                return $query;
            } else {
                return $query->whereIn('subject_type', $this->allowedSubjects($user));
            }
        }

        return $query->whereId(-1);
    }

    /**
     * Get the allowed subjects for the user
     *
     * @param Authorizable $user
     * @return array
     */
    public function allowedSubjects(Authorizable $user)
    {
        $allowed = [];

        $types = SubjectTypes::getTypes();
        foreach ($types as $type) {
            /** @var Model $model_instance */
            $model_instance = new $type();
            $is_allowed = method_exists($model_instance, 'canViewActivityLogs')
                        ? $model_instance->canViewActivityLogs($user) :
                          $user->can('create', $type);

            if ($is_allowed) {
                $allowed[] = $model_instance->getMorphClass();
            }
        }

        return $allowed;
    }
}
