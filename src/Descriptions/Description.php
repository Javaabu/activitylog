<?php

namespace Javaabu\Activitylog\Descriptions;

use Illuminate\Contracts\Support\Htmlable;
use JsonSerializable;
use Stringable;

/**
 * A readable description: a translated template and the values of its placeholders
 *
 * As text, the values are put in as they are. As HTML, they're escaped and wrapped in
 * `<bdi>`, so names and values in another script read correctly in a right-to-left sentence.
 */
class Description implements Htmlable, JsonSerializable, Stringable
{
    /**
     * @param  array<string, string>  $replacements  placeholder (without the colon) => value
     */
    public function __construct(
        public readonly string $template,
        public readonly array $replacements = [],
    ) {}

    public static function plain(string $text): self
    {
        return new self($text);
    }

    public function toString(): string
    {
        return $this->replace(fn (string $value) => $value, fn (string $template) => $template);
    }

    public function toHtml(): string
    {
        return $this->replace(
            fn (string $value) => '<bdi>' . e($value) . '</bdi>',
            fn (string $template) => e($template),
        );
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    /**
     * @param  callable(string): string  $value
     * @param  callable(string): string  $template
     */
    protected function replace(callable $value, callable $template): string
    {
        $pairs = [];

        foreach ($this->replacements as $key => $replacement) {
            $pairs[":{$key}"] = $value((string) $replacement);
        }

        uksort($pairs, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        return strtr($template($this->template), $pairs);
    }
}
