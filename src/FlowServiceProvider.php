<?php

namespace DarkGhostHunter\Laraflow;

use DarkGhostHunter\FlowSdk\Flow;
use DarkGhostHunter\FlowSdk\Services\{Coupon, Customer, Invoice, Payment, Plan, Refund, Settlement, Subscription};
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class FlowServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * All of the container singletons that should be registered.
     *
     * @var array
     */
    public $singletons = [
        Coupon::class => Coupon::class,
        Customer::class => Customer::class,
        Invoice::class => Invoice::class,
        Payment::class => Payment::class,
        Plan::class => Plan::class,
        Refund::class => Refund::class,
        Settlement::class => Settlement::class,
        Subscription::class => Subscription::class,
    ];

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
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