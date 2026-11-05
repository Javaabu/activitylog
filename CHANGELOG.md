# Changelog

All notable changes will be documented in this file

## 2.0.0 - unreleased

- Requires PHP 8.4+, Laravel 12+ and spatie/laravel-activitylog v5; no longer requires javaabu/helpers
- Models configure logging with spatie's `getActivitylogOptions()`, starting from `defaultActivitylogOptions()`; the v1 static properties throw `UnsupportedLogProperty`
- Safer defaults: fillable attributes, never hidden or sensitive attributes, the key or timestamps; no timestamp-only or empty logs
- A migration moves v1 logs' changes into spatie v5's `attribute_changes` column
- Tags with colours, icons and translated labels, configurable and per model
- Readable, translatable descriptions, customizable per model and event
- `Route::activitylog()`: the activity log, a record's history and each log's changes, in Blade or Inertia React, with javaabu/filters; `<x-activitylog::history>` and `ActivityHistory` for app pages
- Rolling back a record to the version of a log, with a confirmation, authorization and a `rolled_back` log
- Exporting the filtered logs with javaabu/exports
- `ActivityPolicy` and the `view_logs`, `view_all_logs`, `rollback_logs` and `export_logs` permissions through javaabu/permissions; any guard
- Optional javaabu/translatable support
- Dhivehi translations and right-to-left views
- Docs for people and AI assistants, `llms.txt`, and a Laravel Boost guideline and skill

## 1.0.0 - 2024-02-05

- initial release
