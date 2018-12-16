<?php

namespace DarkGhostHunter\Laraflow;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\ServiceProvider;
use DarkGhostHunter\FlowSdk\Flow;
use DarkGhostHunter\FlowSdk\Adapters\GuzzleAdapter;
use DarkGhostHunter\FlowSdk\Services\Coupon;
use DarkGhostHunter\FlowSdk\Services\Customer;
use DarkGhostHunter\FlowSdk\Services\Invoice;
use DarkGhostHunter\FlowSdk\Services\Payment;
use DarkGhostHunter\FlowSdk\Services\Plan;
use DarkGhostHunter\FlowSdk\Services\Refund;
use DarkGhostHunter\FlowSdk\Services\Settlement;
use DarkGhostHunter\FlowSdk\Services\Subscription;

class FlowServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->registerFlow();

        $this->registerServices();

    }

    /**
     * Register Flow
     *
     * @return void
     */
    protected function registerFlow()
    {
        $this->app->singleton(Flow::class, function ($app) {
            /** @var \Illuminate\Contracts\Foundation\Application $app */

            $flow = new Flow($app['log']);
            $flow->isProduction($app['config']['flow.environment'] === 'production');
            $flow->setCredentials($app['config']['flow.credentials']);
            $flow->setReturnUrls(array_filter($app['config']['flow.returns']));

            $webhooks = $app['config']['flow.webhooks'];

            if ($app['config']['flow.webhooks-defaults']) {
                /** @var \Illuminate\Contracts\Routing\UrlGenerator $url */
                $url = app(UrlGenerator::class);

                $webhooks = array_merge([
                    'payment.urlConfirmation'   => $url->to('flow/webhooks/payment'),
                    'refund.urlCallBack'        => $url->to('flow/webhooks/refund'),
                    'plan.urlCallback'          => $url->to('flow/webhooks/plan'),
                ], $webhooks);
            }

            $flow->setWebhookUrls(array_filter($webhooks));

            if ($secret = $app['config']['flow.webhook-secret']) $flow->setWebhookSecret($secret);

            $flow->setAdapter(($adapter = $app['config']['flow.adapter'])
                    ? $app->make($adapter, [$flow])
                    : new GuzzleAdapter($flow)
            );

            return $flow;
        });
    }

    /**
     * Registers Flow Services
     *
     * @return void
     */
    protected function registerServices()
    {
        $this->app->singleton(Coupon::class);
        $this->app->singleton(Customer::class);
        $this->app->singleton(Invoice::class);
        $this->app->singleton(Payment::class);
        $this->app->singleton(Plan::class);
        $this->app->singleton(Refund::class);
        $this->app->singleton(Settlement::class);
        $this->app->singleton(Subscription::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            Flow::class,
            Coupon::class,
            Customer::class,
            Invoice::class,
            Payment::class,
            Plan::class,
            Refund::class,
            Settlement::class,
            Subscription::class,
        ];
    }

}