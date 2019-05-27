<?php

namespace Tests\ServiceProvider;

use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class FlowMigrationsTest extends TestCase
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
    protected function setUp() : void
    {
        parent::setUp();

        $this->loadLaravelMigrations();

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->artisan('migrate', ['--database' => 'testing'])->run();
    }

    public function testUpdatesUsersTable()
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'flow_customer_id',
            'flow_card_brand',
            'flow_card_last_four',
        ]));
    }

    public function testCreatesSubscriptionsTable()
    {
        $this->assertTrue(Schema::hasTable('flow_subscriptions'));
        $this->assertTrue(Schema::hasColumns('flow_subscriptions', [
            'id',
            'flow_customer_id',
            'subscription_id',
            'plan_id',
            'coupon_id',
            'trial_starts_at',
            'trial_ends_at',
            'starts_at',
            'ends_at',
            'created_at',
            'updated_at',
        ]));
    }
}
