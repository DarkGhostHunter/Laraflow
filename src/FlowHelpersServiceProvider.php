<?php

namespace DarkGhostHunter\Laraflow;

use Illuminate\Support\ServiceProvider;
use DarkGhostHunter\Laraflow\Console\Commands\SecretGenerateCommand;
use DarkGhostHunter\Laraflow\Http\Middleware\VerifyWebhookMiddleware;

class FlowHelpersServiceProvider extends ServiceProvider
{

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

        if ($this->app->runningInConsole()) {
            $this->commands([
                SecretGenerateCommand::class,
            ]);
        }

        $router = $this->app['router'];

        $router->pushMiddlewareToGroup('web', VerifyWebhookMiddleware::class);
        $router->aliasMiddleware('flow-webhook', VerifyWebhookMiddleware::class);
    }
}