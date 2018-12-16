<?php

namespace DarkGhostHunter\Laraflow;

/**
 * Trait Subscribable
 *
 * This trait uses makes a relationship with a single Subscription.
 *
 * @package DarkGhostHunter\Laraflow
 */
trait Subscribable
{
    use Operations\PerformCouponOperations,
        Operations\PerformSubscriptionOperations;

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Flow Subscription for this Model
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|\DarkGhostHunter\Laraflow\Models\FlowSubscription
     */
    public function flowSubscription()
    {
        return $this->hasOne(
            'DarkGhostHunter\Laraflow\Models\FlowSubscription',
            'flow_customer_id', 'flow_customer_id'
        );
    }

    /**
     * Updates the Flow Subscription
     *
     * @param string $subscriptionId
     * @return Models\FlowSubscription|\Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function returnFlowSubscription(string $subscriptionId = null)
    {
        return $this->flowSubscription();
    }

    /**
     * Return the Subscription Id if it exists
     *
     * @return string|null
     */
    protected function getSubscriptionId()
    {
        return $this->flowSubscription()->value('subscription_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Subscription Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Return if the User has a subscription
     *
     * @return bool
     */
    public function hasSubscription()
    {
        return $this->flowSubscription()->exists();
    }

    /**
     * Return if the User doesn't has a subscription
     *
     * @return bool
     */
    public function doesntHaveSubscription()
    {
        return !$this->hasSubscription();
    }

    /*
    |--------------------------------------------------------------------------
    | Subscription Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Returns the Customer Subscription
     *
     * @return bool|\DarkGhostHunter\FlowSdk\Resources\SubscriptionResource
     */
    public function subscription()
    {
        if ($subscriptionId = $this->getSubscriptionId()) {
            return $this->performGetSubscription($subscriptionId);
        }
        return false;
    }

    /**
     * Subscribes a Customer to a Flow Plan
     *
     * @param array $attributes
     * @return \DarkGhostHunter\FlowSdk\Resources\BasicResource|bool
     */
    public function subscribe(array $attributes)
    {
        if ($this->flowSubscription()->doesntExist()) {
            return $this->performSubscribe($attributes);
        }
        return false;
    }

    /**
     * Subscribes using the Credit Card
     *
     * @param array $attributes
     * @return bool|\DarkGhostHunter\FlowSdk\Resources\BasicResource
     */
    public function subscribeWithCard(array $attributes)
    {
        if ($this->hasCard()) {
            return $this->subscribe($attributes);
        }
        return false;
    }

    /**
     * Unsubscribe the Customer from his subscription at the end of its cycle
     *
     * @return bool|\DarkGhostHunter\FlowSdk\Resources\BasicResource
     */
    public function unsubscribe()
    {
        if ($subscriptionId = $this->getSubscriptionId()) {
            return $this->performUnsubscribe($subscriptionId);
        }
        return false;
    }

    /**
     * Immediately unsubscribe the Customer from his subscription
     *
     * @return bool|\DarkGhostHunter\FlowSdk\Resources\BasicResource
     */
    public function unsubscribeNow()
    {
        if ($subscriptionId = $this->getSubscriptionId()) {
            return $this->performUnsubscribe($subscriptionId, true);
        }
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Coupon Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Attaches a Coupon to the subscription
     *
     * @param string $couponId
     * @return bool|\DarkGhostHunter\FlowSdk\Resources\BasicResource
     */
    public function attachCoupon(string $couponId)
    {
        $subscription = $this->flowSubscription()->first([
            'subscription_id', 'coupon_id'
        ]);

        if ($subscription && !$subscription->hasCoupon()) {
            return $this->performAttachCoupon($subscription->subscription_id, $couponId);
        }

        return false;
    }

    /**
     * Attaches or Replaces the Coupon in the Subscription
     *
     * @param string $couponId
     * @return bool|\DarkGhostHunter\FlowSdk\Resources\BasicResource
     */
    public function attachOrReplaceCoupon(string $couponId)
    {
        if ($subscriptionId = $this->getSubscriptionId()) {
            return $this->performAttachCoupon($subscriptionId, $couponId);
        }
        return false;
    }

    /**
     * Detach a Coupon from the subscription
     *
     * @return bool|\DarkGhostHunter\FlowSdk\Resources\BasicResource
     */
    public function detachCoupon()
    {
        $subscription = $this->flowSubscription()->first([
            'subscription_id', 'coupon_id'
        ]);

        if ($subscription && $subscription->hasCoupon()) {
            return $this->performDetachCoupon($subscription->subscription_id);
        }
        return false;
    }

}