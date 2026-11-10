<?php

namespace Javaabu\Activitylog\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Javaabu\Activitylog\Tests\TestCase;
use Javaabu\Manifest\Enums\NpmScopes;
use Javaabu\Manifest\Manifest;
use PHPUnit\Framework\Attributes\Test;

class FrontendAssetsTest extends TestCase
{
    protected function tearDown(): void
    {
        File::delete(base_path('package.json'));

        parent::tearDown();
    }

    /**
     * manifest:check's rows for an app with this package.json, keyed by npm package
     *
     * @param  array<string, string>  $dependencies
     * @return array<string, array<string, mixed>>
     */
    protected function check(array $dependencies): array
    {
        File::put(base_path('package.json'), json_encode(['devDependencies' => $dependencies]));

        Artisan::call('manifest:check', ['--json' => true]);

        return collect(json_decode(Artisan::output(), true))->keyBy('name')->all();
    }

    #[Test]
    public function it_registers_its_pages_and_components_with_manifest(): void
    {
        $package = app(Manifest::class)->package('activitylog');

        $this->assertEquals('@javaabu/activitylog', $package->importAlias());
        $this->assertEquals('js/Pages', $package->pagesPath());
        $this->assertArrayHasKey('@javaabu/forms', $package->npmDependencies(NpmScopes::PAGES));
        $this->assertArrayHasKey('react', $package->npmPeerDependencies(NpmScopes::PAGES));

        foreach (['Logs/Index', 'Logs/Show', 'Logs/Rollback'] as $page) {
            $this->assertFileExists(__DIR__ . "/../../resources/js/Pages/{$page}.tsx");
        }

        $this->artisan('manifest:list', ['package' => 'activitylog'])->assertSuccessful();
    }

    #[Test]
    public function it_only_needs_npm_packages_for_its_pages(): void
    {
        $package = app(Manifest::class)->package('activitylog');

        $this->assertEmpty($package->npmDependencies(NpmScopes::ASSETS));
        $this->assertEmpty($package->npmPeerDependencies(NpmScopes::ASSETS));
    }

    #[Test]
    public function it_doesnt_ask_blade_apps_for_react_packages(): void
    {
        $rows = $this->check(['vite' => '^8.0', 'laravel-vite-plugin' => '^3.0']);

        $needed_by_activitylog = array_filter($rows, fn (array $row) => in_array('activitylog', $row['needed_by'], true));

        // Other packages (javaabu/forms, ...) may still ask for their own; activitylog asks for nothing
        $this->assertEmpty($needed_by_activitylog);
    }

    #[Test]
    public function it_asks_inertia_apps_for_what_its_pages_need(): void
    {
        $rows = $this->check(['@inertiajs/react' => '^2.0', 'react' => '^19.0']);

        foreach (['@javaabu/forms', '@javaabu/onu', 'react', 'react-dom'] as $name) {
            $this->assertArrayHasKey($name, $rows);
            $this->assertContains('activitylog', $rows[$name]['needed_by']);
            $this->assertContains('pages', $rows[$name]['scopes']);
        }
    }
}
