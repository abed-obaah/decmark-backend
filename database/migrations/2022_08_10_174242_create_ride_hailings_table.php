<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRideHailingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ride_hailings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('rider_id')->nullable()->index();
            $table->point('current_coordinate')->nullable();
            $table->point('destination_coordinate')->nullable();
            $table->string('ride_type');
            $table->unsignedInteger('passengers_count');
            $table->unsignedInteger('seats_count')->nullable();
            $table->string('payment_method')->nullable();
            $table->dateTime('scheduled_at');
            $table->enum('rider_request_status', ['pending', 'accept', 'reject', 'cancel_trip', 'end_trip'])->default('pending');

            //Cancelled Rides
            $table->tinyInteger('cancel_ride')->nullable()->default(0);
            $table->string('reason_for_cancel')->nullable();

            //End Trip
            $table->tinyInteger('end_trip')->nullable()->default(0);
            $table->enum('ended_by', ['rider','passenger'])->nullable();

            //Passenger review
            $table->unsignedInteger('rider_rating')->nullable()->default(0);
            $table->string('rider_review')->nullable();

            $table->timestamps();

            $table->foreign('user_id')
            ->references('id')->on('users')->onDelete('cascade');

            $table->foreign('rider_id')
            ->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ride_hailings');
    }
}
