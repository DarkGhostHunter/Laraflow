<?php

namespace Tests\Traits;

use DarkGhostHunter\FlowSdk\Resources\CustomerResource;
use DarkGhostHunter\FlowSdk\Responses\BasicResponse;
use DarkGhostHunter\Laraflow\Billable;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Orchestra\Testbench\TestCase;

class BillableTest extends TestCase
{

    /** @var string */
    static protected $passwordHash;

    /** @var \Illuminate\Foundation\Auth\User|\DarkGhostHunter\Laraflow\Billable */
    protected $model;

    /** @var \Illuminate\Foundation\Auth\User|\DarkGhostHunter\Laraflow\Billable */
    protected $user;

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
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'FlowCustomer' => 'DarkGhostHunter\Laraflow\Facades\FlowCustomer',
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

    protected function createUser()
    {
        $this->model = new class extends User {
            protected $table = 'users';
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

    protected function updateUserWithCustomer()
    {
        DB::table('users')->where('id', 1)->update([
            'flow_customer_id' => 'cus_abcd1234',
            'flow_card_brand' => null,
            'flow_card_last_four' => null,
        ]);

        $this->user = $this->model->first();
    }

    protected function updateUserWithCustomerAndCard()
    {
        DB::table('users')->where('id', 1)->update([
            'flow_customer_id' => 'cus_abcd1234',
            'flow_card_brand' => 'visa',
            'flow_card_last_four' => '1234',
        ]);

        $this->user = $this->model->first();
    }

    public function testCreateCustomer()
    {
        \FlowCustomer::shouldReceive('create')
            ->once()
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function ($array) {
                return new CustomerResource(array_merge([
                    'customerId' => 'cus_abcd1234',
                    'creditCardType' => 'visa',
                    'last4CardDigits' => '1234',
                ], $array));
            });

        $this->doesntExpectEvents('eloquent.updated: *');

        $resource = $this->user->createCustomer();

        $this->assertInstanceOf(CustomerResource::class, $resource);

        $this->assertEquals('Orchestra', $resource->name);
        $this->assertEquals('hello@orchestraplatform.com', $resource->email);
        $this->assertEquals('users|id|1', $resource->externalId);

        $this->assertEquals('cus_abcd1234', $this->user->flow_customer_id);
        $this->assertEquals('visa', $this->user->flow_card_brand);
        $this->assertEquals('1234', $this->user->flow_card_last_four);
    }

    public function testDoesntCreateCustomer()
    {
        $this->updateUserWithCustomer();

        $resource = $this->user->createCustomer();

        $this->assertFalse($resource);
    }

    public function testAutomaticallyUpdatesCustomer()
    {
        $this->updateUserWithCustomer();

        \FlowCustomer::shouldReceive('update')
            ->once()
            ->with(
                \Mockery::type('string'),
                \Mockery::type('array')
            )
            ->andReturnUsing(function ($id, $array) {
                return new CustomerResource(array_merge([
                    'customerId' => $id,
                    'creditCardType' => null,
                    'last4CardDigits' => null,
                ], $array));
            });

        $this->user->update([
            'name' => 'newName',
            'email' => 'newEmail@email.com'
        ]);

        $this->assertEquals('newName', $this->user->name);
        $this->assertEquals('newEmail@email.com', $this->user->email);

        $this->assertEquals('cus_abcd1234', $this->user->flow_customer_id);
        $this->assertNull($this->user->flow_card_brand);
        $this->assertNull($this->user->flow_card_last_four);

    }

    public function testForceUpdateCustomer()
    {
        $this->updateUserWithCustomer();

        \FlowCustomer::shouldReceive('update')
            ->once()
            ->with(
                \Mockery::type('string'),
                \Mockery::type('array')
            )
            ->andReturnUsing(function ($id, $array) {
                return new CustomerResource(array_merge([
                    'customerId' => $id,
                    'creditCardType' => null,
                    'last4CardDigits' => null,
                ], $array));
            });

        $customer = $this->user->forceUpdateCustomer([
            'name' => 'newName',
            'email' => 'newEmail@email.com'
        ]);

        $this->assertInstanceOf(CustomerResource::class, $customer);

        $this->assertEquals($this->user->name, $customer->name);
        $this->assertEquals($this->user->email, $customer->email);
        $this->assertEquals($this->user->flow_customer_id, $customer->customerId);

        $this->assertEquals('cus_abcd1234', $this->user->flow_customer_id);
        $this->assertNull($this->user->flow_card_brand);
        $this->assertNull($this->user->flow_card_last_four);
    }

    public function testDoesntUpdateCustomer()
    {
        $updated = $this->user->updateCustomer();

        $this->assertFalse($updated);
    }

    public function testRegisterCard()
    {
        $this->updateUserWithCustomer();

        \FlowCustomer::shouldReceive('registerCard')
            ->once()
            ->with('cus_abcd1234')
            ->andReturn(
                new BasicResponse([
                    'url' => 'https://flow.cl/disclaimer.php',
                    'token' => 'asdf1234',
                ])
            );

        $response = $this->user->registerCard();

        $this->assertEquals(
            'https://flow.cl/disclaimer.php?token=asdf1234',
            $response->getUrl()
        );
    }

    public function testDoesntRegisterCard()
    {
        $response = $this->user->registerCard();

        $this->assertFalse($response);
    }

    public function testSyncCard()
    {
        $this->updateUserWithCustomerAndCard();

        $this->doesntExpectEvents('eloquent.updated: *');

        $response = $this->user->syncCard(new CustomerResource([
            'customerId' => 'customerId',
            'creditCardType' => 'creditCardType',
            'last4CardDigits' => 'last4CardDigits',
        ]));

        $this->assertTrue($response);

        $this->assertEquals('customerId', $this->user->flow_customer_id);
        $this->assertEquals('creditCardType', $this->user->flow_card_brand);
        $this->assertEquals('last4CardDigits', $this->user->flow_card_last_four);
    }

    public function testPerformRemoveCard()
    {
        $this->updateUserWithCustomerAndCard();

        \FlowCustomer::shouldReceive('unregisterCard')
            ->once()
            ->with('cus_abcd1234')
            ->andReturnUsing(function ($id) {
                return new CustomerResource([
                    'customerId' => $id,
                    'creditCardType' => null,
                    'last4CardDigits' => null,
                ]);
            });

        $this->doesntExpectEvents('eloquent.updated: *');

        $response = $this->user->removeCard();

        $this->assertNull($response->creditCardType);
        $this->assertNull($response->last4CardDigits);

        $this->assertNull($this->user->flow_card_brand);
        $this->assertNull($this->user->flow_card_last_four);
    }

    public function testForceCharge()
    {
        $this->updateUserWithCustomerAndCard();

        $array = [
            'amount' => 200,
            'subject' => 'test subject',
            'commerceOrder' => 'order-1',
            'optionals' => ['message' => 'lol'],
        ];

        \FlowCustomer::shouldReceive('createCharge')
            ->once()
            ->with(array_merge(
                $array,
                ['customerId' => $this->user->flow_customer_id]
            ))
            ->andReturnUsing(function ($charge) use ($array) {
                return new CustomerResource([
                    'commerceOrder' => $array['commerceOrder'],
                    'requestDate' => '2018-01-01',
                    'amount' => $array['amount'],
                    'payer' => 'hello@orchestraplatform.com',
                ]);
            });

        $response = $this->user->forceCharge($array);

        $this->assertEquals($array['commerceOrder'], $response->commerceOrder);
        $this->assertEquals('2018-01-01', $response->requestDate);
        $this->assertEquals($array['amount'], $response->amount);
        $this->assertEquals('hello@orchestraplatform.com', $response->payer);
    }

    public function testGetCustomer()
    {
        $this->updateUserWithCustomer();

        \FlowCustomer::shouldReceive('get')
            ->with($this->user->flow_customer_id)
            ->andReturnUsing(function ($id) {
                return new CustomerResource([
                    'customerId' => $id,
                ]);
            });

        $customer = $this->user->getCustomer();

        $this->assertInstanceOf(CustomerResource::class, $customer);
        $this->assertEquals($this->user->flow_customer_id, $customer->customerId);
    }

    public function testDoesntGetCustomer()
    {
        $response = $this->user->getCustomer();

        $this->assertFalse($response);
    }

    public function testGetCustomerEmailKey()
    {
        $class = new class extends User {
            use Billable;
            public function getCustomerEmailKey(): string
            {
                return 'testEmailKey';
            }
        };

        $this->assertEquals('testEmailKey', $class->getCustomerEmailKey());

    }

    public function testHasCard()
    {
        $this->updateUserWithCustomerAndCard();

        $this->assertTrue($this->user->hasCard());
    }

    public function testDoesntHasCard()
    {
        $this->updateUserWithCustomer();

        $this->assertTrue($this->user->doesntHasCard());
    }

    public function testHasCustomer()
    {
        $this->updateUserWithCustomer();

        $this->assertTrue($this->user->hasCustomer());
    }

    public function testDoesntHasCustomer()
    {
        $this->assertTrue($this->user->doesntHasCustomer());
    }

    public function testGetCustomerNameKey()
    {
        $class = new class extends User {
            use Billable;
            public function getCustomerNameKey(): string
            {
                return 'testNameKey';
            }
        };

        $this->assertEquals('testNameKey', $class->getCustomerNameKey());
    }

    public function testChargeToCard()
    {
        $this->updateUserWithCustomerAndCard();

        $array = [
            'amount' => 200,
            'subject' => 'test subject',
            'commerceOrder' => 'order-1',
            'optionals' => ['message' => 'lol'],
        ];

        \FlowCustomer::shouldReceive('createCharge')
            ->once()
            ->with(array_merge(
                $array,
                ['customerId' => $this->user->flow_customer_id]
            ))
            ->andReturnUsing(function ($charge) use ($array) {
                return new CustomerResource([
                    'commerceOrder' => $array['commerceOrder'],
                    'requestDate' => '2018-01-01',
                    'amount' => $array['amount'],
                    'payer' => 'hello@orchestraplatform.com',
                ]);
            });

        $response = $this->user->chargeToCard($array);

        $this->assertEquals($array['commerceOrder'], $response->commerceOrder);
        $this->assertEquals('2018-01-01', $response->requestDate);
        $this->assertEquals($array['amount'], $response->amount);
        $this->assertEquals('hello@orchestraplatform.com', $response->payer);
    }

    public function testDoesntChargeToCard()
    {
        $this->updateUserWithCustomer();

        $response = $this->user->chargeToCard([
            'amount' => 200,
            'subject' => 'test subject',
            'commerceOrder' => 'order-1',
            'optionals' => ['message' => 'lol'],
        ]);

        $this->assertFalse($response);
    }

    public function testUpdateCustomerWithoutArguments()
    {
        $this->updateUserWithCustomer();

        \FlowCustomer::shouldReceive('update')
            ->once()
            ->with(
                $this->user->flow_customer_id,
                [
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'externalId' => $this->user->getCustomerExternalId(),
                ]
            )->andReturnUsing(function ($string, $array) {
                return new CustomerResource(
                    ['flow_customer_id' => $string] + $array
                );
            });

        $customer = $this->user->updateCustomer();

        $this->assertEquals($this->user->name, $customer->name);
        $this->assertEquals($this->user->email, $customer->email);
        $this->assertEquals($this->user->flow_customer_id, $customer->flow_customer_id);
    }

    public function testGetCustomerExternalId()
    {
        $class = new class extends User {
            protected $table = 'testTable';
            protected $primaryKey = 'superId';
            protected $attributes = ['superId' => 12];
            use Billable;
        };

        $this->assertEquals('testTable|superId|12', $class->getCustomerExternalId());
    }

    public function testForceCreateCustomer()
    {
        \FlowCustomer::shouldReceive('create')
            ->once()
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function ($array) {
                return new CustomerResource(array_merge([
                    'customerId' => 'cus_abcd1234',
                    'creditCardType' => 'visa',
                    'last4CardDigits' => '1234',
                ], $array));
            });

        $this->doesntExpectEvents('eloquent.updated: *');

        $resource = $this->user->forceCreateCustomer();

        $this->assertInstanceOf(CustomerResource::class, $resource);

        $this->assertEquals('Orchestra', $resource->name);
        $this->assertEquals('hello@orchestraplatform.com', $resource->email);
        $this->assertEquals('users|id|1', $resource->externalId);

        $this->assertEquals('cus_abcd1234', $this->user->flow_customer_id);
        $this->assertEquals('visa', $this->user->flow_card_brand);
        $this->assertEquals('1234', $this->user->flow_card_last_four);
    }

