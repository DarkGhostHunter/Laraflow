<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFlowSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('flow_subscriptions', function (Blueprint $table) {
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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('flow_subscriptions');
    }
}
