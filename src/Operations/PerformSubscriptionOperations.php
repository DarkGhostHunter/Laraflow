<?php

namespace DarkGhostHunter\Laraflow\Operations;

trait PerformSubscriptionOperations
{
    /**
     * Performs the retrieval of the Subscription
     *
     * @param string $subscriptionId
     * @return \DarkGhostHunter\FlowSdk\Resources\SubscriptionResource
     */
    protected function performGetSubscription(string $subscriptionId)
    {
        return \FlowSubscription::get($subscriptionId);
    }

    /**
     * Performs the Subscription
     *
     * @param array $attributes
     * @return \DarkGhostHunter\FlowSdk\Resources\SubscriptionResource
     */
    protected function performSubscribe(array $attributes)
    {
        $subscription = \FlowSubscription::create(
            array_merge($attributes, [
                'customerId' => $this->flow_customer_id
            ])
        );

        if ($subscription->exists()) {

            $this->returnFlowSubscription()
                ->create([
                    'subscription_id' => $subscription->subscriptionId,
                    'plan_id' => $subscription->planId,
                    'trial_starts_at' => $subscription->trial_start,
                    'trial_ends_at' => $subscription->trial_end,
                    'starts_at' => $subscription->period_start,
                    'ends_at' => $subscription->period_end,
                ]);

        }
        return $subscription;
    }

    /**
     * Performs the Subscription Update
     *
     * @param string $subscriptionId
     * @param int $trialPeriodDays
     * @return \DarkGhostHunter\FlowSdk\Resources\SubscriptionResource
     */
    public function performUpdateSubscription(string $subscriptionId, int $trialPeriodDays)
    {
        $flowSubscription = \FlowSubscription::update($subscriptionId, [
            'trial_period_days' => $trialPeriodDays
        ]);

        if ($flowSubscription->exists()) {
            $this->returnFlowSubscription()->update([
                'trial_ends_at' => $flowSubscription->trial_end,
                'starts_at' => $flowSubscription->period_start,
                'ends_at' => $flowSubscription->period_end,
            ]);
        }

        return $flowSubscription;
    }

    /**
     * Performs the Unsubscribe
     *
     * @param string $subscriptionId
     * @param bool $now
     * @return \DarkGhostHunter\FlowSdk\Resources\SubscriptionResource
     */
    protected function performUnsubscribe(string $subscriptionId, bool $now = false)
    {
        $subscription = \FlowSubscription::cancel($subscriptionId, $now);

        if (!$subscription->exists()) {
            $this->returnFlowSubscription($subscriptionId)->delete();
        }

        return $subscription;
    }
}