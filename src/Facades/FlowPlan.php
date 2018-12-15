<?php

namespace DarkGhostHunter\Laraflow\Facades;

use DarkGhostHunter\FlowSdk\Services\Plan;
use Illuminate\Support\Facades\Facade;

/**
 * Class FlowPlan
 * @package DarkGhostHunter\Laraflow\Facades
 *
 * @method static \DarkGhostHunter\FlowSdk\Resources\BasicResource create(array $attributes)
 * @method static \DarkGhostHunter\FlowSdk\Resources\BasicResource get(string $id, $options = null)
 * @method static \DarkGhostHunter\FlowSdk\Resources\BasicResource update($id, ...$attributes)
 * @method static \DarkGhostHunter\FlowSdk\Resources\BasicResource delete(string $id)
 * @method static \DarkGhostHunter\FlowSdk\Responses\PagedResponse getPage(int $page, array $options = null)
 */
class FlowPlan extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Plan::class;
    }
}