    public function testRemoveCard()
    {
        $this->updateUserWithCustomerAndCard();

        \FlowCustomer::shouldReceive('unregisterCard')
            ->once()
            ->with($this->user->flow_customer_id)
            ->andReturnUsing(function ($customerId) {
                return new CustomerResource([
                    'customerId' => $customerId,
                    'creditCardType' => null,
                    'last4CardDigits' => null,
                ]);
            });

        $this->doesntExpectEvents('eloquent.updated: *');

        $customer = $this->user->removeCard();

        $this->assertInstanceOf(CustomerResource::class, $customer);

        $this->assertEquals('cus_abcd1234', $this->user->flow_customer_id);
        $this->assertNull($this->user->flow_card_brand);
        $this->assertNull($this->user->flow_card_last_four);
    }

    public function testDoesntRemoveCard()
    {
        $response = $this->user->removeCard();

        $this->assertFalse($response);

        $this->updateUserWithCustomer();

        $response = $this->user->removeCard();

        $this->assertFalse($response);
    }

    public function testUpdateCard()
    {
        $this->updateUserWithCustomer();

        \FlowCustomer::shouldReceive('update')
            ->once()
            ->with(
                \Mockery::type('string'),
                \Mockery::type('array')
            )
            ->andReturnUsing(function ($id, $array) {
                return new CustomerResource(array_merge([
                    'customerId' => $id,
                    'creditCardType' => null,
                    'last4CardDigits' => null,
                ], $array));
            });

        $this->user->updateCustomer([
            'name' => 'newName',
            'email' => 'newEmail@email.com'
        ]);

        $this->assertEquals('cus_abcd1234', $this->user->flow_customer_id);
        $this->assertNull($this->user->flow_card_brand);
        $this->assertNull($this->user->flow_card_last_four);
    }

