<?php

namespace Tests\Models;

use DarkGhostHunter\Laraflow\Models\FlowSubscription;
use Orchestra\Testbench\TestCase;

class FlowSubscriptionModelTest extends TestCase
{

    public function testHasCoupon()
    {
        $subscription = FlowSubscription::make([
            'coupon_id' => 1234
        ]);

        $this->assertTrue($subscription->hasCoupon());
    }
}
