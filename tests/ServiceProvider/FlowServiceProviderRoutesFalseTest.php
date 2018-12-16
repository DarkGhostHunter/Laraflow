<?php

namespace Tests\ServiceProvider;

use DarkGhostHunter\FlowSdk\Flow;
use DarkGhostHunter\Laraflow\FlowHelpersServiceProvider;
use Orchestra\Testbench\TestCase;

class FlowHelperServiceProviderRoutesFalseTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            'DarkGhostHunter\Laraflow\FlowHelpersServiceProvider',
            'DarkGhostHunter\Laraflow\FlowServiceProvider',
        ];
    }
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('flow.webhooks-defaults', false);
    }

    public function testDoesntRegisterRoutes()
    {
        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app->make('router');

        $routes = $router->getRoutes()->get('POST');

        $this->assertArrayNotHasKey(FlowHelpersServiceProvider::WEBHOOK_PATH . '/payment', $routes);
        $this->assertArrayNotHasKey(FlowHelpersServiceProvider::WEBHOOK_PATH . '/refund', $routes);
        $this->assertArrayNotHasKey(FlowHelpersServiceProvider::WEBHOOK_PATH . '/plan', $routes);

        $this->assertFalse($router->has('flow.webhook.payment'));
        $this->assertFalse($router->has('flow.webhook.refund'));
        $this->assertFalse($router->has('flow.webhook.plan'));

    }


}
