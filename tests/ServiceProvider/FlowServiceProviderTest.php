<?php

namespace Tests\ServiceProvider;

use DarkGhostHunter\FlowSdk\Flow;
use DarkGhostHunter\FlowSdk\Services\Coupon;
use DarkGhostHunter\FlowSdk\Services\Customer;
use DarkGhostHunter\FlowSdk\Services\Invoice;
use DarkGhostHunter\FlowSdk\Services\Payment;
use DarkGhostHunter\FlowSdk\Services\Plan;
use DarkGhostHunter\FlowSdk\Services\Refund;
use DarkGhostHunter\FlowSdk\Services\Settlement;
use DarkGhostHunter\FlowSdk\Services\Subscription;
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

    public function testRegisters()
    {
        $this->assertInstanceOf(Flow::class, $this->app->make(Flow::class));
        $this->assertInstanceOf(Coupon::class, $this->app->make(Coupon::class));
        $this->assertInstanceOf(Customer::class, $this->app->make(Customer::class));
        $this->assertInstanceOf(Invoice::class, $this->app->make(Invoice::class));
        $this->assertInstanceOf(Payment::class, $this->app->make(Payment::class));
        $this->assertInstanceOf(Plan::class, $this->app->make(Plan::class));
        $this->assertInstanceOf(Refund::class, $this->app->make(Refund::class));
        $this->assertInstanceOf(Settlement::class, $this->app->make(Settlement::class));
        $this->assertInstanceOf(Subscription::class, $this->app->make(Subscription::class));
    }

}
