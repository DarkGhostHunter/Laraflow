<?php

namespace DarkGhostHunter\Laraflow;

use DarkGhostHunter\FlowSdk\Resources\CustomerResource;
use DarkGhostHunter\Laraflow\Observers\BillableObserver;
use DarkGhostHunter\Laraflow\Facades\FlowCustomer;

/**
 * Trait Billable
 * @package DarkGhostHunter\Laraflow
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 *
 * @property string flow_customer_id
 * @property string flow_card_brand
 * @property string flow_card_last_four
 *
 */
trait Billable
{
    /*
    |--------------------------------------------------------------------------
    | Boot
    |--------------------------------------------------------------------------
    */

    /**
     * Boot the Trait
     *
     * @return void
     */
    public static function bootBillable()
    {
        // Add the Observer
        static::observe(BillableObserver::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Static Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Run the given callable while being without events
     *
     * @param callable $callback
     * @return mixed
     */
    public static function silenced(callable $callback)
    {
        $events = static::getEventDispatcher();

        static::unsetEventDispatcher();

        $result = $callback();

        static::setEventDispatcher($events);

        return $result;
    }

    /**
     * Run the given callable without events or guarded attributes
     *
     * @param callable $callback
     * @return mixed
     */
    public static function silencedAndUnguarded(callable $callback)
    {
        return self::unguarded(function () use ($callback) {
            return self::silenced($callback);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Keys for Customer
    |--------------------------------------------------------------------------
    */


    /**
     * The Customer Name Key
     *
     * @return string
     */
    public function getCustomerNameKey() : string
    {
        return 'name';
    }

    /**
     * Returns the Customer Name
     *
     * @return string
     */
    protected function getCustomerName()
    {
        return $this->{$this->getCustomerNameKey()};
    }

    /**
     * The Customer Email Key
     *
     * @return string
     */
    public function getCustomerEmailKey() : string
    {
        return 'email';
    }

    /**
     * Return the Customer Email
     *
     * @return mixed
     */
    protected function getCustomerEmail()
    {
        return $this->{$this->getCustomerEmailKey()};
    }

    /**
     * Returns the External ID to identify this billable model
     *
     * @return string
     */
    public function getCustomerExternalId()
    {
        return $this->getTable() . '|' . $this->getKeyName() . '|' . $this->getKey();
    }

    /**
     * Returns if the model should create Customer on Flow when `created`
     *
     * @return bool|null
     */
    public function getSyncOnCreate()
    {
        return $this->syncOnCreate;
    }

    /*
    |--------------------------------------------------------------------------
    | Existance helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Returns if the Model has a Customer registered locally
     *
     * @return bool
     */
    public function hasCustomer()
    {
        return (bool)$this->flow_customer_id;
    }

    /**
     * Returns if the Model doesn't has a Customer registered locally
     *
     * @return bool
     */
    public function doesntHasCustomer()
    {
        return !$this->hasCustomer();
    }

    /**
     * Returns if the Model has a Credit Card registered
     *
     * @return bool
     */
    public function hasCard()
    {
        return (bool)$this->flow_card_brand;
    }

    /**
     * Returns if the Model doesn't has a Credit Card registered
     *
     * @return bool
     */
    public function doesntHasCard()
    {
        return !$this->hasCard();
    }


    /*
    |--------------------------------------------------------------------------
    | Customer Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Returns the Customer in Flow
     *
     * @return \DarkGhostHunter\FlowSdk\Resources\CustomerResource|bool
     */
    public function getCustomer()
    {
        if ($this->hasCustomer()) {
            return FlowCustomer::get($this->flow_customer_id);
        }
        return false;
    }

    /**
     * Creates a Customer on Flow
     *
     * @param array $attributes
     * @return \DarkGhostHunter\FlowSdk\Resources\CustomerResource|bool
     */
    public function createCustomer(array $attributes = [])
    {
        if (!$this->hasCustomer()) {
            return $this->forceCreateCustomer($attributes);
        }
        return false;
    }

    /**
     * Forces creating a Customer on Flow
     *
     * @param array $attributes
     * @return \DarkGhostHunter\FlowSdk\Resources\CustomerResource
     */
    public function forceCreateCustomer(array $attributes = [])
    {
        return $this->performCreateCustomer(array_merge([
            'name' => $this->getCustomerName(),
            'email' => $this->getCustomerEmail(),
            'externalId' => $this->getCustomerExternalId()
        ], $attributes));
    }

    /**
     * Performs the creation of the Customer on Flow
     *
     * @param array $attributes
     * @return \DarkGhostHunter\FlowSdk\Resources\CustomerResource
     */
    protected function performCreateCustomer(array $attributes)
    {
        $customer = FlowCustomer::create($attributes);

        $this->silencedAndUnguarded(function () use ($customer) {
            $this->update([
                'flow_customer_id' => $customer->customerId,
                'flow_card_brand' => $customer->creditCardType,
                'flow_card_last_four' => $customer->last4CardDigits,
            ]);
        });

        return $customer;
    }

    /**
     * Updates a Customer on Flow
     *
     * @return \DarkGhostHunter\FlowSdk\Resources\CustomerResource|bool
     */
    public function updateCustomer()
    {
        if ($this->hasCustomer()) {
            return $this->forceUpdateCustomer();
        }
        return false;
    }

    /**
     * Forcefully updates a Customer on Flow
     *
     * @return \DarkGhostHunter\FlowSdk\Resources\CustomerResource
     */
    public function forceUpdateCustomer()
    {
        return $this->performUpdateCustomer([
            'name' => $this->getCustomerName(),
            'email' => $this->getCustomerEmail(),
            'externalId' => $this->getCustomerExternalId(),
        ]);
    }

    /**
     * Performs updating the Customer on Flow
     *
     * @param array $attributes
     * @return \DarkGhostHunter\FlowSdk\Resources\CustomerResource
     */
    protected function performUpdateCustomer(array $attributes)
    {
        return FlowCustomer::update(
            $this->flow_customer_id,
            $attributes
        );
    }

    /**
     * Delete the Customer in Flow
     *
     * @return \DarkGhostHunter\FlowSdk\Resources\CustomerResource|bool
     */
    public function deleteCustomer()
    {
        if ($this->hasCustomer()) {
            return $this->forceDeleteCustomer();
        }
        return false;
    }

    /**
     * Forcefully deletes a Customer in flow
     *
     * @return CustomerResource
     */
    public function forceDeleteCustomer()
    {
        return $this->performDeleteCustomer();
    }


    /**
     * Performs deleting the Customer on Flow
     *
     * @return \DarkGhostHunter\FlowSdk\Resources\CustomerResource
     */
    protected function performDeleteCustomer()
    {
        return FlowCustomer::delete($this->flow_customer_id);
    }

    /*
    |--------------------------------------------------------------------------
    | Card Operations
    |--------------------------------------------------------------------------
    */
    /**
     * Creates a petition to Register a Credit Card in Flow 
     * 
     * @return \DarkGhostHunter\FlowSdk\Responses\BasicResponse|bool
     */
    public function registerCard()
    {
        if ($this->hasCustomer() && $this->doesntHasCard()) {
            return $this->forceRegisterCard();
        }
        return false;
    }

    /**
     * Forcefully register a Credit Card
     *
     * @return \DarkGhostHunter\FlowSdk\Responses\BasicResponse
     */
    public function forceRegisterCard()
    {
        return FlowCustomer::registerCard($this->flow_customer_id);
    }

    /**
     * Updates the Customer Card Information locally from Flow
     *
     * @param \DarkGhostHunter\FlowSdk\Resources\CustomerResource $resource
     * @return bool
     */
    public function syncCard(CustomerResource $resource)
    {
        return $this->silencedAndUnguarded(function () use ($resource) {
            return $this->update([
                'flow_customer_id' => $resource->customerId,
                'flow_card_brand' => $resource->creditCardType,
                'flow_card_last_four' => $resource->last4CardDigits,
            ]);

        });
    }

    /**
     * Removes a Credit Card from the Customer in Flow
     *
     * @return \DarkGhostHunter\FlowSdk\Resources\CustomerResource|bool
     */
    public function removeCard()
    {
        if ($this->hasCustomer() && $this->hasCard()) {
            return $this->forceRemoveCard();
        }
        return false;
    }

    /**
     * @return CustomerResource
     */
    public function forceRemoveCard()
    {
        return $this->performRemoveCard();
    }

    /**
     * Performs the Credit Card removal
     *
     * @return \DarkGhostHunter\FlowSdk\Resources\CustomerResource
     */
    protected function performRemoveCard()
    {
        $customer = FlowCustomer::unregisterCard($this->flow_customer_id);

        if (!$customer->creditCardType && !$customer->last4CardDigits) {
            $this->silencedAndUnguarded(function () {
                $this->update([
                    'flow_card_brand' => null,
                    'flow_card_last_four' => null,
                ]);
            });
        }

        return $customer;
    }

    /*
    |--------------------------------------------------------------------------
    | Charge Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Charges an amount to the Customer's Credit Card (or Email)
     *
     * @param array $attributes
     * @return bool|\DarkGhostHunter\FlowSdk\Resources\BasicResource
     */
    public function charge(array $attributes)
    {
        if ($this->hasCustomer()) {
            return $this->performCharge($attributes);
        }
        return false;
    }

    /**
     * Charges the user to his Credit Card only if its has one
     *
     * @param array $attributes
     * @return bool|\DarkGhostHunter\FlowSdk\Resources\BasicResource
     */
    public function chargeToCard(array $attributes)
    {
        if ($this->hasCard()) {
            return $this->charge($attributes);
        }
        return false;
    }

    /**
     * Forcefully charges an amount to the Customer, by Credit Card or Email
     *
     * @param array $attributes
     * @return \DarkGhostHunter\FlowSdk\Resources\BasicResource
     */
    public function forceCharge(array $attributes)
    {
        return $this->performCharge($attributes);
    }

    /**
     * Performs a Charge to the Customer
     *
     * @param array $attributes
     * @return \DarkGhostHunter\FlowSdk\Resources\BasicResource
     */
    protected function performCharge(array $attributes)
    {
        return FlowCustomer::createCharge(array_merge($attributes, [
            'customerId' => $this->flow_customer_id
        ]));
    }

}