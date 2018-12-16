<?php

namespace Tests\Traits;

use DarkGhostHunter\FlowSdk\Resources\CustomerResource;
use DarkGhostHunter\FlowSdk\Resources\SubscriptionResource;
use DarkGhostHunter\FlowSdk\Responses\PagedResponse;
use DarkGhostHunter\Laraflow\Billable;
use DarkGhostHunter\Laraflow\Models\FlowSubscription;
use DarkGhostHunter\Laraflow\Multisubscribable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Orchestra\Testbench\TestCase;

class MultisubscribableTest extends TestCase
{

    use HasTestUser;

    /** @var \DarkGhostHunter\Laraflow\Multisubscribable */
    protected $user;

    protected function createUser()
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

    protected function updateSubscriptionsWithCoupon()
    {
        $this->user->flowSubscriptions()->update([
            'coupon_id' => 1234
        ]);
    }

    protected function updateUserWithCard()
    {
        $this->user->update([
            'flow_card_brand' => 'visa',
            'flow_card_last_four' => 1234,
        ]);
    }

    public function testFlowSubscriptions()
    {
        $relation = $this->user->flowSubscriptions();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(FlowSubscription::class, $relation->getModel());
    }

    public function testHasSubscriptions()
    {
        $this->updateUserWithSubscriptions();

        $this->assertTrue($this->user->hasSubscriptions());
        $this->assertTrue($this->user->hasSubscriptions('sus_abcd1234'));
        $this->assertTrue($this->user->hasSubscriptions(['sus_abcd1234', 'rofl']));
        $this->assertFalse($this->user->hasSubscriptions(['no', 'rofl']));
    }

    public function testDoesntHaveSubscriptionsWithNoSubscriptions()
    {
        $this->assertTrue($this->user->doesntHaveSubscriptions());
        $this->assertTrue($this->user->doesntHaveSubscriptions('sus_abcd1234'));
        $this->assertTrue($this->user->doesntHaveSubscriptions(['sus_abcd1234', 'rofl']));
        $this->assertTrue($this->user->doesntHaveSubscriptions(['no', 'rofl']));
    }

    public function testDoesntHaveSubscriptionsWithSubscriptions()
    {
        $this->updateUserWithSubscriptions();

        $this->assertFalse($this->user->doesntHaveSubscriptions());
        $this->assertFalse($this->user->doesntHaveSubscriptions('sus_abcd1234'));
        $this->assertFalse($this->user->doesntHaveSubscriptions(['sus_abcd1234', 'rofl']));
        $this->assertTrue($this->user->doesntHaveSubscriptions(['no', 'rofl']));
    }

    public function testHasSubscription()
    {
        $this->updateUserWithSubscriptions();

        $this->assertTrue($this->user->hasSubscription('sus_abcd1234'));
        $this->assertFalse($this->user->hasSubscription('rofl'));
    }

    public function testDoesntHaveSubscriptionWithNoSubscriptions()
    {
        $this->assertTrue($this->user->doesntHaveSubscription('sus_abcd1234'));
        $this->assertTrue($this->user->doesntHaveSubscription('rofl'));
    }

    public function testDoesntHaveSubscription()
    {
        $this->updateUserWithSubscriptions();

        $this->assertFalse($this->user->doesntHaveSubscription('sus_abcd1234'));
        $this->assertTrue($this->user->doesntHaveSubscription('rofl'));
    }

    public function testIsSubscribedTo()
    {
        $this->updateUserWithSubscriptions();

        $this->assertTrue($this->user->isSubscribedTo('testPlan1'));
        $this->assertFalse($this->user->isSubscribedTo('noPlan'));
    }

    public function testIsNotSubscribedTo()
    {
        $this->updateUserWithSubscriptions();

        $this->assertTrue($this->user->isNotSubscribedTo('noPlan'));
        $this->assertFalse($this->user->isNotSubscribedTo('testPlan1'));
    }

