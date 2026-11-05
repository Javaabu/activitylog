<?php

namespace Javaabu\Activitylog\Tests\Http;

use Javaabu\Activitylog\Tests\TestCase;
use Javaabu\Activitylog\Tests\TestSupport\Http\CustomLogsController;
use PHPUnit\Framework\Attributes\Test;

class ControllerFromConfigTest extends TestCase
{
    use InteractsWithLogs;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('activitylog.controllers.logs', CustomLogsController::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpLogs();
    }

    #[Test]
    public function the_controller_can_be_replaced_in_config(): void
    {
        $this->actingAs($this->userWith(['view_all_logs']))
            ->getJson('/logs')
            ->assertJson(['custom' => true]);
    }
}
