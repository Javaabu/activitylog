<?php

namespace Javaabu\Activitylog\Tests\Unit;

use Javaabu\Activitylog\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class RtlTest extends TestCase
{
    #[Test]
    public function the_views_use_only_logical_directions(): void
    {
        $physical = '/(?<![\w-])(ml|mr|pl|pr|left|right|text-left|text-right|rounded-l|rounded-r|border-l|border-r|float-left|float-right)-?[\w\[]*/';

        foreach (['resources/views', 'resources/js'] as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . "/../../{$directory}", RecursiveDirectoryIterator::SKIP_DOTS));

            foreach ($iterator as $file) {
                preg_match_all('/class(?:Name)?="([^"]*)"/', file_get_contents($file->getPathname()), $classes);

                foreach ($classes[1] as $class_list) {
                    $this->assertDoesNotMatchRegularExpression($physical, $class_list, "Use logical utilities (ms-, pe-, start-) in {$file->getFilename()}");
                }
            }
        }
    }
}
