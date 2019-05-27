<?php

namespace DarkGhostHunter\Laraflow;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use DarkGhostHunter\FlowSdk\Flow;
use DarkGhostHunter\FlowSdk\Services\{Coupon, Customer, Invoice, Payment, Plan, Refund, Settlement, Subscription};

class FlowServiceProvider extends ServiceProvider implements DeferrableProvider
{
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
        // To avoid cluttering up the Service Container with a giant Closure, we will use
        // the FlowFactory class. It will automatically wire up the application services
        // the Flow instance needs, along with its configuration, using just one line.
        $this->app->singleton(Flow::class, static function ($app) {
            /** @var \Illuminate\Contracts\Foundation\Application $app */
            return $app->make(FlowFactory::class)->configure();
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