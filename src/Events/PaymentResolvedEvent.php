<?php

namespace DarkGhostHunter\Laraflow\Events;

use DarkGhostHunter\FlowSdk\Resources\BasicResource;
use Illuminate\Queue\SerializesModels;

class PaymentResolvedEvent
{
    use SerializesModels;

    /** @var BasicResource  */
    public $payment;

    /**
     * Create a new event instance.
     *
     * @param BasicResource $payment
     */
    public function __construct(BasicResource $payment)
    {
        $this->payment = $payment;
    }
}