<?php

namespace Tests\Observers;

use DarkGhostHunter\FlowSdk\Resources\CustomerResource;
use DarkGhostHunter\FlowSdk\Resources\SubscriptionResource;
use DarkGhostHunter\Laraflow\Billable;
use DarkGhostHunter\Laraflow\Models\FlowSubscription;
use DarkGhostHunter\Laraflow\Multisubscribable;
use DarkGhostHunter\Laraflow\Subscribable;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Hash;
use Orchestra\Testbench\TestCase;

class SubscriptionEventsTest extends TestCase
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

    protected function createUser()
    {
        $this->model = new class extends User {
            protected $table = 'users';
            protected $syncOnCreate = true;
            use Billable;
            protected $guarded = ['id'];
        };

        $this->model->create([
            'name' => 'Orchestra',
            'email' => 'hello@orchestraplatform.com',
            'password' => self::$passwordHash ?? self::$passwordHash = Hash::make('secret'),
        ]);

        $this->user = $this->model->first();
    }

    protected function createSubscribableUser()
    {
        $this->model = new class extends User {
            protected $table = 'users';
            use Billable, Subscribable;
            protected $guarded = ['id'];
        };

        $this->model->create([
            'name' => 'Orchestra',
            'email' => 'hello@orchestraplatform.com',
            'password' => self::$passwordHash ?? self::$passwordHash = Hash::make('secret'),
            'flow_customer_id' => 'cus_abcd1234'
        ]);

        $this->user = $this->model->first();
    }

    protected function createMultisubscribableUser()
    {
        $this->model = new class extends User {
            protected $table = 'users';
            use Billable, Multisubscribable;
            protected $guarded = ['id'];
        };

        $this->model->create([
            'name' => 'Orchestra',
            'email' => 'hello@orchestraplatform.com',
            'password' => self::$passwordHash ?? self::$passwordHash = Hash::make('secret'),
            'flow_customer_id' => 'cus_abcd1234'
        ]);

        $this->user = $this->model->first();
    }

    protected function updateUserWithSubscription()
    {
        $this->user->flowSubscription()->create([
            'subscription_id' => 'sus_abcd1234',
            'plan_id' => 'testPlan',
            'coupon_id' => null,
            'trial_starts_at' => '2018-01-01',
            'trial_ends_at' => '2018-01-03',
            'starts_at' => '2018-01-04',
            'ends_at' => '2018-01-14',
        ]);
    }

    protected function updateUserWithSubscriptions()
    {
        $this->user->flowSubscriptions()->createMany([
            [
                'subscription_id' => 'sus_abcd1234',
                'plan_id' => 'testPlan1',
                'coupon_id' => null,
                'trial_starts_at' => '2018-01-01',
                'trial_ends_at' => '2018-01-03',
                'starts_at' => '2018-01-04',
                'ends_at' => '2018-01-14',
            ],
            [
                'subscription_id' => 'sus_abcd1235',
                'plan_id' => 'testPlan2',
                'coupon_id' => null,
                'trial_starts_at' => '2018-01-01',
                'trial_ends_at' => '2018-01-03',
                'starts_at' => '2018-01-04',
                'ends_at' => '2018-01-14',
            ],
        ]);
    }

    public function testAutomaticallyDeletesCustomerSubscription()
    {
        $this->createSubscribableUser();
        $this->updateUserWithSubscription();

        \FlowCustomer::shouldReceive('delete')
            ->once()
            ->with($this->user->flow_customer_id)
            ->andReturnUsing(function ($id) {
                return new CustomerResource([
                    'customerId' => $id,
                    'status' => 3
                ]);
            });

        \FlowSubscription::shouldReceive('cancel')
            ->once()
            ->with('sus_abcd1234', TRUE)
            ->andReturnUsing(function ($id) {
                return new SubscriptionResource([
                    'customerId' => $id,
                    'planId' => 'testPlan',
                    'subscriptionId' => 'sus_abcd1234',
                    'status' => 0
                ]);
            });

        $this->assertTrue(
            FlowSubscription::where('flow_customer_id', $this->user->flow_customer_id)->exists()
        );

        $this->user->delete();

        $this->assertFalse(
            $this->model->where('flow_customer_id', $this->user->flow_customer_id)->exists()
        );

        $this->assertFalse(
            FlowSubscription::where('flow_customer_id', $this->user->flow_customer_id)->exists()
        );

    }

    public function testAutomaticallyDeletesCustomerSubscriptions()
    {
        $this->createMultisubscribableUser();
        $this->updateUserWithSubscriptions();

        \FlowCustomer::shouldReceive('delete')
            ->once()
            ->with($this->user->flow_customer_id)
            ->andReturnUsing(function ($id) {
                return new CustomerResource([
                    'customerId' => $id,
                    'status' => 3
                ]);
            });

        \FlowSubscription::shouldReceive('cancel')
            ->twice()
            ->with(\Mockery::type('string'), true)
            ->andReturnUsing(function ($id) {
                return new SubscriptionResource([
                    'customerId' => $id,
                    'status' => 0
                ]);
            });

        $this->assertTrue(
            FlowSubscription::where('flow_customer_id', $this->user->flow_customer_id)->exists()
        );

        $this->user->delete();

        $this->assertFalse(
            $this->model->where('flow_customer_id', $this->user->flow_customer_id)->exists()
        );

        $this->assertFalse(
            FlowSubscription::where('flow_customer_id', $this->user->flow_customer_id)->exists()
        );

    }

    public function testSyncCreateOnFlow()
    {
        \FlowCustomer::shouldReceive('create')
            ->once()
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function ($array) {
                return new CustomerResource(array_merge(
                    $array,
                    ['customerId' => 'cus_abcd1234']
                ));
            });

        $this->createUser();

        $this->assertEquals(
            'cus_abcd1234', $this->user->flow_customer_id
        );
    }

}