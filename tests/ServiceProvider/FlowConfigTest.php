<?php

namespace Tests\ServiceProvider;

use DarkGhostHunter\FlowSdk\Contracts\AdapterInterface;
use DarkGhostHunter\FlowSdk\Flow;
use Orchestra\Testbench\TestCase;

class FlowConfigTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [
            'DarkGhostHunter\Laraflow\FlowHelpersServiceProvider',
            'DarkGhostHunter\Laraflow\FlowServiceProvider',
        ];
    }

    public function setUp() : void
    {
        copy(
            __DIR__ . '/../../config/flow.php',
            $config = __DIR__ . '/../../vendor/orchestra/testbench-core/laravel/config/flow.php'
        );

        // read the entire string
        $str = file_get_contents($config);

        // replace something in the file string
        $str = str_replace("env('FLOW_API_KEY')", "'theFlowApiKey'", $str);
        $str = str_replace("env('FLOW_ENV', 'sandbox')", "'production'", $str);
        $str = str_replace("env('FLOW_WEBHOOK_SECRET')", "'123456789'", $str);

        file_put_contents($config, $str);

        parent::setUp();
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('flow.adapter', 'custom.adapter');

        $app->singleton('custom.adapter', function () {
            return \Mockery::instanceMock(AdapterInterface::class);
        });
    }

    public function testReadsConfigAndEnvironmentVariable()
    {
        $config = $this->app->make('config');

        $this->assertEquals('production', $config->get('flow.environment'));
        $this->assertEquals('theFlowApiKey', $config->get('flow.credentials.apiKey'));

    }

    public function testReceivesWebhookConfig()
    {
        /** @var \Illuminate\Config\Repository $config */
        $config = $this->app->make('config');

        $this->assertInstanceOf(Flow::class, $this->app->make(Flow::class));
        $this->assertEquals('123456789', $this->app->make(Flow::class)->getWebhookSecret());
    }

    public function testReceivesAdapter()
    {
        /** @var Flow $flow */
        $flow = $this->app->make(Flow::class);

        $this->assertInstanceOf(AdapterInterface::class, $flow->getAdapter());
    }

    public function tearDown() : void
    {
        unlink(__DIR__ . '/../../vendor/orchestra/testbench-core/laravel/config/flow.php');
        parent::tearDown();
    }


}
