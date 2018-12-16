<?php

namespace Tests\ServiceProvider;

use DarkGhostHunter\FlowSdk\Flow;
use DarkGhostHunter\Laraflow\FlowHelpersServiceProvider;
use DarkGhostHunter\Laraflow\Http\Middleware\VerifyWebhookMiddleware;
use Orchestra\Testbench\TestCase;

class FlowHelperServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            'DarkGhostHunter\Laraflow\FlowHelpersServiceProvider',
        ];
    }

    public function testHasConfigFile()
    {
        $this->assertFileExists(
            $config = __DIR__ . '/../../config/flow.php'
        );

        $this->assertFileIsReadable($config);
    }

    public function testReadsConfig()
    {
        $config = $this->app->make('config')->get('flow');

        $file = include __DIR__ . '/../../config/flow.php';

        $this->assertIsArray($config);
        $this->assertEquals($file, $config);
    }

    public function testPublishesConfig()
    {
        $this->artisan('vendor:publish', [
            '--provider' => 'DarkGhostHunter\Laraflow\FlowHelpersServiceProvider'
        ]);

        $this->assertFileExists($this->app->configPath('flow.php'));
        $this->assertFileIsReadable($this->app->configPath('flow.php'));
        $this->assertTrue(unlink($this->app->configPath('flow.php')));
    }

    public function testGeneratesWebhookSecret()
    {
        touch($this->app->environmentFilePath());

        $this->assertFileExists($this->app->environmentFilePath());
        $this->assertFileIsReadable($this->app->environmentFilePath());

        $this->artisan('webhook-secret:generate');

        $this->assertRegExp(
            '/^FLOW_WEBHOOK_SECRET=\w+$/m',
            file_get_contents($this->app->environmentFilePath())
        );

        $this->assertTrue(unlink($this->app->environmentFilePath()));
    }

    public function testReplacesWebhookSecret()
    {
        file_put_contents(
            $this->app->environmentFilePath(),
            'FLOW_WEBHOOK_SECRET=' . $secret = bin2hex(random_bytes(16))
        );

        $this->assertFileExists($this->app->environmentFilePath());
        $this->assertIsReadable($this->app->environmentFilePath());

        $this->app->make('config')->set('flow.webhook-secret', $secret);

        $this->artisan('webhook-secret:generate');

        $this->assertNotContains(
            $secret,
            file_get_contents($this->app->environmentFilePath())
        );

        $this->assertRegExp(
            '/^FLOW_WEBHOOK_SECRET=\w+$/m',
            file_get_contents($this->app->environmentFilePath())
        );

        $this->assertTrue(unlink($this->app->environmentFilePath()));
    }

    public function testCommandShowsKey()
    {
        $this->artisan('webhook-secret:generate', [
            '--show' => true
        ])->expectsOutput(\Mockery::pattern('/^\w+$/m'));
    }

    public function testHasMigrations()
    {
        $path = __DIR__ . '/../../database/migrations';

        $migrations = $this->app->make('migrator')->getMigrationFiles($path);

        foreach (array_slice(scandir($path), 2) as $file) {
            $this->assertTrue(in_array("$path/$file", $migrations));
        }
    }

    public function testRegistersMiddleware()
    {
        /** @var \Illuminate\Routing\Router $router */
        $middlewares = $this->app->make('router')->getMiddleware();

        $this->assertArrayHasKey('flow-webhook', $middlewares);
        $this->assertEquals($middlewares['flow-webhook'], VerifyWebhookMiddleware::class);
    }

    public function testRegisterRoutes()
    {
        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app->make('router');

        $routes = $router->getRoutes()->get('POST');

        $this->assertArrayHasKey(FlowHelpersServiceProvider::WEBHOOK_PATH . '/payment', $routes);
        $this->assertArrayHasKey(FlowHelpersServiceProvider::WEBHOOK_PATH . '/refund', $routes);
        $this->assertArrayHasKey(FlowHelpersServiceProvider::WEBHOOK_PATH . '/plan', $routes);

        $this->assertTrue($router->has('flow.webhook.payment'));
        $this->assertTrue($router->has('flow.webhook.refund'));
        $this->assertTrue($router->has('flow.webhook.plan'));
    }


}