    public function testSubscription()
    {
        $this->updateUserWithSubscriptions();

        \FlowSubscription::shouldReceive('get')
            ->once()
            ->with($id = 'sus_abcd1234')
            ->andReturnUsing(function ($id) {
                $subscription = new SubscriptionResource([
                    'subscriptionId' => $id,
                    'planId' => 'testPlan1',
                    'customerId' => 'cus_abcd1234'
                ]);
                $subscription->setExists();
                return $subscription;
            });

        $subscription = $this->user->subscription($id);

        $this->assertInstanceOf(SubscriptionResource::class, $subscription);
        $this->assertEquals(1, $this->user->flowSubscriptions()->where('plan_id', 'testPlan1')->count());
    }

    public function testDoesntReturnSubscription()
    {
        $subscription = $this->user->subscription('no_subscription');

        $this->assertFalse($subscription);
    }

    public function testSubscriptionsForPlanId()
    {
        $this->updateUserWithSubscriptions();

        \FlowSubscription::shouldReceive('getPage')
            ->once()
            ->with(1, [
                'planId' => $planId = 'testPlan1',
                'limit' => 100,
                'start' => 0,
                'filter' => $this->user->name,
                'status' => 1
            ])->andReturnUsing(function () {
                return new PagedResponse([
                    'items' => [
                        new SubscriptionResource(['subscriptionId' => 'sus_abcd1234', 'planId' => 'testPlan1']),
                        new SubscriptionResource(['subscriptionId' => 'sus_abcd1235', 'planId' => 'testPlan1']),
                        new SubscriptionResource(['subscriptionId' => 'sus_abcd1236', 'planId' => 'testPlan1']),
                    ]
                ]);
            });

        $resource = $this->user->subscriptionsForPlanId($planId);

        $this->assertInstanceOf(Collection::class, $resource);
        $this->assertCount(3, $resource);
        $this->assertInstanceOf(SubscriptionResource::class, $resource->first());
        $this->assertEquals('sus_abcd1234', $resource->first()->subscriptionId);
        $this->assertEquals($planId, $resource->first()->planId);
    }

    public function testDoesntReturnSubscriptionsForPlanId()
    {
        $this->updateUserWithSubscriptions();

        $this->assertFalse($this->user->subscriptionsForPlanId('noPlan'));

        $this->user->name = null;

        $this->assertFalse($this->user->subscriptionsForPlanId('testPlan1'));
    }

    public function testSubscriptions()
    {
        $this->updateUserWithSubscriptions();

        $this->user->flowSubscriptions()->create([
            'subscription_id' => 'sus_abcd12310',
            'plan_id' => 'testPlan2',
            'coupon_id' => null,
            'trial_starts_at' => '2018-01-01',
            'trial_ends_at' => '2018-01-03',
            'starts_at' => '2018-01-04',
            'ends_at' => '2018-01-14',
        ]);

        \FlowSubscription::shouldReceive('getPage')
            ->once()
            ->with(1, [
                'planId' => $planId1 = 'testPlan1',
                'limit' => 100,
                'start' => 0,
                'filter' => $this->user->name,
                'status' => 1
            ])->andReturnUsing(function () {
                return new PagedResponse([
                    'items' => [
                        new SubscriptionResource(['subscriptionId' => 'sus_abcd1233', 'planId' => 'testPlan1']),
                        new SubscriptionResource(['subscriptionId' => 'sus_abcd1234', 'planId' => 'testPlan1']),
                        new SubscriptionResource(['subscriptionId' => 'sus_abcd1235', 'planId' => 'testPlan1']),
                        new SubscriptionResource(['subscriptionId' => 'sus_abcd1236', 'planId' => 'testPlan1']),
                    ]
                ]);
            });

        \FlowSubscription::shouldReceive('getPage')
            ->with(1, [
                'planId' => $planId2 = 'testPlan2',
                'limit' => 100,
                'start' => 0,
                'filter' => $this->user->name,
                'status' => 1
            ])->andReturnUsing(function () {
                return new PagedResponse([
                    'items' => [
                        new SubscriptionResource(['subscriptionId' => 'sus_abcd1237', 'planId' => 'testPlan2']),
                        new SubscriptionResource(['subscriptionId' => 'sus_abcd1238', 'planId' => 'testPlan2']),
                        new SubscriptionResource(['subscriptionId' => 'sus_abcd1239', 'planId' => 'testPlan2']),
                    ]
                ]);
            });

        $resource = $this->user->subscriptions();

        $this->assertInstanceOf(Collection::class, $resource);
        $this->assertCount(7, $resource);

        $subscriptions = [
            'sus_abcd1233',
            'sus_abcd1234',
            'sus_abcd1235',
            'sus_abcd1236',
            'sus_abcd1237',
            'sus_abcd1238',
            'sus_abcd1239',
        ];

        foreach ($subscriptions as $key => $subscription) {
            $this->assertEquals($subscription, $resource->get($key)->subscriptionId);
        }

    }

