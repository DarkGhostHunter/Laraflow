<?php

namespace Tests\Traits;

trait HasTestUser
{
    /** @var string */
    static protected $passwordHash;

    /** @var \Illuminate\Foundation\Auth\User|\DarkGhostHunter\Laraflow\Billable */
    protected $model;

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
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'FlowCoupon' => 'DarkGhostHunter\Laraflow\Facades\FlowCoupon',
            'FlowCustomer' => 'DarkGhostHunter\Laraflow\Facades\FlowCustomer',
            'FlowInvoice' => 'DarkGhostHunter\Laraflow\Facades\FlowInvoice',
            'FlowPayment' => 'DarkGhostHunter\Laraflow\Facades\FlowPayment',
            'FlowPlan' => 'DarkGhostHunter\Laraflow\Facades\FlowPlan',
            'FlowRefund' => 'DarkGhostHunter\Laraflow\Facades\FlowRefund',
            'FlowSettlement' => 'DarkGhostHunter\Laraflow\Facades\FlowSettlement',
            'FlowSubscription' => 'DarkGhostHunter\Laraflow\Facades\FlowSubscription',
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

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->artisan('migrate', ['--database' => 'testing'])->run();

        $this->createUser();
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
    }

}