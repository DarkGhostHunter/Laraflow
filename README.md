![Laraflow](https://i.imgur.com/ITc1gc6.png)

[![Latest Stable Version](https://poser.pugx.org/darkghosthunter/laraflow/v/stable)](https://packagist.org/packages/darkghosthunter/laraflow) [![License](https://poser.pugx.org/darkghosthunter/laraflow/license)](https://packagist.org/packages/darkghosthunter/laraflow)
![](https://img.shields.io/packagist/php-v/darkghosthunter/laraflow.svg)
 [![Build Status](https://travis-ci.com/DarkGhostHunter/Laraflow.svg?branch=master)](https://travis-ci.com/DarkGhostHunter/Laraflow) [![Coverage Status](https://coveralls.io/repos/github/DarkGhostHunter/Laraflow/badge.svg?branch=master)](https://coveralls.io/github/DarkGhostHunter/Laraflow?branch=master) [![Maintainability](https://api.codeclimate.com/v1/badges/5c93490206b5d426c842/maintainability)](https://codeclimate.com/github/DarkGhostHunter/Laraflow/maintainability) [![Test Coverage](https://api.codeclimate.com/v1/badges/5c93490206b5d426c842/test_coverage)](https://codeclimate.com/github/DarkGhostHunter/Laraflow/test_coverage)


# Laraflow

This package lets you use the unofficial [Flow SDK](https://github.com/DarkGhostHunter/FlowSdk) with Laravel.

> This package is compatible with [Laravel Cashier](https://laravel.com/docs/master/billing), so you can use both.

## Requirements

* PHP >= 7.1.3
* Laravel 5.8

> Check older releases for older Laravel versions.

## Installation

Just fire up Composer and require it into your project.

```bash
composer require darkghosthunter/laraflow
```

### Database Preparation

If you want to use the `Billable` trait to charge customers, along with the `Subscribable` or `Multisubscribable` traits for Flow Subscriptions, you will need to run these database migrations:

```php
<?php

// To enable Billable trait methods
Schema::table('users', function (Blueprint $table) {
    $table->string('flow_customer_id')->nullable();
    $table->string('flow_card_brand')->nullable();
    $table->string('flow_card_last_four')->nullable();
});

// To enable Subscriptable/Multisubscribable traits method
Schema::create('subscriptions', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('flow_customer_id');
    $table->string('subscription_id');
    $table->string('plan_id');
    $table->string('coupon_id')->nullable();
    $table->date('trial_starts_at')->nullable();
    $table->date('trial_ends_at')->nullable();
    $table->date('starts_at')->nullable();
    $table->date('ends_at')->nullable();
    $table->timestamps();
});
```

These migrations are available in `database/migrations`. **These are deactivated by default**, but you can activate them if you [publish the config file](#configuration):

```php
<?php 

return [
    
    // ...
    
    'migrations' => true,
];
```

The point of these migrations is to sync the Customer ID, Subscription ID and the local data. 

It's not necessary to run them if you are not planning to use these traits, so you can let this config on `false`, which is the default.

## Configuration

To start using Flow with this package, just set three keys in your `.env` file: the environment, your API Key and Secret.

```dotenv
APP_NAME=Laravel
APP_ENV=local

#...

FLOW_ENV=sandbox
FLOW_API_KEY=1F90971E-8276-4713-97FF-2BLF5091EE3B
FLOW_SECRET=f8b45f9b8bcdb5702dc86a1b894492303741c405
```

Additionally, you can publish the config file `flow.php` if you want to set defaults or some other advanced settings.

```bash
php artisan vendor:publish --provider="DarkGhostHunter\Laraflow\FlowHelpersServiceProvider"
``` 

## Deactivating CSRF

Since Flow issues POST Requests to your site using the user's browser, CSRF must be deactivated in these routes:

* `payment.urlReturn`
* `card.url_return`

These POST Requests have a `token` comprised of 40 random characters identifying the transaction. Consider using `ThrottleRequests` Middleware to avoid [brute force attacks](https://en.wikipedia.org/wiki/Brute-force_attack).

```php
<?php

Route::post('flow/return/payment')
    ->uses('PaymentController@status')
    ->middleware('throttle:20,1'); // 20 attempts every 1 minute.
```

## What's inside?

A lot of goodies:

* [Facades](#facades)
* [Billable trait](#billable)
* [Subscribable trait](#subscribable)
* [Multisubscribable trait](#multisubscribable)
* [Notifications Events](#notifications-events)
* [Webhook protection](#webhook-protection)
* [Logging out-of-the-box](#logging)
* [Third-party Http Client compatibility](#custom-adapter)
* [Deferred Service Provider](#service-providers)

### Facades

You get Facades for all Flow Services:

| Service | Facade
|---|---|
| Coupon | FlowCoupon
| Customer | FlowCustomer
| Invoice | FlowInvoice
| Payment | FlowPayment
| Plan | FlowPlan
| Refund | FlowRefund
| Settlement | FlowSettlement
| Subscription | FlowSubscription

All the services receive main Flow instance from the Service Container. So you can easily make something like this:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use FlowPayment;

class PaymentController extends Controller
{
    /**
     * Make a Payment.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function pay(Request $request)
    {
        // Validate, check items, ...
        
        // Finally, create the Payment
        $response = FlowPayment::commit([
            'commerceOrder' => 'MyOrder1',
            'subject' => 'Game Console',
            'email' => 'johndoe@email.com',
            'amount' => 9900,
            'urlConfirmation' => 'https://myapp.com/webhooks/payment',
            'urlReturn' => 'https://myapp.com/payment/return',
        ]);

        return redirect($response->getUrl());
    }
    
}
```

Refer to the [Flow SDK Wiki](https://github.com/DarkGhostHunter/FlowSdk/wiki/Services) to see how to use each Service. Just replace `$flow->{service}()` by the Facade of your choosing.

### Billable

You can hook up charges to a user's Credit Card, or Email if he doesn't have one registered, through the `Billable` trait.

After you made the `update_users_table` migrations, you will be able to:

* Register as a Customer in Flow
* Delete the Customer in Flow
* Register and Unregister a Credit Card
* Be Charged (Credit Card, or Email in absence).
 

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DarkGhostHunter\Laraflow\Billable;

class User extends Model
{
    use Billable;
    
    // ...
    
}
```

By default, `Billable` won't register your User when is created, allowing you to not clutter up your Flow account with users that won't use billing or subscriptions. You can sync the model creation with the Customer creation in Flow by setting `syncOnCreate` to `true`: 

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DarkGhostHunter\Laraflow\Billable;

class User extends Model
{
    use Billable;
    
    /**
     * Should create a Customer in Flow when created
     *
     * @return bool
     */
    protected $syncOnCreate = true;
}
```

| Method | Description
|---|---
| `hasCustomer()` | If the Model has a Flow Customer
| `hasCard()` | If the Model Credit Card Registered in Flow
| `getCustomer()` | Returns if the Flow Customer
| `createCustomer()` | Creates a Customer in Flow
| `updateCustomer()` | Updates a Customer in Flow using the local data
| `deleteCustomer()` | Deletes a Customer in Flow
| `registerCard()` | Creates a petition to register a Credit Card in Flow
| `syncCard()` | Sync the local card data from Flow
| `removeCard()` | Removes a Credit Card from Flow
| `charge()` | Charge a Customer
| `chargeToCard()` | Charge a Customer only if it has a Credit Card registered

You can always checkout the source code for more handy methods.
 

### Subscribable

To gracefully allow your Users to have subscriptions managed by Flow, add the `Subscribable` trait. This will link a one-to-one subscription to the Customer and User.

After you run the `create_flow_subscriptions` migration, along with this trait, you will be able to:

* Subscribe and Unsubscribe Customers
* Subscribe only if has a Credit Card Registered
* Attach and Detach Coupons from the Subscription

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DarkGhostHunter\Laraflow\Billable;
use DarkGhostHunter\Laraflow\Subscribable;

class User extends Model
{
    use Billable, Subscribable;
    
    // ...
}
```

| Method | Description
|---|---
| `flowSubscription()` | Local Flow Subscription relationship
| `hasSubscription()` | If the Model has a local Subscription 
| `subscription()` | Returns the Flow Subscription
| `subscribe()` | Subscribes the Customer to a Plan
| `subscribeWithCard()` | Subscribes the Customer only if he has a Credit Card registered 
| `updateSubscription()` | Updates the subscription trial days (if possible)
| `unsubscribe()` | Unsubscribes the Customer at the end of his cycle
| `unsubscribeNow()` | Immediately unsubscribes a Customer
| `attachCoupon()` | Attaches a Coupon to the Flow Subscription
| `attachOrReplaceCoupon()` | Attaches or Replaces a Coupon in the Flow Subscription
| `detachCoupon()` | Detaches any Coupon from the Flow Subscription

You can always checkout the source code for more handy methods.

### Multisubscribable

The main difference between this and the `Subscribable` trait is this allows a User to have multiple subscriptions - even on the same plan.

Since this trait uses the same method names, **do not use both `Subscribable` and `Multisubscribable` traits in the same Model**.

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DarkGhostHunter\Laraflow\Billable;
use DarkGhostHunter\Laraflow\Multisubscribable;

class User extends Model
{
    use Billable, Multisubscribable;
    
    // ...
}
```

Because a User can have multiple subscriptions under this trait, for most operations you will have to include the `subscriptionId` where you want to operate.


```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    /**
     * Unsubscribe.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function unsubscribe(Request $request)
    {
        // Validate, etc, ...
        
        // Unsubscribe the user immediately
        $subscription = Auth::user()->unsubscribeNow(
            $request->subscription_id
        );
        
        if ($subscription->status === 4) {
            return view('user.unsubscribed')
                ->with('subscription', $subscription);            
        }

        return view('user.unsubscribe-fail')
                ->with('subscription', $subscription); 
    }
}
```
### Method table

| Method | Description
|---|---
| `flowSubscriptions()` | Local Flow Subscriptions relationship
| `hasSubscriptions()` | If the User has at least one matching subscription
| `isSubscribedTo()` | If the User is subscribed to a Plan
| `subscription()` | Returns the Flow Subscription
| `subscriptions()` | Returns all User's Flow Subscriptions 
| `subscriptionsForPlanId()` | Return the Flow Subscriptions for a Plan Id
| `subscribe()` | Subscribes the Customer to a Plan
| `subscribeWithCard()` | Subscribes the Customer only if he has a Credit Card registered 
| `updateSubscription()` | Updates the subscription trial days (if possible)
| `unsubscribe()` | Unsubscribes the Customer at the end of his cycle
| `unsubscribeNow()` | Immediately unsubscribes a Customer
| `attachCoupon()` | Attaches a Coupon to the Flow Subscription
| `attachOrReplaceCoupon()` | Attaches or Replaces a Coupon in the Flow Subscription
| `detachCoupon()` | Detaches any Coupon from the Flow Subscription

You can always checkout the source code for more handy methods.

### Notifications Events

This package comes with Webhook routes and Events that will fire up when Flow hit these routes with Payments, Refunds and Plans paid:

* `DarkGhostHunter\Laraflow\Events\PaymentResolvedEvent`
* `DarkGhostHunter\Laraflow\Events\RefundResolvedEvent`
* `DarkGhostHunter\Laraflow\Events\PlanPaidEvent`

An Event will fire containing the Payment or a Refund, depending on what Flow has sent.

While this was made to save you time, you can deactivate these routes and use your own with your own logic, with or without firing events, inside the published `flow.php`.

```php
<?php

return [
    
    // ...
    
    'webhooks-defaults' => false,
];
```
 
You can also override the the default for each, if `webhooks-defaults` are set to true:

```php
<?php

return [
    
    // ...
    
    'webhooks' => [
        'payment.urlConfirmation'   => 'https://app.com/my-payment-webhook',
        'refund.urlCallBack'        => null,
        'plan.urlCallback'          => 'https://app.com/my-plan-webhook'
    ],

];
```

> The default Webhook Routes are protected by the `VerifyWebhookMiddleware`, which will validate the secret if there is set.  

### Webhook Protection

Since disabling CSRF will unprotect the endpoints, **it's recommended to add a static Webhook Secret String** so only Flow can reach your application. This string is never visible to the end user.

You can conveniently generate a random string and save it as the Webhook Secret to protect your exposed endpoints: 

```bash
php artisan webhook-secret:generate
```

This will append the secret to your `.env` file automatically, **or replaced** if it already exists. 

```dotenv
#...

FLOW_ENV=sandbox
FLOW_API_KEY=1F90971E-8276-4713-97FF-2BLF5091EE3B
FLOW_SECRET=f8b45f9b8bcdb5702dc86a1b894492303741c405

FLOW_WEBHOOK_SECRET=f9b8bcdb5702dc86a1b8944922d8s8w7
```

After that, add the `VerifyWebhookMiddleware` to the Webhook endpoints. This Middleware ensures the POST Request has a `token` of 40 characters and the same `secret` as configured. When not, it will abort the Request with a 404 Not Found:

```php
<?php

Route::post('my-route/payment','MyFlowController@payment')
    ->middleware('flow-webhook');
```

> If you loaded the middleware but didn't set a Webhook Secret, don't worry. The middleware won't do nothing as it checks first if the Webhook Secret exists before anything.

## Logging

No hassle! This package hooks Flow gracefully into Laravel's default logging system, so no need to change anything.

## Custom Adapter

While Flow SDK uses Guzzle as a default HTTP Client, you can make your own Adapter, implementing the `AdapterInterface` and [registered](https://laravel.com/docs/master/container) inside the [Service Container](https://laravel.com/docs/master/container).

Then, put the name of your registered Adapter in the `adapter` section of your published `flow.php` config file. The Adapter will be resolved when Flow SDK is instantiated.  

```php
<?

return [
    
    // ...
    
    'adapter' => 'App\Http\Flow\MyCurlAdapter',
];
```

This will allow in your test to make a fake Adapter and catch all Requests.

## Service Providers

This package adds Flow SDK into your application as a Service Provider. You can access the Flow object just [pulling it out from the Service Container like you would normally do](https://laravel.com/docs/container#resolving), like using Dependency Injection or just using the `resolve()` helper, etc.

```php
<?php

namespace App;

use DarkGhostHunter\FlowSdk\Flow;
use Illuminate\Http\Request;
use App\Http\Controller;

class ExampleController extends Controller
{
    public function pay(Request $request, Flow $flow)
    {
        if ($flow->isProduction()) {
            // ...
        }
    }
    
    public function customPay()
    {
        $flow = app(Flow::class);
        
        echo $flow->isProduction(); // false..
    }
}
```

On the backstage, this package registers two Services Providers:
 
* `FlowHelpersServiceProvider` loads the configuration, middleware and other helpers.
* `FlowServiceProvider` registers a single instance of Flow inside the Service Container as a [deferred Service Provider](https://laravel.com/docs/providers#deferred-providers), along with the other services. 

This ensures the configuration will be always loaded, and the Flow SDK only when is called in your code.

## License

This package is licenced by the [MIT License](LICENSE).

This package is not related in any way, directly or indirectly, to any of the services, companies, products and/or services referenced in this package.