    public function testCharge()
    {
        $this->updateUserWithCustomerAndCard();

        $array = [
            'amount' => 200,
            'subject' => 'test subject',
            'commerceOrder' => 'order-1',
            'optionals' => ['message' => 'lol'],
        ];

        \FlowCustomer::shouldReceive('createCharge')
            ->once()
            ->with(array_merge(
                $array,
                ['customerId' => $this->user->flow_customer_id]
            ))
            ->andReturnUsing(function ($charge) use ($array) {
                return new CustomerResource([
                    'commerceOrder' => $array['commerceOrder'],
                    'requestDate' => '2018-01-01',
                    'amount' => $array['amount'],
                    'payer' => 'hello@orchestraplatform.com',
                ]);
            });

        $response = $this->user->charge($array);

        $this->assertEquals($array['commerceOrder'], $response->commerceOrder);
        $this->assertEquals('2018-01-01', $response->requestDate);
        $this->assertEquals($array['amount'], $response->amount);
        $this->assertEquals('hello@orchestraplatform.com', $response->payer);
    }

    public function testDoesntCharge()
    {
        $response = $this->user->charge([
            'foo' => 'bar'
        ]);

        $this->assertFalse($response);
    }

    public function testDeletesCustomerWhenDeletesModel()
    {
        $this->updateUserWithCustomer();

        \FlowCustomer::shouldReceive('delete')
            ->once()
            ->with($this->user->flow_customer_id);

        \FlowCustomer::shouldReceive('get')
            ->once()
            ->with($this->user->flow_customer_id)
            ->andReturnUsing(function ($id) {
                $customer = new CustomerResource([
                    'customerId' => $id,
                    'status' => 3
                ]);

                $customer->setExists(false);

                return $customer;
            });

        $deleted = $this->user->delete();

        $this->assertTrue($deleted);
        $this->assertFalse($this->user->exists());

        $this->assertFalse($this->user->getCustomer()->exists());

    }

    public function testDoesntDeleteCustomer()
    {
        $response = $this->user->deleteCustomer();

        $this->assertFalse($response);
    }
}
