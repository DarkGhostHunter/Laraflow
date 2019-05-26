<?php

namespace DarkGhostHunter\Laraflow;

use Illuminate\Support\ServiceProvider;
use DarkGhostHunter\Laraflow\Console\Commands\SecretGenerateCommand;
use DarkGhostHunter\Laraflow\Http\Middleware\VerifyWebhookMiddleware;

class FlowHelpersServiceProvider extends ServiceProvider
{

    /**
     * Constant path for Webhooks
     *
     * @const string
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
        // Set the configuration file for publishing
        $this->publishes([
            __DIR__.'/../config/flow.php' => config_path('flow.php'),
        ]);

        // Load the migrations for subscriptions
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register the middleware to protect the application in the Webhooks routes
        $this->app['router']->aliasMiddleware('flow-webhook', VerifyWebhookMiddleware::class);

        // Load the Webhooks routes
        if ($this->app['config']['flow.webhooks-defaults']) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/webhooks.php');
        }

        // If we are running in Console mode, register the commands
        if ($this->app->runningInConsole()) {
            $this->commands([SecretGenerateCommand::class]);
        }
    }
}