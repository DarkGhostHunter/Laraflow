<?php
($router = $this->app->make('router'))->prefix(\DarkGhostHunter\Laraflow\FlowHelpersServiceProvider::WEBHOOK_PATH)
    ->namespace('DarkGhostHunter\Laraflow\Http\Controllers')
    ->middleware('flow-webhook')
    ->group(function () use ($router) {
        $router->post('payment', 'FlowWebhooksController@payment')->name('flow.webhook.payment');
        $router->post('refund', 'FlowWebhooksController@refund')->name('flow.webhook.refund');
        $router->post('plan', 'FlowWebhooksController@plan')->name('flow.webhook.plan');
    });