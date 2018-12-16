<?php

namespace Tests\ServiceProvider;

use Orchestra\Testbench\TestCase;

class FlowFacadesTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [
            'DarkGhostHunter\Laraflow\FlowServiceProvider',
            'DarkGhostHunter\Laraflow\FlowHelpersServiceProvider',
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'FlowPayment' => 'DarkGhostHunter\Laraflow\Facades\FlowPayment',
            'FlowCoupon' => 'DarkGhostHunter\Laraflow\Facades\FlowCoupon',
            'FlowCustomer' => 'DarkGhostHunter\Laraflow\Facades\FlowCustomer',
            'FlowInvoice' => 'DarkGhostHunter\Laraflow\Facades\FlowInvoice',
            'FlowPlan' => 'DarkGhostHunter\Laraflow\Facades\FlowPlan',
            'FlowRefund' => 'DarkGhostHunter\Laraflow\Facades\FlowRefund',
            'FlowSettlement' => 'DarkGhostHunter\Laraflow\Facades\FlowSettlement',
            'FlowSubscription' => 'DarkGhostHunter\Laraflow\Facades\FlowSubscription',
        ];
    }

    public function testAssertFacades()
    {
        $this->assertInstanceOf(
            \DarkGhostHunter\FlowSdk\Services\Payment::class, \FlowPayment::getFacadeRoot()
        );

        $this->assertInstanceOf(
            \DarkGhostHunter\FlowSdk\Services\Coupon::class, \FlowCoupon::getFacadeRoot()
        );

        $this->assertInstanceOf(
            \DarkGhostHunter\FlowSdk\Services\Customer::class, \FlowCustomer::getFacadeRoot()
        );

        $this->assertInstanceOf(
            \DarkGhostHunter\FlowSdk\Services\Invoice::class, \FlowInvoice::getFacadeRoot()
        );

        $this->assertInstanceOf(
            \DarkGhostHunter\FlowSdk\Services\Plan::class, \FlowPlan::getFacadeRoot()
        );

        $this->assertInstanceOf(
            \DarkGhostHunter\FlowSdk\Services\Refund::class, \FlowRefund::getFacadeRoot()
        );

        $this->assertInstanceOf(
            \DarkGhostHunter\FlowSdk\Services\Settlement::class, \FlowSettlement::getFacadeRoot()
        );

        $this->assertInstanceOf(
            \DarkGhostHunter\FlowSdk\Services\Subscription::class, \FlowSubscription::getFacadeRoot()
        );
    }


}
