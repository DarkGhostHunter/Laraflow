<?php

namespace DarkGhostHunter\Laraflow\Facades;

use DarkGhostHunter\FlowSdk\Services\Settlement;
use Illuminate\Support\Facades\Facade;

/**
 * Class FlowSettlement
 * @package DarkGhostHunter\Laraflow\Facades
 *
 * @method static \DarkGhostHunter\FlowSdk\Resources\BasicResource getByDate(string $date)
 * @method static \DarkGhostHunter\FlowSdk\Resources\BasicResource get(string $id, $options = null)
 */
class FlowSettlement extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Settlement::class;
    }
}