    public function testDoesntReturnSubscriptionsWithNoSubscriptions()
    {
        $resources = $this->user->subscriptions();

        $this->assertFalse($resources);
    }

    public function testDoesntReturnSubscriptionsWithoutCustomerName()
    {
        $this->user->name = null;

        $this->assertFalse($this->user->subscriptions());
    }

    public function testSubscribe()
    {
        $this->updateUserWithSubscriptions();

        \FlowSubscription::shouldReceive('create')
            ->once()
            ->with([
                'customerId' => $this->user->flow_customer_id,
                'planId' => 'testPlan3',
            ])
            ->andReturnUsing(function ($array) {
                $subscription = new SubscriptionResource([
                    'customerId' => $array['customerId'],
                    'subscriptionId' => 'sus_abcd1239',
                    'planId' => $array['planId'],
                    'trial_start' => '2018-01-01',
                    'trial_end' => '2018-01-01',
                    'period_start' => '2018-01-01',
                    'period_end' => '2018-01-01',
                ]);
                $subscription->setExists();
                return $subscription;
            });

        $subscription = $this->user->subscribe([
            'planId' => 'testPlan3',
        ]);

        $data = $this->user->flowSubscriptions()->where('plan_id', 'testPlan3')->first();

        $this->assertInstanceOf(SubscriptionResource::class, $subscription);
        $this->assertEquals('testPlan3', $subscription->planId);

        $this->assertNotNull($data);
        $this->assertEquals('2018-01-01', $data->trial_starts_at->format('Y-m-d'));
        $this->assertEquals('2018-01-01', $data->trial_ends_at->format('Y-m-d'));
        $this->assertEquals('2018-01-01', $data->starts_at->format('Y-m-d'));
        $this->assertEquals('2018-01-01', $data->ends_at->format('Y-m-d'));
    }

    public function testSubscribesWithCard()
    {
        $this->updateUserWithSubscriptions();
        $this->updateUserWithCard();

        \FlowSubscription::shouldReceive('create')
            ->once()
            ->with([
                'customerId' => $this->user->flow_customer_id,
                'planId' => 'testPlan3',
            ])
            ->andReturnUsing(function ($array) {
                $subscription = new SubscriptionResource([
                    'customerId' => $array['customerId'],
                    'subscriptionId' => 'sus_abcd1239',
                    'planId' => $array['planId'],
                    'trial_start' => '2018-01-01',
                    'trial_end' => '2018-01-01',
                    'period_start' => '2018-01-01',
                    'period_end' => '2018-01-01',
                ]);
                $subscription->setExists();
                return $subscription;
            });

        $subscription = $this->user->subscribeWithCard([
            'planId' => 'testPlan3',
        ]);

        $data = $this->user->flowSubscriptions()->where('plan_id', 'testPlan3')->first();

        $this->assertInstanceOf(SubscriptionResource::class, $subscription);
        $this->assertEquals('testPlan3', $subscription->planId);

        $this->assertNotNull($data);
        $this->assertEquals('2018-01-01', $data->trial_starts_at->format('Y-m-d'));
        $this->assertEquals('2018-01-01', $data->trial_ends_at->format('Y-m-d'));
        $this->assertEquals('2018-01-01', $data->starts_at->format('Y-m-d'));
        $this->assertEquals('2018-01-01', $data->ends_at->format('Y-m-d'));
    }

