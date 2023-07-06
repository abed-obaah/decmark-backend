<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivationCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activation_codes', function (Blueprint $table) {
            $table->id();
            $table->uuid('owner_id');
            $table->string('owner_type', 100);
            $table->uuid('for_id')->nullable();
            $table->string('for_type', 100)->nullable();
            $table->string('token');
            $table->string('action');
            $table->dateTime('expires_at');
            $table->timestamps();

            $table->index(['owner_id', 'owner_type']);
            $table->index(['for_id', 'for_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activation_codes');
    }
}
