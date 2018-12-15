<?php

namespace DarkGhostHunter\Laraflow\Facades;

use DarkGhostHunter\FlowSdk\Services\Payment;
use Illuminate\Support\Facades\Facade;

/**
 * Class FlowPayment
 * @package DarkGhostHunter\Laraflow\Facades
 *
 * @method static \DarkGhostHunter\FlowSdk\Responses\BasicResponse commit(array $attributes)
 * @method static \DarkGhostHunter\FlowSdk\Resources\BasicResource get(string $id, $options = null)
 *
 * @method static \DarkGhostHunter\FlowSdk\Resources\BasicResource getByCommerceOrder(string $id)
 * @method static \DarkGhostHunter\FlowSdk\Resources\BasicResource getByCommerceId(string $id)
 *
 * @method static \DarkGhostHunter\FlowSdk\Responses\BasicResponse commitByEmail(array $attributes)
 */
class FlowPayment extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Payment::class;
    }
}