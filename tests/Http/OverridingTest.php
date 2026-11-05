<?php

namespace Javaabu\Activitylog\Tests\Http;

use Illuminate\Support\Facades\Gate;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Tests\TestCase;
use Javaabu\Activitylog\Tests\TestSupport\Http\CustomLogsController;
use Javaabu\Activitylog\Tests\TestSupport\Policies\DenyAllPolicy;
use PHPUnit\Framework\Attributes\Test;

class OverridingTest extends TestCase
{
    use InteractsWithLogs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpLogs();
    }

    protected function defineRoutes($router): void
    {
        $router->middleware('web')->group(function ($router) {
            $router->activitylog();
            $router->activitylog(['prefix' => 'custom-logs', 'name' => 'custom-logs', 'controller' => CustomLogsController::class]);
        });
    }

    #[Test]
    public function the_controller_can_be_replaced_per_registration(): void
    {
        $this->actingAs($this->userWith(['view_all_logs']))
            ->getJson('/custom-logs')
            ->assertOk()
            ->assertJson(['custom' => true]);
    }

    #[Test]
    public function the_policy_can_be_replaced_with_gate_policy(): void
    {
        Gate::policy(Activity::class, DenyAllPolicy::class);

        $this->actingAs($this->userWith(['view_all_logs']))
            ->getJson('/logs')
            ->assertForbidden();
    }

    #[Test]
    public function the_default_permissions_are_registered(): void
    {
        foreach (['view_logs', 'view_all_logs', 'rollback_logs', 'export_logs'] as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission, 'guard_name' => 'web']);
            $this->assertDatabaseHas('permissions', ['name' => $permission, 'guard_name' => 'web_admin']);
        }
    }
}
