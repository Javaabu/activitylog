<?php

namespace Javaabu\Activitylog;

trait ModelEnumTrait
{
    protected static array $labels = [];

    /**
     * Type Classes
     */
    protected static array $types = [];

    /**
     * Register new types
     */
    public static function register(array $types, bool $merge = true): array
    {
        static::$types = $merge ? $types + static::$types : $types;

        return static::$types;
    }

    /**
     * Initialize labels
     * @return void
     */
    protected static function initLabels(): void
    {
        static::$labels = [];

        $types = collect(static::$types)->sort();

        foreach ($types as $type) {
            $slug = with(new $type())->getMorphClass();

            static::$labels[$slug] = slug_to_title($slug);
        }
    }

    /**
     * Get label for key
     *
     * @param $key
     * @return string
     */
    public static function getLabel(string $key): string
    {
        return isset(static::getLabels()[$key]) ? trans(static::getLabels()[$key]) : '';
    }

    /**
     * Get type labels
     * @return array
     */
    public static function getLabels(): array
    {
        //first initialize
        if (empty(static::$labels)) {
            static::initLabels();
        }

        return static::$labels;
    }

    /**
     * Get keys
     *
     * @return array
     */
    public static function getKeys(): array
    {
        return array_keys(static::getLabels());
    }

    /**
     * Get label for key
     *
     * @param $key
     * @return string
     */
    public static function getSlug(string $key): string
    {
        return $key;
    }

    /**
     * Check if is a valid key
     *
     * @param $key
     * @return bool
     */
    public static function isValidKey(string $key): bool
    {
        return array_key_exists($key, self::getLabels());
    }

    /**
     * Get the types
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return static::$types;
    }
}
