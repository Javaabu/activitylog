<?php

namespace Javaabu\Activitylog\Support;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Translatable\Contracts\JsonTranslatable;
use Javaabu\Translatable\Translatable;

/**
 * Optional javaabu/translatable support: everything here works, and does nothing, without it
 *
 * - Changes to a JSON-translatable model's `translations` column are shown per language and
 *   field ("Title (ދިވެހި)") in diffs, descriptions and exports.
 * - The history of a DB-translatable model includes the logs of its other language rows.
 */
class Translations
{
    /** The column JSON-translatable models keep their translations in */
    public const string COLUMN = 'translations';

    public static function enabled(): bool
    {
        return class_exists(Translatable::class) && Translatable::enabled();
    }

    public static function supports(object|string|null $model, ?string $field = null): bool
    {
        return class_exists(Translatable::class) && Translatable::supports($model, $field);
    }

    public static function isJsonTranslatable(object|string|null $model): bool
    {
        return $model !== null
            && is_a($model, JsonTranslatable::class, true)
            && static::supports($model);
    }

    public static function isDbTranslatable(object|string|null $model): bool
    {
        return class_exists(Translatable::class)
            && Translatable::isDbTranslatable($model)
            && static::supports($model);
    }

    /**
     * Replace the `translations` column in a log's changes with one entry per language and field,
     * keyed `translations.{locale}.{field}`, for the ones that changed
     *
     * @param  class-string|null  $model
     * @param  array<int, string>  $attributes
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @return array{0: array<int, string>, 1: array<string, mixed>, 2: array<string, mixed>}
     */
    public static function expand(?string $model, array $attributes, array $old, array $new): array
    {
        if (! in_array(self::COLUMN, $attributes, true) || ! static::isJsonTranslatable($model)) {
            return [$attributes, $old, $new];
        }

        $old_translations = static::decode($old[self::COLUMN] ?? null);
        $new_translations = static::decode($new[self::COLUMN] ?? null);
        $translatables = (new $model)->getTranslatables();

        $expanded = [];
        $expanded_old = [];
        $expanded_new = [];

        foreach (array_unique([...array_keys($old_translations), ...array_keys($new_translations)]) as $locale) {
            $fields = array_unique([
                ...array_keys((array) ($old_translations[$locale] ?? [])),
                ...array_keys((array) ($new_translations[$locale] ?? [])),
            ]);

            foreach (array_intersect($fields, $translatables) as $field) {
                $before = $old_translations[$locale][$field] ?? null;
                $after = $new_translations[$locale][$field] ?? null;

                if ($before === $after) {
                    continue;
                }

                $key = self::COLUMN . ".{$locale}.{$field}";
                $expanded[] = $key;

                if (array_key_exists(self::COLUMN, $old)) {
                    $expanded_old[$key] = $before;
                }

                if (array_key_exists(self::COLUMN, $new)) {
                    $expanded_new[$key] = $after;
                }
            }
        }

        $position = array_search(self::COLUMN, $attributes, true);
        array_splice($attributes, $position, 1, $expanded);

        unset($old[self::COLUMN], $new[self::COLUMN]);

        return [$attributes, $old + $expanded_old, $new + $expanded_new];
    }

    /**
     * The field and locale of an expanded key: `translations.dv.title` → ['title', 'dv']
     *
     * @return array{0: string, 1: string}|null
     */
    public static function parseKey(string $attribute): ?array
    {
        if (! preg_match('/^' . self::COLUMN . '\.([^.]+)\.(.+)$/', $attribute, $matches)) {
            return null;
        }

        return [$matches[2], $matches[1]];
    }

    /**
     * The name of a language, in that language: `dv` → ދިވެހި
     */
    public static function languageName(string $locale): string
    {
        if (! class_exists(Translatable::class)) {
            return $locale;
        }

        $language = Translatable::locale($locale);

        return $language?->native_name ?: ($language?->name ?: $locale);
    }

    /**
     * The keys of a DB-translatable model's language rows (itself, its default row and the
     * other translations of that row), so its history includes all of them
     *
     * @return array<int, mixed>
     */
    public static function relatedKeys(Model $model): array
    {
        if (! static::isDbTranslatable($model)) {
            return [$model->getKey()];
        }

        $parent = $model->getAttribute('translatable_parent_id') ?? $model->getKey();

        $keys = $model->newQuery()
            ->where(fn ($query) => $query->whereKey($parent)->orWhere('translatable_parent_id', $parent))
            ->pluck($model->getKeyName())
            ->all();

        return array_values(array_unique([$model->getKey(), ...$keys]));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function decode(mixed $translations): array
    {
        if (is_string($translations)) {
            $translations = json_decode($translations, true);
        }

        return is_array($translations) ? $translations : [];
    }
}
