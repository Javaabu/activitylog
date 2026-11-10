<?php

namespace Javaabu\Activitylog\Tests\Unit;

use Javaabu\Activitylog\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DocsTest extends TestCase
{
    #[Test]
    public function llms_txt_lists_every_docs_page(): void
    {
        $root = __DIR__ . '/../..';
        $llms = file_get_contents("{$root}/llms.txt");

        preg_match_all('/\]\((docs\/[^)]+\.md)\)/', $llms, $linked);

        foreach ($linked[1] as $page) {
            $this->assertFileExists("{$root}/{$page}");
        }

        foreach ($this->pages() as $page) {
            $this->assertContains($page, $linked[1], "Link {$page} from llms.txt");
        }
    }

    #[Test]
    public function links_between_docs_pages_resolve(): void
    {
        $root = __DIR__ . '/../..';

        foreach ($this->pages() as $page) {
            preg_match_all('/\]\(((?!https?:|mailto:|#)[^)#]+\.md)(#[^)]*)?\)/', file_get_contents("{$root}/{$page}"), $links);

            foreach ($links[1] as $link) {
                $this->assertFileExists(dirname("{$root}/{$page}") . "/{$link}", "Broken link to {$link} in {$page}");
            }
        }
    }

    /**
     * @return array<int, string>
     */
    protected function pages(): array
    {
        $root = realpath(__DIR__ . '/../..');
        $pages = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/docs", RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            if ($file->getExtension() === 'md') {
                $pages[] = str_replace("{$root}/", '', $file->getPathname());
            }
        }

        sort($pages);

        return $pages;
    }
}
