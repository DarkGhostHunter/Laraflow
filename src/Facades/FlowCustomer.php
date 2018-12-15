<?php

namespace DarkGhostHunter\Laraflow\Facades;

use DarkGhostHunter\FlowSdk\Services\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;

/**
 * Class FlowCustomer
 * @package DarkGhostHunter\Laraflow\Facades
 *
 * @method static \DarkGhostHunter\FlowSdk\Resources\CustomerResource create(array $attributes)
 * @method static \DarkGhostHunter\FlowSdk\Resources\CustomerResource get(string $id, $options = null)
 * @method static \DarkGhostHunter\FlowSdk\Resources\CustomerResource update($id, ...$attributes)
 * @method static \DarkGhostHunter\FlowSdk\Resources\CustomerResource delete(string $id)
 * @method static \DarkGhostHunter\FlowSdk\Responses\PagedResponse getPage(int $page, array $options = null)
 *
 * @method static \DarkGhostHunter\FlowSdk\Responses\BasicResponse registerCard(string $customerId, string $urlReturn = null)
 * @method static \DarkGhostHunter\FlowSdk\Resources\CustomerResource getCard(string $token)
 * @method static \DarkGhostHunter\FlowSdk\Resources\CustomerResource unregisterCard(string $customerId)
 * @method static \DarkGhostHunter\FlowSdk\Resources\BasicResource createCharge(array $attributes)
 * @method static \DarkGhostHunter\FlowSdk\Responses\BasicResponse reverseCharge(string $idType, string $value)
 *
 * @method static \DarkGhostHunter\FlowSdk\Responses\PagedResponse getChargesPage(string $customerId, int $page, array $options = null)
 *
 */
class FlowCustomer extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Customer::class;
    }
}