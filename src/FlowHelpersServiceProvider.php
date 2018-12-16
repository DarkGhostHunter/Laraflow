<?php

namespace DarkGhostHunter\Laraflow;

use Illuminate\Support\ServiceProvider;
use DarkGhostHunter\Laraflow\Console\Commands\SecretGenerateCommand;
use DarkGhostHunter\Laraflow\Http\Middleware\VerifyWebhookMiddleware;

class FlowHelpersServiceProvider extends ServiceProvider
{

    /**
     * Constant path for Webhooks
     */
    public const WEBHOOK_PATH = 'flow/webhooks';

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/flow.php', 'flow');
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/flow.php' => config_path('flow.php'),
        ]);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

//        $this->app['router']->pushMiddlewareToGroup('web', VerifyWebhookMiddleware::class);
        $this->app['router']->aliasMiddleware('flow-webhook', VerifyWebhookMiddleware::class);

        if ($this->app['config']['flow.webhooks-defaults']) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/webhooks.php');
        }

        if ($this->app->runningInConsole())
            $this->commands([SecretGenerateCommand::class]);
    }
}