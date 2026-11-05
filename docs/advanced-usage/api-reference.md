---
title: API reference
sidebar_position: 9
---

# Javaabu\Activitylog\Traits\LogsActivity

Uses spatie's `Spatie\Activitylog\Models\Concerns\LogsActivity`, so all of spatie's methods are there too (`activitiesAsSubject()`, `disableLogging()`, `enableLogging()`, `attributesToBeLogged()`, ...).

| Method | Returns |
| --- | --- |
| `getActivitylogOptions()` | `LogOptions`: `defaultActivitylogOptions()` unless overridden |
| `defaultActivitylogOptions()` | Fillable (or all) attributes, except `activitylogExceptAttributes()`, only dirty, no empty logs, no timestamp-only logs |
| `activitylogExceptAttributes()` | Hidden attributes, the primary key, timestamps and matching `sensitive_attributes` |
| `activitylogTimestampAttributes()` | `created_at`, `updated_at`, `deleted_at` (the model's names) |
| `static activitylogTags()` | `array<string, string\|Tag>`: tags for this model's events |
| `describeActivity(Activity $activity)` | `Description\|string\|null`: a description of one of its logs |
| `static activitylogAttributeLabels()` | `array<string, string>`: attribute labels |

Optional methods the package looks for on models (with or without the trait):

| Method | Used for |
| --- | --- |
| `activitylogDisplayName(): string` | The model's name in descriptions and pages |
| `static activitylogLabel(): string` | The model's label ("Invoice") |
| `activitylogUrl(): ?string` | Where the pages link the model to |
| `static activitylogViewableBy(Model $user): bool` | Whether a `view_logs` user sees its logs |
| `activitylogCanRollback(Activity $activity): bool` | Whether it may be rolled back to that log |

# Javaabu\Activitylog\Models\Activity

Extends `Spatie\Activitylog\Models\Activity`.

| Member | Returns |
| --- | --- |
| `ip` | The IP address it was logged from |
| `changes()` | `['attributes' => [...], 'old' => [...]]`, from `attribute_changes` or `properties` |
| `newValues()`, `oldValues()` | One side of the changes |
| `changedAttributes()` | The attributes with values |
| `hasLoggedChanges()` | Whether there are any |
| `customProperties()` | `properties` without logged changes and rollback bookkeeping |
| `tag()` | `Tag` |
| `tagKey()` | The event, or the description when it's a known tag |
| `describe()` | `Description` |
| `rollback()` | `Rollback` |
| `canBeRolledBack()` | `bool` |
| `isCausedBy(Model $user)` | `bool` |
| `recordIp()` | Sets `ip` from the request (when `log_ip` is on) |
| `scopeVisibleTo(?Model $user)` | The logs the user may see |
| `scopeTagged(string\|array $tags)` | Logs with these tags |
| spatie's `scopeForSubject()`, `scopeCausedBy()`, `scopeForEvent()`, `scopeInLog()` | |

# Javaabu\Activitylog\Activitylog

All static.

| Method | Returns |
| --- | --- |
| `ignoreMigrations()` | Stop loading the package's migrations |
| `activityModel()`, `newActivityModel()` | The activity model class / a new instance |
| `morphedModel(?string $type)` | The class of a morph type, or `null` |
| `registerTag(Tag\|string $tag, ?string $label, ?string $color, ?string $icon)`, `registerTags(string\|array $tags)` | Add tags |
| `tags()`, `tag(?string $key)`, `findTag(?string $key)`, `hasTag(?string $key)`, `forgetTags()` | Read and reset tags |
| `tagData(Tag $tag)` | `{key, label, color, icon, svg}` |
| `describe(string\|array $event, Closure\|string $description, ?string $model = null)` | Register a description |
| `findDescription(?string $event, ?string $model = null)`, `forgetDescriptions()` | Read and reset descriptions |
| `viewableSubjectTypes(Model $user)`, `forgetViewableSubjectTypes()` | The subject types a `view_logs` user sees |
| `history(Model $subject, ?Model $user = null, int $limit = 10, string $route = 'logs')` | `{logs, url, strings}` for `ActivityHistory` |
| `historyQuery(Model $subject, ?Model $user = null)` | The record's visible logs, newest first |
| `strings()` | The pages' translated strings |
| `displayName(Model $model)` | The model's name |
| `url(Model $model)` | `activitylogUrl()`, or `null` |

# Tags

| Class | What |
| --- | --- |
| `Javaabu\Activitylog\Contracts\Tag` | `key()`, `label()`, `color()`, `icon()` |
| `Javaabu\Activitylog\Enums\ActivityTags` | The built-in tags |
| `Javaabu\Activitylog\Tags\ActivityTag` | A tag from config or code: `new ActivityTag($key, $label, $color = 'secondary', $icon = 'activity')`, `fromArray()`, `fallback()` |
| `Javaabu\Activitylog\Support\Icons` | `svg(string $name)`, `has(string $name)` |

# Descriptions

| Class | What |
| --- | --- |
| `Javaabu\Activitylog\Descriptions\Describer` | `describe(Activity)`, `replacements()`, `causerName()`, `subjectName()`, `modelLabel()`, `attributeLabel()`, `attributeList()`, `formatValue()`; `$max_listed_attributes = 3`, `$max_value_length = 60` |
| `Javaabu\Activitylog\Descriptions\Description` | `template`, `replacements`, `toString()`, `toHtml()`, `plain(string)`; `Htmlable`, `Stringable`, `JsonSerializable` |

# Diffs and rollback

| Class | What |
| --- | --- |
| `Javaabu\Activitylog\Support\Diff` | `forActivity(Activity)`, `between(array $from, array $to, ?string $model)`, `expanded()`, `rows()`, `isEmpty()`; `attributes`, `old`, `new`, `model` |
| `Javaabu\Activitylog\Rollback\Rollback` | `to(Activity)`, `subject()`, `reason()`, `isPossible()`, `targetValues()`, `currentValues()`, `changes()`, `perform(?Model $causer)`; `VERSION_EVENTS` |
| `Javaabu\Activitylog\Rollback\RollbackNotPossible` | Thrown by `perform()` |
| `Javaabu\Activitylog\Events\ModelRolledBack` | `subject`, `version`, `log`, `causer` |

# HTTP

| Class | What |
| --- | --- |
| `Javaabu\Activitylog\Routing\ActivitylogRoutes` | `register(Router, array $options)` (behind `Route::activitylog()`), `guard(Request)`, `namePrefix(Request)` |
| `Javaabu\Activitylog\Http\Controllers\ActivityLogsController` | `index`, `show`, `confirmRollback`, `rollback` |
| `Javaabu\Activitylog\Http\Resources\ActivityResource` | The JSON of a log; `detailed()` adds changes, properties and rollback |
| `Javaabu\Activitylog\Filters\ActivityFilters` | The filters; `forUser(?Model)` |
| `Javaabu\Activitylog\Policies\ActivityPolicy` | The policy |
| `Javaabu\Activitylog\Views\ViewRenderer` | `render(string $page, array $data)`, `stack()`, `isInertia()`, `inertiaPage()` |
| `Javaabu\Activitylog\Exports\ActivityLogExport` | `for(?Model $user, array $parameters)`, `fromRequest(Request)` |

# Translated models

`Javaabu\Activitylog\Support\Translations`: see [Translated models](translatable.md#api).

# React (`@javaabu/activitylog`)

| Export | What |
| --- | --- |
| `ActivityTag` | `{ tag }` |
| `ActivityDescription` | `{ log }` |
| `ActivityDiff` | `{ changes, strings, oldLabel?, newLabel? }` |
| `ActivityDetails` | `{ log, strings }` |
| `ActivityTable` | `{ logs, strings, empty?, pagination? }` |
| `ActivityHistory` | `{ history, title?, className? }` |
| Types | `ActivityData`, `ActivityTagData`, `ActivityParty`, `ActivityChange`, `ActivitylogStrings`, `ActivityHistoryData`, `Paginated`, `LogsIndexProps`, `LogsShowProps`, `LogsRollbackProps`, ... |

Pages: `activitylog::Logs/Index`, `activitylog::Logs/Show`, `activitylog::Logs/Rollback`.
