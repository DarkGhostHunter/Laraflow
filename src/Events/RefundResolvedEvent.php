<?php

namespace DarkGhostHunter\Laraflow\Events;

use DarkGhostHunter\FlowSdk\Resources\BasicResource;
use Illuminate\Queue\SerializesModels;

class RefundResolvedEvent
{
    use SerializesModels;

    /** @var BasicResource  */
    public $refund;

    /**
     * Create a new event instance.
     *
     * @param BasicResource $refund
     */
    public function __construct(BasicResource $refund)
    {
        $this->refund = $refund;
    }
}