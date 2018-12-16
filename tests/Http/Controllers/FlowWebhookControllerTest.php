<?php

namespace Tests\Http\Controllers;

use DarkGhostHunter\FlowSdk\Resources\BasicResource;
use DarkGhostHunter\FlowSdk\Services\Payment;
use DarkGhostHunter\FlowSdk\Services\Refund;
use DarkGhostHunter\Laraflow\Events\PaymentResolvedEvent;
use DarkGhostHunter\Laraflow\Events\RefundResolvedEvent;
use DarkGhostHunter\Laraflow\Events\PlanPaidEvent;
use DarkGhostHunter\Laraflow\FlowHelpersServiceProvider;
use Orchestra\Testbench\TestCase;

class FlowWebhookControllerTest extends TestCase
{

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            'DarkGhostHunter\Laraflow\FlowServiceProvider',
            'DarkGhostHunter\Laraflow\FlowHelpersServiceProvider',
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'FlowPayment' => 'DarkGhostHunter\Laraflow\Facades\FlowPayment',
            'FlowRefund' => 'DarkGhostHunter\Laraflow\Facades\FlowRefund',
        ];
    }

    public function testFiresPaymentResolvedEvent()
    {
        $this->expectsEvents(PaymentResolvedEvent::class);

        $mockPayment = \Mockery::mock(Payment::class);

        $mockPayment->shouldReceive('get')
            ->with($secret = bin2hex(random_bytes(20)))
            ->andReturn(new BasicResource(['foo' => 'bar']));

        $this->app->instance(Payment::class, $mockPayment);

        $response = $this->post(FlowHelpersServiceProvider::WEBHOOK_PATH . '/payment', [
            'token' => $secret
        ]);

        $response->assertOk();
        $this->assertEmpty($response->content());
    }

    public function testFiresRefundResolvedEvent()
    {
        $this->expectsEvents(RefundResolvedEvent::class);

        $mockRefund = \Mockery::mock(Refund::class);

        $mockRefund->shouldReceive('get')
            ->with($secret = bin2hex(random_bytes(20)))
            ->andReturn(new BasicResource(['foo' => 'bar']));

        $this->app->instance(Refund::class, $mockRefund);

        $response = $this->post(FlowHelpersServiceProvider::WEBHOOK_PATH . '/refund', [
            'token' => $secret
        ]);

        $response->assertOk();
        $this->assertEmpty($response->content());
    }

    public function testFiresSubscriptionPaidEvent()
    {
        $this->expectsEvents(PlanPaidEvent::class);

        $mockPayment = \Mockery::mock(Payment::class);

        $mockPayment->shouldReceive('get')
            ->with($secret = bin2hex(random_bytes(20)))
            ->andReturn(new BasicResource(['foo' => 'bar']));

        $this->app->instance(Payment::class, $mockPayment);

        $response = $this->post(FlowHelpersServiceProvider::WEBHOOK_PATH . '/plan', [
            'token' => $secret
        ]);

        $response->assertOk();
        $this->assertEmpty($response->content());
    }
}
