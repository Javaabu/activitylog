---
title: Translated models
sidebar_position: 6
---

When [javaabu/translatable](https://github.com/Javaabu/translatable) v3 is installed and has languages, the log pages understand translated models. Without it, nothing changes: the package doesn't require it and checks for it before using it.

```bash
composer require javaabu/translatable
```

# JSON-translated models

A model that keeps its translations in a `translations` JSON column (`IsJsonTranslatable`) logs that column like any other attribute. Diffs, descriptions and exports show it per language and field:

| Attribute | Old value | New value |
| --- | --- | --- |
| Title (ދިވެހި) | ރިޕޯޓު | ފައިނަލް |

```php
$article->addTranslation('dv', 'title', 'ފައިނަލް')->save();

(string) $article->activitiesAsSubject()->latest('id')->first()->describe();
// "Ahmed updated the title (ދިވެހި) of Article Report from ރިޕޯޓު to ފައިނަލް"
```

The language is named in its own language (the `native_name` of the translatable language). Only the model's translatable fields (`getTranslatables()`) are shown; translatable's own bookkeeping in the column is left out.

[Rolling back](../basic-usage/rolling-back.md) restores the `translations` column as it was, so every language goes back to that version. The confirmation shows the change per language and field.

Make sure the column is logged: it is by default when it's in `$fillable`, or when the model has no `$fillable`.

# DB-translated models

A model that keeps each language in its own row (`IsDbTranslatable`) logs each row separately. A record's history (the index filtered to it, `<x-activitylog::history>`, `Activitylog::history()`) includes the logs of all its language rows: the default row and its translations.

# Testing both ways

The package's own tests run with translatable installed, and again in CI with it removed (`composer remove --dev javaabu/translatable`), so nothing depends on it.

In an app, use translatable's `InteractsWithLanguages` to add languages in tests:

```php
use Javaabu\Translatable\Testing\InteractsWithLanguages;

$this->setUpLanguages(['en', 'dv']);
```

# API

`Javaabu\Activitylog\Support\Translations`:

| Method | Returns |
| --- | --- |
| `enabled()` | Whether translatable is installed and has languages |
| `supports($model, ?string $field = null)` | `Translatable::supports()`, or `false` without translatable |
| `isJsonTranslatable($model)` / `isDbTranslatable($model)` | Which kind of translated model it is |
| `expand(?string $model, array $attributes, array $old, array $new)` | The changes with `translations` replaced by `translations.{locale}.{field}` entries |
| `parseKey(string $attribute)` | `['title', 'dv']` for `translations.dv.title` |
| `languageName(string $locale)` | `ދިވެހި` for `dv` |
| `relatedKeys(Model $model)` | The keys of a DB-translated record's language rows |
