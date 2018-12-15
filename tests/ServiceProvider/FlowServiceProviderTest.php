<?php

namespace Tests\ServiceProvider;

use DarkGhostHunter\FlowSdk\Flow;
use Orchestra\Testbench\TestCase;

class FlowServiceProviderTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [
            'DarkGhostHunter\Laraflow\FlowHelpersServiceProvider',
            'DarkGhostHunter\Laraflow\FlowServiceProvider',
        ];
    }

    public function testGetsServiceProvider()
    {
        $flow = $this->app->make(Flow::class);

        $this->assertInstanceOf(Flow::class, $flow);
    }

    public function testHasConfig()
    {
        $config = $this->app->make('config')->get('flow');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('environment', $config);
        $this->assertArrayHasKey('credentials', $config);
        $this->assertArrayHasKey('apiKey', $config['credentials']);
        $this->assertArrayHasKey('secret', $config['credentials']);
        $this->assertArrayHasKey('returns', $config);
        $this->assertArrayHasKey('payment.urlReturn', $config['returns']);
        $this->assertArrayHasKey('card.url_return', $config['returns']);
        $this->assertArrayHasKey('webhooks', $config);
        $this->assertArrayHasKey('payment.urlConfirmation', $config['webhooks']);
        $this->assertArrayHasKey('refund.urlCallBack', $config['webhooks']);
        $this->assertArrayHasKey('plan.urlCallback', $config['webhooks']);
        $this->assertArrayHasKey('webhook-secret', $config);
        $this->assertArrayHasKey('adapter', $config);
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

        $this->app->make('config')->set(
            'flow.webhook-secret',
            $secret
        );

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

}
