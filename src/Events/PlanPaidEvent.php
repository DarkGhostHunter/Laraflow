<?php

namespace DarkGhostHunter\Laraflow\Events;

use DarkGhostHunter\FlowSdk\Resources\BasicResource;
use Illuminate\Queue\SerializesModels;

class PlanPaidEvent
{
    use SerializesModels;

    /** @var BasicResource  */
    public $invoice;

    /**
     * Create a new event instance.
     *
     * @param BasicResource $invoice
     */
    public function __construct(BasicResource $invoice)
    {
        $this->invoice = $invoice;
    }
}