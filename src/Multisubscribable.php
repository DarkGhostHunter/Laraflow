<?php

namespace DarkGhostHunter\Laraflow;

/**
 * Trait Subscribable
 *
 * This trait uses makes a relationship with a multiple subscriptions.
 *
 * @package DarkGhostHunter\Laraflow
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin Billable
 */
trait Multisubscribable
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function flowSubscriptions()
    {
        return $this->hasMany(
            'DarkGhostHunter\Laraflow\Models\FlowSubscription',
            'flow_customer_id', 'flow_customer_id'
        );
    }

    /**
     * Updates the Flow Subscription
     *
     * @param string $subscriptionId
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function returnFlowSubscription(string $subscriptionId = null)
    {
        return $this->flowSubscriptions()
            ->when($subscriptionId, function ($query, $value) {
                /** @var \Illuminate\Database\Eloquent\Builder $query */
                return $query->where('subscription_id', $value);
            });
    }

    /**
     * Return if the Customer has at least one matching subscription
     *
     * @param string|array $subscriptionId
     * @return bool
     */
    public function hasSubscriptions($subscriptionId = null)
    {
        return $this->flowSubscriptions()
            ->when($subscriptionId, function ($query, $value) {
                /** @var \Illuminate\Database\Eloquent\Builder $query */
                if (is_array($value)) {
                    return $query->whereIn('subscription_id', $value);
                }
                return $query->where('subscription_id', (string)$value);
            })
            ->exists();
    }

    /**
     * Return if the Customer is not subscribed
     *
     * @param string|array $subscriptionId
     * @return bool
     */
    public function doesntHaveSubscriptions($subscriptionId = null)
    {
        return !$this->hasSubscriptions($subscriptionId);
    }

    /**
     * Returns if the Model has one particular Subscription
     *
     * @param string $subscriptionId
     * @return bool
     */
    public function hasSubscription(string $subscriptionId)
    {
        return $this->hasSubscriptions($subscriptionId);
    }

    /**
     * Returns if the Model doesn't have one particular Subscription
     *
     * @param string $subscriptionId
     * @return bool
     */
    public function doesntHaveSubscription(string $subscriptionId)
    {
        return !$this->hasSubscriptions($subscriptionId);
    }

    /**
     * Return if the Customer is subscribed to a plan
     *
     * @param string $planId
     * @return bool
     */
    public function isSubscribedTo(string $planId)
    {
        return $this->flowSubscriptions()->where('plan_id', $planId)->exists();
    }

    /**
     * Return if the Customer is not subscribed to a plan
     *
     * @param string $planId
     * @return bool
     */
    public function isNotSubscribedTo(string $planId)
    {
        return !$this->isSubscribedTo($planId);
    }

    /*
    |--------------------------------------------------------------------------
    | Subscription Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Get a single Subscription
     *
     * @param string $subscriptionId
     * @return bool|\DarkGhostHunter\FlowSdk\Resources\SubscriptionResource
     */
    public function subscription(string $subscriptionId)
    {
        if ($this->hasSubscription($subscriptionId)) {
            return $this->performGetSubscription($subscriptionId);
        }
        return false;
    }

    /**
     * Returns the Subscriptions for a particular Plan Id
     *
     * @param string $planId
     * @return bool|\Illuminate\Support\Collection
     */
    public function subscriptionsForPlanId(string $planId)
    {
        if ($this->isSubscribedTo($planId) && $customerName = $this->getCustomerName()) {
            return $this->performGetSubscriptions($planId, $customerName);
        }
        return false;
    }

    /**
     * Returns all Customer Subscriptions
     *
     * @return \Illuminate\Support\Collection|bool
     */
    public function subscriptions()
    {
        if ($customerName = $this->getCustomerName()) {

            $plans = $this->flowSubscriptions()
                ->groupBy(['plan_id'])
                ->get(['flow_customer_id', 'plan_id', 'subscription_id']);

            // The most elegant approach to get all the subscriptions from Flow is
            // getting a large page for each unique "planId" subscribed and the
            // customer name, merge every page between every plan Id.
            if ($plans->isNotEmpty()) {

                $collection = collect();

                $plans->each(function ($plan) use ($collection, $customerName) {
                    foreach ($this->performGetSubscriptions($plan->plan_id, $customerName)
                             as $flowSubscription) {
                        $collection->push($flowSubscription);
                    }
                });

                return $collection;
            }
        }
        return false;
    }

    /**
     * Get all the Subscriptions
     *
     * @param string $planId
     * @param string $customerName
     * @return \Illuminate\Support\Collection
     */
    protected function performGetSubscriptions(string $planId, string $customerName)
    {
        $subscriptions = \FlowSubscription::getPage(1, [
            'planId' => $planId,
            'limit' => 100,
            'start' => 0,
            'filter' => $customerName,
            'status' => 1
        ]);

        return collect($subscriptions->items);
    }

    /**
     * Subscribes a Customer to a Flow Plan which hasn't subscribed
     *
     * @param array $attributes
     * @return \DarkGhostHunter\FlowSdk\Resources\BasicResource|bool
     */
    public function subscribe(array $attributes)
    {
        // Check first if the user is subscribing to the same plan.
        if ($this->isNotSubscribedTo($attributes['planId'])) {
            return $this->performSubscribe($attributes);
        }
        return false;
    }

    /**
     * Forcefully subscribes the customer to a Plan
     *
     * @param array $attributes
     * @return \DarkGhostHunter\FlowSdk\Resources\BasicResource
     */
    public function forceSubscribe(array $attributes)
    {
        return $this->performSubscribe($attributes);
    }

    /**
     * Unsubscribe the Customer at the end of its cycle
     *
     * @param string $subscriptionId
     * @return bool|\DarkGhostHunter\FlowSdk\Resources\BasicResource
     */
    public function unsubscribe(string $subscriptionId)
    {
        if ($this->hasSubscription($subscriptionId)) {
            return $this->performUnsubscribe($subscriptionId);
        }
        return false;
    }

    /***
     * Forcefully unsubscribes a Customer at the end of his cycle
     *
     * @param string $subscriptionId
     * @return \DarkGhostHunter\FlowSdk\Resources\SubscriptionResource
     */
    public function forceUnsubscribe(string $subscriptionId)
    {
        return $this->performUnsubscribe($subscriptionId);
    }

    /**
     * Immediately unsubscribe the Customer from his subscription
     *
     * @param string $subscriptionId
     * @return bool|\DarkGhostHunter\FlowSdk\Resources\BasicResource
     */
    public function unsubscribeNow(string $subscriptionId)
    {
        if ($this->hasSubscription($subscriptionId)) {
            return $this->performUnsubscribe($subscriptionId, true);
        }
        return false;
    }

    /**
     * Forcefully unsubscribe a Customer immediately
     *
     * @param string $subscriptionId
     * @return \DarkGhostHunter\FlowSdk\Resources\SubscriptionResource
     */
    public function forceUnsubscribeNow(string $subscriptionId)
    {
        return $this->performUnsubscribe($subscriptionId, true);
    }

    /*
    |--------------------------------------------------------------------------
    | Coupon Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Attaches a Coupon to the subscription
     *
     * @param string $subscriptionId
     * @param string $couponId
     * @return bool|\DarkGhostHunter\FlowSdk\Resources\BasicResource
     */
    public function attachCoupon(string $subscriptionId, string $couponId)
    {
        $subscription = $this->flowSubscriptions()
            ->where('subscription_id', $subscriptionId)
            ->first([
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
     * @param string $subscriptionId
     * @param string $couponId
     * @return bool|\DarkGhostHunter\FlowSdk\Resources\BasicResource
     */
    public function attachOrReplaceCoupon(string $subscriptionId, string $couponId)
    {
        if ($this->hasSubscription($subscriptionId)) {
            return $this->performAttachCoupon($subscriptionId, $couponId);
        }
        return false;
    }

    /**
     * Detach a Coupon from the subscription
     *
     * @param string $subscriptionId
     * @return bool|\DarkGhostHunter\FlowSdk\Resources\BasicResource
     */
    public function detachCoupon(string $subscriptionId)
    {
        if ($this->hasSubscription($subscriptionId)) {
            return $this->performDetachCoupon($subscriptionId);
        }
        return false;
    }

}