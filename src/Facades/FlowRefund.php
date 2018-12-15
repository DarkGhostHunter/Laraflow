<?php

namespace DarkGhostHunter\Laraflow\Facades;

use DarkGhostHunter\FlowSdk\Services\Refund;
use Illuminate\Support\Facades\Facade;

/**
 * Class FlowRefund
 * @package DarkGhostHunter\Laraflow\Facades
 *
 * @method static \DarkGhostHunter\FlowSdk\Resources\BasicResource create(array $attributes)
 * @method static \DarkGhostHunter\FlowSdk\Resources\BasicResource get(string $id, $options = null)
 *
 */
class FlowRefund extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Refund::class;
    }
}