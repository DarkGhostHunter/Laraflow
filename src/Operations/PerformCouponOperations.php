<?php

namespace DarkGhostHunter\Laraflow\Operations;

trait PerformCouponOperations
{
    /**
     * Performs the coupon attachment
     *
     * @param string $subscriptionId
     * @param string $couponId
     * @return \DarkGhostHunter\FlowSdk\Resources\BasicResource
     */
    protected function performAttachCoupon(string $subscriptionId, string $couponId)
    {
        $subscription = \FlowSubscription::addCoupon($subscriptionId, $couponId);

        $this->returnFlowSubscription($subscriptionId)->update([
            'coupon_id' => $couponId
        ]);

        return $subscription;
    }

    /**
     * Performs the coupon detachment
     *
     * @param string $subscriptionId
     * @return mixed
     */
    protected function performDetachCoupon(string $subscriptionId)
    {
        $subscription = \FlowSubscription::removeCoupon($subscriptionId);

        $this->returnFlowSubscription($subscriptionId)->update([
            'coupon_id' => null
        ]);

        return $subscription;
    }
}