    public function testDoesntSubscribesWithoutCard()
    {
        $this->updateUserWithSubscriptions();

        $subscription = $this->user->subscribeWithCard([
            'planId' => 'testPlan3',
            'foo' => 'bar'
        ]);

        $this->assertFalse($subscription);
        $this->assertNull($this->user->flowSubscriptions()->where('plan_id', 'testPlan3')->first());
    }


    public function testDoesntSubscribeIfHasSamePlan()
    {
        $this->updateUserWithSubscriptions();

        $subscription = $this->user->subscribe([
            'planId' => 'testPlan2',
            'foo' => 'bar'
        ]);

        $this->assertFalse($subscription);

    }

    public function testForceSubscribe()
    {
        $this->updateUserWithSubscriptions();

        \FlowSubscription::shouldReceive('create')
            ->once()
            ->with([
                'customerId' => $this->user->flow_customer_id,
                'planId' => $planId = 'testPlan2',
            ])
            ->andReturnUsing(function ($array) {
                $subscription = new SubscriptionResource([
                    'customerId' => $array['customerId'],
                    'subscriptionId' => 'sus_abcd1239',
                    'planId' => $array['planId'],
                    'trial_start' => '2018-01-01',
                    'trial_end' => '2018-01-01',
                    'period_start' => '2018-01-01',
                    'period_end' => '2018-01-01',
                    'status' => 1,
                ]);
                $subscription->setExists();
                return $subscription;
            });

        $subscription = $this->user->forceSubscribe([
            'planId' => $planId,
        ]);

        $this->assertInstanceOf(SubscriptionResource::class, $subscription);
        $this->assertEquals('testPlan2', $subscription->planId);

        $this->assertEquals(2, $this->user->flowSubscriptions()->where('plan_id', 'testPlan2')->count());

        $data = $this->user->flowSubscriptions()->where('subscription_id', 'sus_abcd1239')->first();

        $this->assertEquals('2018-01-01', $data->trial_starts_at->format('Y-m-d'));
        $this->assertEquals('2018-01-01', $data->trial_ends_at->format('Y-m-d'));
        $this->assertEquals('2018-01-01', $data->starts_at->format('Y-m-d'));
        $this->assertEquals('2018-01-01', $data->ends_at->format('Y-m-d'));
    }

    public function testUpdatesSubscription()
    {
        $this->updateUserWithSubscriptions();

        Carbon::setTestNow(Carbon::create(2017,12,30));

        $subscription = $this->user->flowSubscriptions()->find(1);

        \FlowSubscription::shouldReceive('update')
            ->once()
            ->with(
                $subscription->subscription_id,
                ['trial_period_days' => 20]
            )
            ->andReturnUsing(function ($id, $array) {
                $subscription = new SubscriptionResource($array + [
                        'subscription_id' => $id,
                        'trial_start' => '2018-01-01',
                        'trial_end' => '2018-01-20',
                        'period_start' => '2018-01-21',
                        'period_end' => '2018-02-21',
                    ]);
                $subscription->setExists(true);
                return $subscription;
            });

        $subscription = $this->user->updateSubscription($subscription->subscription_id, 20);

        $this->assertEquals(20, $subscription->trial_period_days);

        $updatedData = $this->user->flowSubscriptions()->find(1);

        $this->assertEquals('2018-01-01', $updatedData->trial_starts_at->toDateString());
        $this->assertEquals('2018-01-20', $updatedData->trial_ends_at->toDateString());
        $this->assertEquals('2018-01-21', $updatedData->starts_at->toDateString());
        $this->assertEquals('2018-02-21', $updatedData->ends_at->toDateString());
    }

