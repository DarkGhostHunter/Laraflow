<?php

namespace DarkGhostHunter\Laraflow\Facades;

use DarkGhostHunter\FlowSdk\Services\Invoice;
use Illuminate\Support\Facades\Facade;

/**
 * Class FlowInvoice
 * @package DarkGhostHunter\Laraflow\Facades
 *
 * @method static \DarkGhostHunter\FlowSdk\Resources\InvoiceResource get(string $id, $options = null)
 */
class FlowInvoice extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Invoice::class;
    }
}