<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Set explicitly to "production" when you're ready to use Flow for real
    | transactions. Any other value will instruct Flow to use the Sandbox
    | environment. The credentials are different for both environments.
    |
    */

    'environment' => env('FLOW_ENV', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    |
    | Put here your credentials. The "production" credentials are different
    | from the "sandbox", so be sure to use the ones for the environment.
    | You can use dummy ones until you get the proper ones from Flow.
    |
    */

    'credentials' => [
        'apiKey' => env('FLOW_API_KEY'),
        'secret' => env('FLOW_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Return URLs
    |--------------------------------------------------------------------------
    |
    | Return URLs will be appended by default to all the transaction that use
    | them. This saves you some line of code for pure convenience, and can
    | be overridden after you make them. Leave them empty if otherwise.
    |
    */

    'returns' => [
        'payment.urlReturn' => null, // https://myapp.com/flow/refund
        'card.url_return'   => null, // https://myapp.com/flow/card
    ],


    /*
    |--------------------------------------------------------------------------
    | Webhooks URLs
    |--------------------------------------------------------------------------
    |
    | Webhooks URLs can also be appended by default to these transactions.
    | Flow will use them to notify different events in Flow servers. You
    | will need to set proper logic to receive them and process them.
    |
    */

    'webhooks' => [
        'payment.urlConfirmation'   => null, // https://app.com/webhook/payment
        'refund.urlCallBack'        => null, // https://app.com/webhook/refund
        'plan.urlCallback'          => null, // https://app.com/webhook/plan
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    |
    | To protect your exposed endpoint, you can enable a secret string to be
    | appended to the Webhooks URLs you declare in your transactions. This
    | will be appended automatically when a Transaction is sent to Flow.
    |
    */

    'webhook-secret' => env('FLOW_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Adapter
    |--------------------------------------------------------------------------
    |
    | You can use your own adapter to communicate with your application and
    | Flow servers. Set here the registered Service name to retrieve from
    | the Service Container, or we will use the default Guzzle Adapter.
    |
    */

    'adapter' => null // 'MyAdapter', '\App\HttpAdapter', Adapter::class, etc...


];