    public function testDoesntUpdateSubscriptionsIfDoesntExists()
    {
        $this->updateUserWithSubscriptions();

        $subscription = $this->user->updateSubscription('doesnExists', 20);

        $this->assertFalse($subscription);
    }

    public function testDoesntUpdateSubscriptionIfOutOfTrialDays()
    {
        $this->updateUserWithSubscriptions();

        $subscriptionData = $this->user->flowSubscriptions()->find(1);

        Carbon::setTestNow(Carbon::create(2018,01,05));

        $subscription = $this->user->updateSubscription($subscriptionData->subscription_id, 20);

        $this->assertFalse($subscription);
    }

    public function testUnsubscribe()
    {
        $this->updateUserWithSubscriptions();

        \FlowSubscription::shouldReceive('cancel')
            ->once()
            ->with($id = $this->user->flowSubscriptions()->value('subscription_id'), false)
            ->andReturnUsing(function ($id) {
                $subscription = new SubscriptionResource([
                    'subscriptionId' => $id,
                    'customerId' => $this->user->flow_customer_id,
                ]);
                $subscription->setExists(false);
                return $subscription;
            });

        $resource = $this->user->unsubscribe($id);

        $this->assertInstanceOf(SubscriptionResource::class, $resource);
        $this->assertFalse($this->user->flowSubscriptions()->where('subscription_id', $id)->exists());
    }

    public function testDoesntUnsubscribeWhereSubscriptionDoesntExist()
    {
        $this->updateUserWithSubscriptions();
        $resource = $this->user->unsubscribe('doesntExists');

        $this->assertFalse($resource);
        $this->assertEquals(2, $this->user->flowSubscriptions()->count());
    }

    public function testForceUnsubscribe()
    {
        \FlowSubscription::shouldReceive('cancel')
            ->once()
            ->with($id = 'doesntExists', false)
            ->andReturnUsing(function ($id) {
                $subscription = new SubscriptionResource([
                    'subscriptionId' => $id,
                    'customerId' => $this->user->flow_customer_id,
                    'status' => 0,
                ]);
                $subscription->setExists(false);
                return $subscription;
            });

        $resource = $this->user->forceUnsubscribe($id);

        $this->assertInstanceOf(SubscriptionResource::class, $resource);
        $this->assertEquals($id, $resource->subscriptionId);
        $this->assertFalse($this->user->flowSubscriptions()->where('subscription_id', $id)->exists());
    }

    public function testUnsubscribeNow()
    {
        $this->updateUserWithSubscriptions();

        \FlowSubscription::shouldReceive('cancel')
            ->once()
            ->with($id = $this->user->flowSubscriptions()->value('subscription_id'), true)
            ->andReturnUsing(function ($id) {
                return new SubscriptionResource([
                    'subscriptionId' => $id,
                    'customerId' => $this->user->flow_customer_id,
                    'status' => 0,
                ]);
            });

        $resource = $this->user->unsubscribeNow($id);

        $data = $this->user->flowSubscriptions()->where('subscription_id', $id)->first();

        $this->assertInstanceOf(SubscriptionResource::class, $resource);
        $this->assertNull($data);
    }

    public function testDoesntUnsubscribeNowIfSubscriptionDoesntExists()
    {
        $this->updateUserWithSubscriptions();
        $resource = $this->user->unsubscribeNow('doesntExists');

        $this->assertFalse($resource);
        $this->assertEquals(2, $this->user->flowSubscriptions()->count());
    }

    public function testForceUnsubscribeNow()
    {
        \FlowSubscription::shouldReceive('cancel')
            ->once()
            ->with($id = 'doesntExists', true)
            ->andReturnUsing(function ($id) {
                $subscription = new SubscriptionResource([
                    'subscriptionId' => $id,
                    'customerId' => $this->user->flow_customer_id,
                    'status' => 0,
                ]);
                $subscription->setExists(false);
                return $subscription;
            });

        $resource = $this->user->forceUnsubscribeNow($id);

        $this->assertInstanceOf(SubscriptionResource::class, $resource);
        $this->assertEquals($id, $resource->subscriptionId);
        $this->assertFalse($this->user->flowSubscriptions()->where('subscription_id', $id)->exists());
    }

