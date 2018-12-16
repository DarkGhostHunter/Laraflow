<?php

namespace Tests\Models;

use DarkGhostHunter\Laraflow\Models\FlowSubscription;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;

class FlowSubscriptionTest extends TestCase
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
            'DarkGhostHunter\Laraflow\FlowHelpersServiceProvider',
        ];
    }

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadLaravelMigrations();

        $this->artisan('migrate', ['--database' => 'testing'])->run();
    }

    public function testHasCoupon()
    {
        $subscription = FlowSubscription::make([
            'coupon_id' => 1234
        ]);

        $this->assertTrue($subscription->hasCoupon());
    }

    public function testFillsAttributes()
    {
        $subscription = FlowSubscription::create([
            'id' => $id = 'IGNORE THIS',
            'flow_customer_id' => $flow_customer_id = 'cus_abcd1234',
            'subscription_id' => $subscription_id = 'sus_abcd1234',
            'plan_id' => $plan_id = 'testPlan',
            'coupon_id' => $coupon_id = 1234,
            'trial_starts_at' => $trial_starts_at = '2018-01-01',
            'trial_ends_at' => $trial_ends_at = '2018-01-03',
            'starts_at' => $starts_at = '2018-01-04',
            'ends_at' => $ends_at = '2018-01-14',
        ]);

        $this->assertNotEquals($id, $subscription->id);

        $this->assertEquals($flow_customer_id, $subscription->flow_customer_id);
        $this->assertEquals($subscription_id, $subscription->subscription_id);
        $this->assertEquals($plan_id, $subscription->plan_id);
        $this->assertEquals($coupon_id, $subscription->coupon_id);

        $this->assertInstanceOf(Carbon::class, $subscription->trial_starts_at);
        $this->assertInstanceOf(Carbon::class, $subscription->trial_ends_at);
        $this->assertInstanceOf(Carbon::class, $subscription->starts_at);
        $this->assertInstanceOf(Carbon::class, $subscription->ends_at);

        $this->assertEquals($trial_starts_at, $subscription->trial_starts_at->format('Y-m-d'));
        $this->assertEquals($trial_ends_at, $subscription->trial_ends_at->format('Y-m-d'));
        $this->assertEquals($starts_at, $subscription->starts_at->format('Y-m-d'));
        $this->assertEquals($ends_at, $subscription->ends_at->format('Y-m-d'));
    }

    public function testExceptionOnUnfilledAttributes()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        FlowSubscription::create([
            'id' => 'IGNORE THIS',
            'coupon_id' => null,
            'trial_starts_at' => null,
            'trial_ends_at' => null,
            'starts_at' => null,
            'ends_at' => null,
        ]);
    }
}
