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
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('flow.migrations', true);
    }

    public function testUpdatesUsersTable()
    {
        $this->loadLaravelMigrations();
        $this->artisan('migrate', ['--database' => 'testing'])->run();

        $this->assertTrue(Schema::hasColumns('users', [
            'flow_customer_id',
            'flow_card_brand',
            'flow_card_last_four',
        ]));
    }

    public function testCreatesSubscriptionsTable()
    {
        $this->loadLaravelMigrations();
        $this->artisan('migrate', ['--database' => 'testing'])->run();

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
