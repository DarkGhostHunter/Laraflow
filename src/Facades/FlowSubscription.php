<?php

namespace DarkGhostHunter\Laraflow\Facades;

use DarkGhostHunter\FlowSdk\Services\Subscription;
use Illuminate\Support\Facades\Facade;

/**
 * Class FlowSubscription
 * @package DarkGhostHunter\Laraflow\Facades
 *
 * @method static \DarkGhostHunter\FlowSdk\Resources\SubscriptionResource create(array $attributes)
 * @method static \DarkGhostHunter\FlowSdk\Resources\SubscriptionResource get(string $id, $options = null)
 * @method static \DarkGhostHunter\FlowSdk\Resources\SubscriptionResource update($id, ...$attributes)
 * @method static \DarkGhostHunter\FlowSdk\Responses\PagedResponse getPage(int $page, array $options = null)
 *
 * @method static \DarkGhostHunter\FlowSdk\Resources\SubscriptionResource cancel(string $id, bool $atPeriodEnd)
 * @method static \DarkGhostHunter\FlowSdk\Resources\SubscriptionResource addCoupon(string $subscriptionId, string $couponId)
 * @method static \DarkGhostHunter\FlowSdk\Resources\SubscriptionResource removeCoupon(string $subscriptionId)
 *
 */
class FlowSubscription extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Subscription::class;
    }
}