<?php

namespace Javaabu\Activitylog\Tests\Unit;

use Javaabu\Activitylog\Descriptions\Describer;
use Javaabu\Activitylog\Enums\ActivityTags;
use Javaabu\Activitylog\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

class TranslationsTest extends TestCase
{
    #[Test]
    public function every_string_has_a_dhivehi_translation(): void
    {
        $dv = $this->read('dv');
        $en = $this->read('en');

        $missing = array_values(array_diff($this->translatableStrings(), array_keys($dv)));

        $this->assertEquals([], $missing, 'Add these to lang/dv.json');
        $this->assertEquals(array_keys($dv), array_keys($en), 'lang/en.json and lang/dv.json should have the same keys');
    }

    #[Test]
    public function the_dhivehi_templates_keep_every_placeholder(): void
    {
        foreach ($this->read('dv') as $key => $translation) {
            preg_match_all('/:[a-z_]+/', $key, $expected);
            preg_match_all('/:[a-z_]+/', $translation, $actual);

            $this->assertEqualsCanonicalizing($expected[0], $actual[0], "Placeholders differ in the translation of [{$key}]");
        }
    }

    #[Test]
    public function it_translates_into_dhivehi(): void
    {
        app()->setLocale('dv');

        $this->assertEquals('އުފެއްދީ', ActivityTags::CREATED->label());
        $this->assertEquals('ހަރަކާތްތަކުގެ ލޮގް', __('Activity log'));
    }

    /**
     * @return array<string, string>
     */
    protected function read(string $locale): array
    {
        return json_decode(file_get_contents(__DIR__ . "/../../lang/{$locale}.json"), true);
    }

    /**
     * Literal strings passed to __() in src and resources, the description templates, and the
     * permission descriptions
     *
     * @return array<int, string>
     */
    protected function translatableStrings(): array
    {
        $strings = [];

        foreach (['src', 'resources/views'] as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . "/../../{$directory}", RecursiveDirectoryIterator::SKIP_DOTS));

            foreach ($iterator as $file) {
                foreach (file($file->getPathname()) as $line) {
                    if (preg_match('/^\s*(\*|\/\/|\/\*)/', $line)) {
                        continue;
                    }

                    preg_match_all("/(?<![\w>])__\(\s*'((?:[^'\\\\]|\\\\.)*)'/", $line, $matches);

                    foreach ($matches[1] as $string) {
                        $strings[] = stripslashes($string);
                    }
                }
            }
        }

        $describer = file_get_contents((new ReflectionClass(Describer::class))->getFileName());

        preg_match_all("/'(:(?:causer|tag)[^']*)'/", $describer, $templates);

        $provider = file_get_contents(__DIR__ . '/../../src/ActivitylogServiceProvider.php');

        preg_match_all("/'\w+_logs' => '([^']+)'/", $provider, $permissions);

        return array_values(array_unique([...$strings, ...$templates[1], ...$permissions[1], 'Activity logs']));
    }
}
