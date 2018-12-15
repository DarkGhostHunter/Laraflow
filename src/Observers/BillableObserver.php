<?php

namespace DarkGhostHunter\Laraflow\Observers;

use Illuminate\Database\Eloquent\Model;

class BillableObserver
{
    /**
     * Handle the reader "created" event.
     *
     * @param Model & \DarkGhostHunter\Laraflow\Billable $model
     * @return void
     */
    public function created(Model $model)
    {
        if ($model->getSyncOnCreate()) {
            $model->createCustomer();
        }
    }

    /**
     * Handle the reader "updated" event.
     *
     * @param Model & \DarkGhostHunter\Laraflow\Billable $model
     * @return void
     */
    public function updated(Model $model)
    {
        if ($model->wasChanged([$model->getCustomerNameKey(), $model->getCustomerEmailKey()])) {
            $model->updateCustomer();
        }
    }

    /**
     * Handle the reader "deleted" event.
     *
     * @param Model & \DarkGhostHunter\Laraflow\Billable $model
     * @return void
     */
    public function deleted(Model $model)
    {
        $model->deleteCustomer();

        if (method_exists($model, 'returnFlowSubscription')) {

            $model->returnFlowSubscription()
                ->chunk(10, function ($subscriptions) use ($model) {
                    foreach ($subscriptions as $subscription) {
                        $model->unsubscribeNow($subscription->subscription_id);
                    }
                });

            $model->returnFlowSubscription()->delete();
        }
    }
}
