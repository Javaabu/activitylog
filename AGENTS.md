# activitylog: notes for AI coding agents

`javaabu/activitylog` is a layer over spatie/laravel-activitylog **v5**: a `LogsActivity` trait with safe default `LogOptions`, an `Activity` model (IP, changes, tags, descriptions, rollback, visibility), tags, readable descriptions, routes with an overridable controller and JSON API, Blade and Inertia React pages, rollback, an export for javaabu/exports, filters from javaabu/filters, and a policy with permissions from javaabu/permissions. Apps use the trait's methods, `Activitylog`'s static API, the `Tag` contract, `Describer`/`Description`, `Rollback`, `Route::activitylog()`, the view names and the JSON shape of `ActivityResource`: treat them as public contracts.

## Layout

| Path | What |
| --- | --- |
| `src/Traits/LogsActivity.php` | The model trait: default options, the v1-property guard, per-model hooks |
| `src/Models/Activity.php` | The log model: `changes()`, `tag()`, `describe()`, `rollback()`, `visibleTo()`, IP |
| `src/Activitylog.php` | Registries and helpers: tags, descriptions, visibility, history, strings, display names |
| `src/Contracts`, `src/Enums`, `src/Tags`, `src/Support/Icons.php`, `resources/icons` | Tags and their icons |
| `src/Descriptions` | `Describer` and `Description` |
| `src/Support/Diff.php`, `src/Rollback`, `src/Events` | Diffs, rollback, `ModelRolledBack` |
| `src/Support/Translations.php` | Optional javaabu/translatable support |
| `src/Routing`, `src/Http`, `src/Policies`, `src/Filters`, `src/Views`, `src/Exports` | `Route::activitylog()`, controller, resource, policy, filters, `ViewRenderer`, `ActivityLogExport` |
| `config/activitylog.php` | spatie's options and the package's, under the same key |
| `database/migrations` | v1's migrations (unchanged) and the v5 `attribute_changes` migration |
| `resources/views` | Blade pages and components (javaabu/forms) |
| `resources/js` | Inertia React pages and components (TypeScript), shipped through javaabu/manifest as `@javaabu/activitylog` |
| `lang/en.json`, `lang/dv.json` | Translations; English strings are the keys |
| `docs`, `llms.txt` | Docusaurus pages for docs.javaabu.com and the index for AI assistants |
| `resources/boost` | Laravel Boost guideline and skill, shipped in the package |
| `tests` | Testbench tests; two guards (`web` → `User`, `web_admin` → `Admin`); `tests/Translatable` needs javaabu/translatable; `tests/js` is Vitest |

## Rules

- Follow Javaabu codestyle ([boost-javaabu-guidelines](https://github.com/Javaabu/boost-javaabu-guidelines)): snake_case variables and properties, camelCase methods, early returns, `#[Test]` and `it_…` test names. Pint enforces formatting.
- Build on spatie v5's API; don't fork its behaviour. The package's trait uses spatie's and only overrides `getActivitylogOptions()` and `buildChanges()`.
- Never log hidden or sensitive attributes by default, and never restore them in a rollback.
- Descriptions are rendered at display time, never stored. Every template goes through `__()`.
- Nothing may assume the `web` guard or `App\Models\User`. Type-hint users as `Model`; test on both guards.
- javaabu/translatable and Inertia are optional: feature-detect them, keep them in `suggest`, and test both ways (CI runs the `Test Suite` without translatable).
- Never depend on javaabu/helpers (CI checks `composer show --tree --no-dev`).
- Every user-facing string goes through `__()` with an English key, and gets a line in `lang/en.json` and `lang/dv.json` (`TranslationsTest` checks keys and placeholders). Use Thaana in tests of user-facing behaviour. Views use logical CSS only (`RtlTest` checks).
- Every change needs tests, docs (linked from `llms.txt`), the Boost skill if it changes how the package is used, and a line in `CHANGELOG.md`.
- Work on the `next-gen` branch; don't commit to `main`.

## Commands

```bash
composer install
composer lint       # vendor/bin/pint --test
composer format     # vendor/bin/pint
composer test       # vendor/bin/phpunit (both suites)
pnpm install        # needs vendor/ for @javaabu/filters and @javaabu/stencil
pnpm typecheck
pnpm test           # Vitest
```
