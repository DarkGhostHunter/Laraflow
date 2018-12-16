<?php

namespace Tests\Traits;

use DarkGhostHunter\FlowSdk\Resources\SubscriptionResource;
use DarkGhostHunter\Laraflow\Billable;
use DarkGhostHunter\Laraflow\Models\FlowSubscription;
use DarkGhostHunter\Laraflow\Subscribable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Orchestra\Testbench\TestCase;

class SubscribableTest extends TestCase
{
    use HasTestUser;

    /** @var \DarkGhostHunter\Laraflow\Billable & \DarkGhostHunter\Laraflow\Subscribable */
    protected $user;

    protected function createUser()
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

    protected function updateSubscriptionWithCoupon()
    {
        $this->user->flowSubscription()->update([
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

    public function testFlowSubscription()
    {
        $relation = $this->user->flowSubscription();

        $this->assertInstanceOf(HasOne::class, $relation);
        $this->assertInstanceOf(FlowSubscription::class, $relation->getModel());
    }

    public function testHasSubscription()
    {
        $this->assertFalse($this->user->hasSubscription());

        $this->updateUserWithSubscription();

        $this->assertTrue($this->user->hasSubscription());
    }

    public function testDoesntHasSubscription()
    {
        $this->assertTrue($this->user->doesntHaveSubscription());

        $this->updateUserWithSubscription();

        $this->assertFalse($this->user->doesntHaveSubscription());
    }

    public function testSubscription()
    {
        $this->updateUserWithSubscription();

        \FlowSubscription::shouldReceive('get')
            ->once()
            ->with($this->user->flowSubscription()->value('subscription_id'))
            ->andReturnUsing(function ($id) {
                $subscription = new SubscriptionResource([
                    'subscriptionId' => $id,
                    'planId' => 'testPlan',
                    'customerId' => 'cus_abcd1234',
                    'status' => 1,
                ]);
                $subscription->setExists();
                return $subscription;
            });

        $resource = $this->user->subscription();

        $this->assertInstanceOf(SubscriptionResource::class, $resource);
        $this->assertEquals($this->user->flowSubscription()->value('subscription_id'), $resource->subscriptionId);
        $this->assertEquals($this->user->flowSubscription()->value('plan_id'), $resource->planId);
        $this->assertEquals($this->user->flow_customer_id, $resource->customerId);
    }

    public function testDoesntHaveSubscription()
    {
        $this->assertFalse($this->user->subscription());
    }

    public function testSubscribe()
    {
        \FlowSubscription::shouldReceive('create')
            ->once()
            ->with([
                'customerId' => $this->user->flow_customer_id,
                'planId' => 'testPlanId3',
            ])
            ->andReturnUsing(function ($array) {
                $subscription = new SubscriptionResource($array + [
                    'planId' => 'testPlanId3',
                    'subscriptionId' => 'sus_abcd1234',
                    'trial_start' => '2018-01-01',
                    'trial_end' => '2018-01-01',
                    'period_start' => '2018-01-01',
                    'period_end' => '2018-01-01',
                    'status' => 1,
                ]);
                $subscription->setExists();
            });

        $resource = $this->user->subscribe([
            'planId' => 'testPlanId3'
        ]);

        $subscriptionData = $this->user->flowSubscription()->first();

        $this->assertInstanceOf(SubscriptionResource::class, $resource);
        $this->assertEquals($this->user->flow_customer_id, $resource->customerId);
        $this->assertTrue($resource->setExists());
        $this->assertEquals('2018-01-01', $subscriptionData->trial_starts_at->format('Y-m-d'));
        $this->assertEquals('2018-01-01', $subscriptionData->trial_ends_at->format('Y-m-d'));
        $this->assertEquals('2018-01-01', $subscriptionData->starts_at->format('Y-m-d'));
        $this->assertEquals('2018-01-01', $subscriptionData->ends_at->format('Y-m-d'));
    }

    public function testSubscribeButDoesntPersist()
    {
        \FlowSubscription::shouldReceive('create')
            ->once()
            ->with([
                'customerId' => $this->user->flow_customer_id,
                'planId' => 'testPlanId3',
            ])
            ->andReturnUsing(function ($array) {
                $subscription = new SubscriptionResource($array + [
                        'planId' => 'testPlanId3',
                        'subscriptionId' => 'sus_abcd1234',
                        'trial_start' => '2018-01-01',
                        'trial_end' => '2018-01-01',
                        'period_start' => '2018-01-01',
                        'period_end' => '2018-01-01',
                        'status' => 4,
                    ]);
                $subscription->setExists(false);
                return $subscription;
            });

        $resource = $this->user->subscribe([
            'planId' => 'testPlanId3'
        ]);

        $this->assertInstanceOf(SubscriptionResource::class, $resource);
        $this->assertEquals($this->user->flow_customer_id, $resource->customerId);
        $this->assertFalse($resource->exists());
        $this->assertNull($this->user->flowSubscription()->first());
    }

    public function testDoesntSubscribe()
    {
        $this->updateUserWithSubscription();

        $resource = $this->user->subscribe([
            'foo' => 'bar'
        ]);

        $this->assertFalse($resource);
    }

    public function testSubscribeWithCard()
    {
        $this->updateUserWithCard();

        \FlowSubscription::shouldReceive('create')
            ->once()
            ->with([
                'customerId' => $this->user->flow_customer_id,
                'planId' => 'testPlanId'
            ])
            ->andReturnUsing(function ($array) {
                $subscription = new SubscriptionResource($array + [
                        'subscriptionId' => 'sus_abcd1234',
                        'trial_start' => '2018-01-01',
                        'trial_end' => '2018-01-01',
                        'period_start' => '2018-01-01',
                        'period_end' => '2018-01-01',
                ]);
                $subscription->setExists(true);
                return $subscription;
            });

        $resource = $this->user->subscribeWithCard([
            'planId' => 'testPlanId'
        ]);

        $subscriptionData = $this->user->flowSubscription()->first();

        $this->assertInstanceOf(SubscriptionResource::class, $resource);
        $this->assertEquals($this->user->flow_customer_id, $resource->customerId);
        $this->assertNotNull($subscriptionData);
        $this->assertEquals('sus_abcd1234', $subscriptionData->subscription_id);
        $this->assertEquals('2018-01-01', $subscriptionData->trial_starts_at->format('Y-m-d'));
        $this->assertEquals('2018-01-01', $subscriptionData->trial_ends_at->format('Y-m-d'));
        $this->assertEquals('2018-01-01', $subscriptionData->starts_at->format('Y-m-d'));
        $this->assertEquals('2018-01-01', $subscriptionData->ends_at->format('Y-m-d'));
    }

    public function testDoesntSubscribeWithCard()
    {
        $resource = $this->user->subscribeWithCard([
            'foo' => 'bar'
        ]);

        $this->assertFalse($resource);
    }

    public function testUpdatesSubscription()
    {
        $this->updateUserWithSubscription();

        Carbon::setTestNow(Carbon::create(2017,12,30));

        $subscription = $this->user->flowSubscription()->first();

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

        $subscription = $this->user->updateSubscription(20);

        $this->assertEquals(20, $subscription->trial_period_days);

        $updatedData = $this->user->flowSubscription()->first();

        $this->assertEquals('2018-01-01', $updatedData->trial_starts_at->toDateString());
        $this->assertEquals('2018-01-20', $updatedData->trial_ends_at->toDateString());
        $this->assertEquals('2018-01-21', $updatedData->starts_at->toDateString());
        $this->assertEquals('2018-02-21', $updatedData->ends_at->toDateString());
    }

    public function testDoesntUpdateSubscriptionsIfDoesntExists()
    {
        $subscription = $this->user->updateSubscription(20);

        $this->assertFalse($subscription);
    }

    public function testDoesntUpdateSubscriptionIfOutOfTrialDays()
    {
        $this->updateUserWithSubscription();

        Carbon::setTestNow(Carbon::create(2018,01,05));

        $subscription = $this->user->updateSubscription(20);

        $this->assertFalse($subscription);
    }

    public function testUnsubscribe()
    {
        $this->updateUserWithSubscription();

        \FlowSubscription::shouldReceive('cancel')
            ->once()
            ->with($id = $this->user->flowSubscription()->value('subscription_id'), false)
            ->andReturnUsing(function ($id) {
                $subscribe = new SubscriptionResource([
                    'subscriptionId' => $id,
                    'customerId' => $this->user->flow_customer_id,
                ]);
                $subscribe->setExists(false);
                return $subscribe;
            });

        $resource = $this->user->unsubscribe();

        $this->assertInstanceOf(SubscriptionResource::class, $resource);
        $this->assertEquals($id, $resource->subscriptionId);
        $this->assertEquals($this->user->flow_customer_id, $resource->customerId);
        $this->assertNull($this->user->flowSubscription()->first());
    }

    public function testDoesntUnsubscribe()
    {
        $resource = $this->user->unsubscribe();

        $this->assertFalse($resource);
    }

    public function testUnsubscribeNow()
    {
        $this->updateUserWithSubscription();

        \FlowSubscription::shouldReceive('cancel')
            ->once()
            ->with($id = $this->user->flowSubscription()->value('subscription_id'), true)
            ->andReturnUsing(function ($id) {
                $subscription =  new SubscriptionResource([
                    'subscriptionId' => $id,
                    'customerId' => $this->user->flow_customer_id,
                ]);
                $subscription->setExists(false);
                return $subscription;
            });

        $resource = $this->user->unsubscribeNow();

        $this->assertInstanceOf(SubscriptionResource::class, $resource);
        $this->assertEquals($id, $resource->subscriptionId);
        $this->assertEquals($this->user->flow_customer_id, $resource->customerId);
        $this->assertNull($this->user->flowSubscription()->first());
    }

    public function testDoesntUnsubscribeNow()
    {
        $resource = $this->user->unsubscribeNow();

        $this->assertFalse($resource);
    }

    public function testAttachCoupon()
    {
        $this->updateUserWithSubscription();

        \FlowSubscription::shouldReceive('addCoupon')
            ->once()
            ->with(
                $subscriptionId = $this->user->flowSubscription()->value('subscription_id'),
                $couponId = 1234
            )
            ->andReturnUsing(function ($id, $couponId) {
                return new SubscriptionResource([
                    'subscriptionId' => $id,
                    'discount' => $couponId
                ]);
            });

        $resource = $this->user->attachCoupon($couponId);

        $this->assertInstanceOf(SubscriptionResource::class, $resource);
        $this->assertEquals($subscriptionId, $resource->subscriptionId);
        $this->assertEquals($couponId, $resource->discount);
        $this->assertEquals($couponId, $this->user->flowSubscription()->value('coupon_id'));
    }

    public function testDoesntAttachCouponWithNoSubscription()
    {
        $resource = $this->user->attachCoupon(1234);

        $this->assertFalse($resource);
    }

    public function testDoesntAttachCouponIfAlreadyHasCoupon()
    {
        $this->updateUserWithSubscription();
        $this->updateSubscriptionWithCoupon();

        $resource = $this->user->attachCoupon(5678);

        $this->assertFalse($resource);
        $this->assertEquals(
            1234,
            $this->user->flowSubscription()->value('coupon_id')
        );
    }

    public function testAttachOrReplaceCouponWithoutCoupon()
    {
        $this->updateUserWithSubscription();

        \FlowSubscription::shouldReceive('addCoupon')
            ->once()
            ->with(
                $subscriptionId = $this->user->flowSubscription()->value('subscription_id'),
                $couponId = 1234)
            ->andReturnUsing(function ($id, $couponId) {
                return new SubscriptionResource([
                    'subscriptionId' => $id,
                    'discount' => $couponId
                ]);
            });

        $resource = $this->user->attachOrReplaceCoupon(1234);

        $this->assertInstanceOf(SubscriptionResource::class, $resource);
        $this->assertEquals($subscriptionId, $resource->subscriptionId);
        $this->assertEquals($couponId, $resource->discount);
        $this->assertEquals($couponId, $this->user->flowSubscription()->value('coupon_id'));
    }

    public function testAttachOrReplaceCouponWithCoupon()
    {
        $this->updateUserWithSubscription();
        $this->updateSubscriptionWithCoupon();

        \FlowSubscription::shouldReceive('addCoupon')
            ->once()
            ->with(
                $subscriptionId = $this->user->flowSubscription()->value('subscription_id'),
                $couponId = 5678)
            ->andReturnUsing(function ($id, $couponId) {
                return new SubscriptionResource([
                    'subscriptionId' => $id,
                    'discount' => $couponId
                ]);
            });

        $resource = $this->user->attachOrReplaceCoupon(5678);

        $this->assertInstanceOf(SubscriptionResource::class, $resource);
        $this->assertEquals($subscriptionId, $resource->subscriptionId);
        $this->assertEquals($couponId, $resource->discount);
        $this->assertEquals($couponId, $this->user->flowSubscription()->value('coupon_id'));
    }

    public function testDoesntAttachOrReplaceCouponIfSubscriptionDoesntExists()
    {
        $this->assertFalse($this->user->attachOrReplaceCoupon(1234));
    }

    public function testDetachCoupon()
    {
        $this->updateUserWithSubscription();
        $this->updateSubscriptionWithCoupon();

        \FlowSubscription::shouldReceive('removeCoupon')
            ->once()
            ->with($subscriptionId = $this->user->flowSubscription()->value('subscription_id'))
            ->andReturnUsing(function ($id) {
                return new SubscriptionResource([
                    'subscriptionId' => $id,
                    'foo' => 'bar'
                ]);
            });

        $resource = $this->user->detachCoupon();

        $this->assertInstanceOf(SubscriptionResource::class, $resource);
        $this->assertEquals($subscriptionId, $resource->subscriptionId);
        $this->assertNull($this->user->flowSubscription()->value('coupon_id'));
    }

    public function testDoesntDetachCouponWithoutSubscription()
    {
        $this->assertFalse($this->user->detachCoupon());
    }

    public function testDoesntDetachCouponWithoutCoupon()
    {
        $this->updateUserWithSubscription();

        $this->assertFalse($this->user->detachCoupon());
    }
}
