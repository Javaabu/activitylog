<?php

namespace Javaabu\Activitylog;

use BackedEnum;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;
use Javaabu\Activitylog\Contracts\Tag;
use Javaabu\Activitylog\Enums\ActivityTags;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Support\Icons;
use Javaabu\Activitylog\Tags\ActivityTag;

/**
 * Package-wide settings and registries: tags, descriptions and the activity model
 */
class Activitylog
{
    /** Whether the package loads its migrations. Turn off with ignoreMigrations(). */
    public static bool $runs_migrations = true;

    /** @var array<string, Tag> Tags registered at runtime */
    protected static array $tags = [];

    /** @var array<string, Tag>|null Built-in, config and registered tags, merged */
    protected static ?array $resolved_tags = null;

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
}
