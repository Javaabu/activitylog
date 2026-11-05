---
title: Dhivehi and right-to-left
sidebar_position: 7
---

Every string the package shows is translated, with English and Dhivehi in `lang/en.json` and `lang/dv.json`: the pages, tag labels, descriptions, rollback messages, export headings and permission descriptions.

```php
app()->setLocale('dv');

(string) $log->describe();
// "އަހުމަދު Post ފައިނަލް ގެ title، ރިޕޯޓު އިން ފައިނަލް އަށް ބަދަލުކޮށްފި"
```

Descriptions are built when they're shown, so the same log reads in English for one user and in Dhivehi for another.

# Changing the translations

Publish them and edit the copy in your app:

```bash
php artisan vendor:publish --tag=activitylog-translations
```

This copies the files to `lang/vendor/activitylog`. Since the package uses JSON translations, you can also put any key in your app's own `lang/dv.json`; the app's files win.

Your own [tag labels](../basic-usage/tags.md), [description templates](../basic-usage/descriptions.md), model labels (`activitylogLabel()`) and attribute labels (`activitylogAttributeLabels()`, or `validation.attributes`) are passed through `__()`, so translate them the same way. Description templates keep their placeholders in any order, so the Dhivehi sentence can put the verb last:

```json
{
    ":causer marked :type :subject as paid": ":causer :type :subject ފައިސާ ދައްކާފައިވާ ކަމަށް ބަހައްޓައިފި"
}
```

# Right-to-left

- The Blade and React views use logical utilities only (`ms-`, `pe-`, `start-`, `end-`), never `left`/`right`; a test checks every class list.
- Names and values inside descriptions are wrapped in `<bdi>` (the HTML of `$log->describe()`, and `description_html` in the JSON), so a Latin name in a Dhivehi sentence, or a Thaana name in an English one, keeps its own direction.
- Values in diffs and properties have `dir="auto"`.
- The search field of the filter bar types Thaana (`TextFilter::thaana()`), and finds Thaana inside logged changes as well as in descriptions.
- Dates are formatted by Carbon in the current locale.

# Translations to review

These Dhivehi strings were written without a native-speaker review. Please check them, especially:

| English | Dhivehi | Note |
| --- | --- | --- |
| Activity log | ހަރަކާތްތަކުގެ ލޮގް | The page title and permission group |
| History | ތާރީޚު | Kept apart from "Date" (ތާރީޚް); a better word for a record's change history may exist |
| Roll back | ރައްދުކުރޭ | Also "ކުރީގެ ވަރޝަނަށް ރައްދުކުރުން" in longer strings |
| Rolled back | ކުރީގެ ވަރޝަނަށް ރައްދުކުރެވިއްޖެ | The tag label; long for a badge |
| Attribute | މަޢުލޫމާތު | Same word as "Details" |
| Failed login | ނާކާމިޔާބު ލޮގިން | |
| `:causer ... ކޮށްފި` sentences | | The descriptions use the "-ފި" past form throughout; check that it reads naturally in a log |
| :causer was locked out after too many login attempts | ގިނަ ފަހަރު ލޮގިން ވާން މަސައްކަތްކުރުމުން :causer ލޮކްވެއްޖެ | |
| empty | ހުސް | Shown for empty values in diffs and descriptions |
