<?php

namespace Javaabu\Activitylog;

use BackedEnum;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Javaabu\Activitylog\Contracts\Tag;
use Javaabu\Activitylog\Enums\ActivityTags;
use Javaabu\Activitylog\Http\Resources\ActivityResource;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Support\Icons;
use Javaabu\Activitylog\Support\Translations;
use Javaabu\Activitylog\Tags\ActivityTag;

/**
 * Package-wide settings and registries: tags, descriptions and the activity model
 */
class Activitylog
{
    /** Whether the package loads its migrations. Turn off with ignoreMigrations(). */
    public static bool $runs_migrations = true;

    /** Whether the IP callback was added to spatie's LogActivityAction (once per process) */
    public static bool $records_ip_before_logging = false;

    /** @var array<string, Tag> Tags registered at runtime */
    protected static array $tags = [];

    /** @var array<string, Tag>|null Built-in, config and registered tags, merged */
    protected static ?array $resolved_tags = null;

    /** @var array<string, array<int, string>> User => the subject types they may see logs of */
    protected static array $viewable_subject_types = [];

    /** @var array<string, array<string, Closure|string>> Model class (or `*`) => event => description */
    protected static array $descriptions = [];

    /**
     * Don't load the package's migrations; publish them with `--tag=activitylog-migrations` instead
     */
    public static function ignoreMigrations(): void
    {
        static::$runs_migrations = false;
    }

    /**
     * The activity model class, from `config('activitylog.activity_model')`
     *
     * @return class-string<Activity>
     */
    public static function activityModel(): string
    {
        return config('activitylog.activity_model') ?: Activity::class;
    }

    public static function newActivityModel(): Activity
    {
        $class = static::activityModel();

        return new $class;
    }

    /**
     * The model class of a morph type, e.g. `post` → `App\Models\Post`
     *
     * @return class-string|null
     */
    public static function morphedModel(?string $type): ?string
    {
        if (! $type) {
            return null;
        }

        $class = Relation::getMorphedModel($type) ?? $type;

        return class_exists($class) ? $class : null;
    }

    /**
     * Add a tag, or replace one with the same key
     *
     * ```php
     * Activitylog::registerTag('published', label: 'Published', color: 'success', icon: 'circle-check');
     * Activitylog::registerTag(InvoiceTags::PAID);
     * ```
     */
    public static function registerTag(Tag|string $tag, ?string $label = null, ?string $color = null, ?string $icon = null): void
    {
        if (is_string($tag)) {
            $tag = ActivityTag::fromArray($tag, array_filter(compact('label', 'color', 'icon')), static::findTag($tag));
        }

        static::$tags[$tag->key()] = $tag;
        static::$resolved_tags = null;
    }

    /**
     * Add many tags: Tag instances, or a backed enum class whose cases implement Tag
     *
     * @param  class-string<BackedEnum&Tag>|array<int, Tag>  $tags
     */
    public static function registerTags(string|array $tags): void
    {
        if (is_string($tags)) {
            if (! is_a($tags, BackedEnum::class, true) || ! is_a($tags, Tag::class, true)) {
                throw new InvalidArgumentException("[{$tags}] must be a backed enum implementing " . Tag::class . '.');
            }

            $tags = $tags::cases();
        }

        foreach ($tags as $tag) {
            static::registerTag($tag);
        }
    }

    /**
     * Every known tag, keyed by event: the built-in ones, then `activitylog.tags`, then registered tags
     *
     * @return array<string, Tag>
     */
    public static function tags(): array
    {
        if (static::$resolved_tags !== null) {
            return static::$resolved_tags;
        }

        $tags = [];

        foreach (ActivityTags::cases() as $tag) {
            $tags[$tag->key()] = $tag;
        }

        foreach (config('activitylog.tags', []) as $key => $definition) {
            $tags[$key] = $definition instanceof Tag
                ? $definition
                : ActivityTag::fromArray($key, (array) $definition, $tags[$key] ?? null);
        }

        return static::$resolved_tags = [...$tags, ...static::$tags];
    }

    public static function hasTag(?string $key): bool
    {
        return $key !== null && array_key_exists($key, static::tags());
    }

    public static function findTag(?string $key): ?Tag
    {
        return $key === null ? null : (static::tags()[$key] ?? null);
    }

    /**
     * The tag for a key, or a plain tag labelled after the key when nothing registered it
     */
    public static function tag(?string $key): Tag
    {
        if ($key === null || $key === '') {
            return static::tags()[ActivityTags::DEFAULT->value];
        }

        return static::findTag($key) ?? ActivityTag::fallback($key);
    }

    /**
     * A tag as the pages and API serialize it; `svg` is the icon's inner SVG, or null for an icon font
     *
     * @return array{
     *     key: string,
     *     label: string,
     *     color: string,
     *     icon: string,
     *     svg: string|null
     * }
     */
    public static function tagData(Tag $tag): array
    {
        return [
            'key' => $tag->key(),
            'label' => $tag->label(),
            'color' => $tag->color(),
            'icon' => $tag->icon(),
            'svg' => Icons::svg($tag->icon()),
        ];
    }

    /**
     * Forget registered tags (and the config cache), e.g. between tests
     */
    public static function forgetTags(): void
    {
        static::$tags = [];
        static::$resolved_tags = null;
    }