    public function testAttachCoupon()
    {
        $this->updateUserWithSubscriptions();

        \FlowSubscription::shouldReceive('addCoupon')
            ->once()
            ->with(
                $subscriptionId = $this->user->flowSubscriptions()->value('subscription_id'),
                $couponId = 1234)
            ->andReturnUsing(function ($id, $couponId) {
                $subscription = new SubscriptionResource([
                    'subscriptionId' => $id,
                    'discount' => $couponId,
                ]);
                $subscription->setExists();
                return $subscription;
            });

        $resource = $this->user->attachCoupon($subscriptionId, $couponId);

        $this->assertInstanceOf(SubscriptionResource::class, $resource);
        $this->assertEquals($couponId, $resource->discount);
        $this->assertEquals($couponId,
            $this->user->flowSubscriptions()->where('subscription_id', $subscriptionId)->value('coupon_id')
        );
    }

    public function testDoesntAttachCouponIfSubscriptionDoesntExists()
    {
        $this->updateUserWithSubscriptions();
        $resource = $this->user->attachCoupon('doesnExists', 1234);

        $this->assertFalse($resource);
    }

    public function testDoesntAttachCouponIfHasCoupon()
    {
        $this->updateUserWithSubscriptions();
        $this->updateSubscriptionsWithCoupon();

        $resource = $this->user->attachCoupon($subscriptionId = 'sus_abcd1234', 5678);

        $this->assertFalse($resource);
        $this->assertEquals(
            1234,
            $this->user->flowSubscriptions()->where('subscription_id', $subscriptionId)->value('coupon_id')
        );
    }

    public function testDetachCoupon()
    {
        $this->updateUserWithSubscriptions();
        $this->updateSubscriptionsWithCoupon();

        \FlowSubscription::shouldReceive('removeCoupon')
            ->once()
            ->with($subscriptionId = $this->user->flowSubscriptions()->value('subscription_id'))
            ->andReturnUsing(function ($id) {
                $subscription = new SubscriptionResource([
                    'subscriptionId' => $id,
                    'discount' => null,
                ]);
                $subscription->setExists(true);
                return $subscription;
            });

        $resource = $this->user->detachCoupon($subscriptionId);

        $this->assertInstanceOf(SubscriptionResource::class, $resource);
        $this->assertEquals($subscriptionId, $resource->subscriptionId);
        $this->assertNull($resource->discount);
        $this->assertNull(
            $this->user->flowSubscriptions()->where('subscription_id', $subscriptionId)->value('coupon_id')
        );
    }

    public function testDoesntDetachCouponIfSubscriptionDoesntExists()
    {
        $resource = $this->user->detachCoupon('doesnExists', 1234);

        $this->assertFalse($resource);
    }

    public function testAttachOrReplaceCoupon()
    {
        $this->updateUserWithSubscriptions();
        $this->updateSubscriptionsWithCoupon();

        \FlowSubscription::shouldReceive('addCoupon')
            ->once()
            ->with(
                $subscriptionId = $this->user->flowSubscriptions()->value('subscription_id'),
                $couponId = 5678)
            ->andReturnUsing(function ($id, $couponId) {
                return new SubscriptionResource([
                    'subscriptionId' => $id,
                    'discount' => $couponId,
                    'status' => 1,
                ]);
            });

        $resource = $this->user->attachOrReplaceCoupon($subscriptionId, $couponId);

        $this->assertInstanceOf(SubscriptionResource::class, $resource);
        $this->assertEquals($couponId, $resource->discount);
        $this->assertEquals($couponId,
            $this->user->flowSubscriptions()->where('subscription_id', $subscriptionId)->value('coupon_id')
        );
    }

    public function testDoesntAttachOrReplacesCouponIfSubscriptionDoesntExists()
    {
        $resource = $this->user->attachOrReplaceCoupon('doesnExists', 1234);

        $this->assertFalse($resource);
    }
}
