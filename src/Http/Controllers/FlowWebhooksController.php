<?php

namespace DarkGhostHunter\Laraflow\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use DarkGhostHunter\Laraflow\Events\PaymentResolvedEvent;
use DarkGhostHunter\Laraflow\Events\RefundResolvedEvent;
use DarkGhostHunter\Laraflow\Events\PlanPaidEvent;
use DarkGhostHunter\FlowSdk\Services\Payment;
use DarkGhostHunter\FlowSdk\Services\Refund;

class FlowWebhooksController extends Controller
{
    /**
     * Notify the Application about the Payment received
     *
     * @param Request $request
     * @return void
     */
    public function payment(Request $request)
    {
        event(new PaymentResolvedEvent(app(Payment::class)->get($request->input('token'))));
    }

    /**
     * Notify the Application about the Refund received
     *
     * @param Request $request
     */
    public function refund(Request $request)
    {
        event(new RefundResolvedEvent(app(Refund::class)->get($request->input('token'))));
    }

    /**
     * Notify the Application about the Plan paid
     *
     * @param Request $request
     */
    public function plan(Request $request)
    {
        event(new PlanPaidEvent(app(Payment::class)->get($request->input('token'))));
    }
}