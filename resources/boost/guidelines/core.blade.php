{{--
    javaabu/activitylog guidelines for AI code assistants

    Maintained by: Javaabu (https://javaabu.com)
--}}

## javaabu/activitylog

- This project logs model changes with `javaabu/activitylog`, built on spatie/laravel-activitylog **v5**. Log a model with `use Javaabu\Activitylog\Traits\LogsActivity;` and customize it by overriding `getActivitylogOptions()` starting from `$this->defaultActivitylogOptions()`; never with v1's static `$logAttributes`, `$ignoreChangedAttributes` or `$logExceptAttributes` properties (they throw). `LogOptions` is `Spatie\Activitylog\Support\LogOptions`.
- Always activate the `javaabu-activitylog` skill when logging a model or a custom event, reading or showing logs, adding a record's history to a page, registering the log pages, adding tags or descriptions, rolling back, exporting logs, or changing who may see logs.