    /**
     * Describe an event (or a description, for logs without an event), for every model or one
     *
     * The description is a template with placeholders (:causer, :subject, :type, :attribute,
     * :attributes, :count, :old, :new, :tag, :date, :description), translated when used, or a
     * closure that gets the log and the placeholder values and returns a string or a Description.
     *
     * ```php
     * Activitylog::describe('paid', ':causer marked :type :subject as paid', Invoice::class);
     * Activitylog::describe('sent', fn (Activity $log, array $values) => __(...));
     * ```
     *
     * @param  string|array<int, string>  $event
     * @param  class-string|null  $model
     */
    public static function describe(string|array $event, Closure|string $description, ?string $model = null): void
    {
        foreach (Arr::wrap($event) as $key) {
            static::$descriptions[$model ?? '*'][$key] = $description;
        }
    }

    /**
     * The registered description of an event: for the model first (registered, then config),
     * then for every model
     *
     * @param  class-string|null  $model
     */
    public static function findDescription(?string $event, ?string $model = null): Closure|string|null
    {
        if ($event === null || $event === '') {
            return null;
        }

        $config = config('activitylog.descriptions', []);

        if ($model) {
            $found = static::$descriptions[$model][$event]
                ?? (is_array($config[$model] ?? null) ? ($config[$model][$event] ?? null) : null);

            if ($found !== null) {
                return $found;
            }
        }

        $global = $config[$event] ?? null;

        return static::$descriptions['*'][$event] ?? (is_array($global) ? null : $global);
    }

    public static function forgetDescriptions(): void
    {
        static::$descriptions = [];
    }

    /**
     * The subject types (morph types) whose logs a user with `view_logs` may see: those whose
     * model's static activitylogViewableBy($user) is true, or, without it, whose `viewAny` the
     * user may
     *
     * @return array<int, string>
     */
    public static function viewableSubjectTypes(Model $user): array
    {
        $key = $user->getMorphClass() . ':' . $user->getKey();

        if (isset(static::$viewable_subject_types[$key])) {
            return static::$viewable_subject_types[$key];
        }

        $types = static::newActivityModel()
            ->newQuery()
            ->whereNotNull('subject_type')
            ->distinct()
            ->pluck('subject_type')
            ->filter(function (string $type) use ($user) {
                $model = static::morphedModel($type);

                if (! $model) {
                    return false;
                }

                if (method_exists($model, 'activitylogViewableBy')) {
                    return (bool) $model::activitylogViewableBy($user);
                }

                return $user->can('viewAny', $model);
            })
            ->values()
            ->all();

        return static::$viewable_subject_types[$key] = $types;
    }

    /**
     * Forget the subject types worked out for users, e.g. after their permissions change
     */
    public static function forgetViewableSubjectTypes(): void
    {
        static::$viewable_subject_types = [];
    }

    /**
     * Where the pages link a causer or subject to: the model's activitylogUrl(), or nothing
     */
    public static function url(Model $model): ?string
    {
        if (method_exists($model, 'activitylogUrl')) {
            return $model->activitylogUrl();
        }

        return null;
    }

    /**
     * A model's latest logs that a user may see, for the history component on the app's own pages
     *
     * ```php
     * return Inertia::render('Invoices/Show', [
     *     'invoice' => $invoice,
     *     'history' => Activitylog::history($invoice, $request->user(), route: 'admin.logs'),
     * ]);
     * ```
     *
     * @param  string  $route  the name prefix the log routes were registered with
     * @return array{
     *     logs: array<int, array<string, mixed>>,
     *     url: string|null,
     *     strings: array<string, string>
     * }
     */
    public static function history(Model $subject, ?Model $user = null, int $limit = 10, string $route = 'logs'): array
    {
        $user ??= auth()->user();

        $logs = static::historyQuery($subject, $user)->limit($limit)->get();

        $url = Route::has("{$route}.index") && $user?->can('viewAny', static::activityModel())
            ? route("{$route}.index", ['subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey()])
            : null;

        return [
            'logs' => $logs->map(fn (Activity $log) => static::historyItem($log, $route))->all(),
            'url' => $url,
            'strings' => static::strings(),
        ];
    }

    /**
     * The logs of a model that a user may see, newest first
     *
     * @return Builder<Activity>
     */
    public static function historyQuery(Model $subject, ?Model $user = null): Builder
    {
        return static::activityModel()::query()
            ->visibleTo($user)
            ->where('subject_type', $subject->getMorphClass())
            ->whereIn('subject_id', Translations::relatedKeys($subject))
            ->with(['causer', 'subject'])
            ->latest('created_at')
            ->latest((new (static::activityModel()))->getKeyName());
    }

    /**
     * @return array<string, mixed>
     */
    protected static function historyItem(Activity $log, string $route): array
    {
        $data = (new ActivityResource($log))->resolve(request());

        $data['links']['show'] = Route::has("{$route}.show") ? route("{$route}.show", $log->getKey()) : null;

        return $data;
    }

    /**
     * The translated text of the log pages and components, for the React pages and components
     *
     * @return array<string, string>
     */
    public static function strings(): array
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
            'cancel' => __('Cancel'),
            'back' => __('Back to the activity log'),
            'back_to_log' => __('Back to the log'),
            'system' => __('System'),
            'deleted_user' => __('A deleted user'),
            'deleted_record' => __('Deleted'),
            'all_activity' => __('All activity'),
        ];
    }

    /**
     * What a model is called in descriptions and pages: its activitylogDisplayName(), or its
     * name, title, label, number or email, or `#` and its key
     */
    public static function displayName(Model $model): string
    {
        if (method_exists($model, 'activitylogDisplayName')) {
            return (string) $model->activitylogDisplayName();
        }

        foreach (['name', 'title', 'label', 'number', 'email'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_scalar($value) && (string) $value !== '') {
                return (string) $value;
            }
        }

        return "#{$model->getKey()}";
    }
}
