<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->uuid('by')->index();
            $table->uuid('for')->index();
            $table->enum('type', ['SERVICE', 'RIDER'])->default('SERVICE')->index();
            $table->foreign('by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('for')->references('id')->on('services')->cascadeOnDelete();
            $table->longText('review');
            $table->double('score')->index();
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
        Schema::dropIfExists('ratings');
    }
}
