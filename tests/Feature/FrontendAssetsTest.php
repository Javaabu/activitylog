<?php

namespace Javaabu\Activitylog\Tests\Feature;

use Javaabu\Activitylog\Tests\TestCase;
use Javaabu\Manifest\Manifest;
use PHPUnit\Framework\Attributes\Test;

class FrontendAssetsTest extends TestCase
{
    #[Test]
    public function it_registers_its_pages_and_components_with_manifest(): void
    {
        $package = app(Manifest::class)->package('activitylog');

        $this->assertEquals('@javaabu/activitylog', $package->importAlias());
        $this->assertEquals('js/Pages', $package->pagesPath());
        $this->assertArrayHasKey('@javaabu/forms', $package->npmDependencies());
        $this->assertArrayHasKey('react', $package->npmPeerDependencies());

        foreach (['Logs/Index', 'Logs/Show', 'Logs/Rollback'] as $page) {
            $this->assertFileExists(__DIR__ . "/../../resources/js/Pages/{$page}.tsx");
        }

        $this->artisan('manifest:list', ['package' => 'activitylog'])->assertSuccessful();
    }
}
