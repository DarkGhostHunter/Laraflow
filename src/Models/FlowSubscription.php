<?php

namespace DarkGhostHunter\Laraflow\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FlowSubscription
 * @package DarkGhostHunter\Laraflow\Models
 *
 * @property int $id
 * @property string $subscription_id
 * @property string $flow_customer_id
 * @property string $plan_id
 * @property string $coupon_id
 * @property \Illuminate\Support\Carbon $trial_starts_at
 * @property \Illuminate\Support\Carbon $trial_ends_at
 * @property \Illuminate\Support\Carbon $starts_at
 * @property \Illuminate\Support\Carbon $ends_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class FlowSubscription extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'flow_subscriptions';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $dates = [
        'trial_starts_at',
        'trial_ends_at',
        'starts_at',
        'ends_at',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id'
    ];

    /*
    |--------------------------------------------------------------------------
    | Subscription Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Return if the local subscription data has a coupon registered
     *
     * @return bool
     */
    public function hasCoupon()
    {
        return (bool)$this->coupon_id;
    }

}