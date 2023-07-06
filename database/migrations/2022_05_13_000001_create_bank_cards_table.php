<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBankCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('owner');
            $table->string('reference', 100);
            $table->string('label', 45)->nullable();
            $table->string('name', 75)->nullable();
            $table->string('number', 45)->nullable();
            $table->smallInteger('expiry_month')->nullable();
            $table->smallInteger('expiry_year')->nullable();
            $table->text('token')->nullable();
            $table->string('brand', 45)->nullable();
            $table->string('driver', 45);
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('refunded_at')->nullable();
            $table->timestamps();

            $table->unique(['driver', 'reference']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bank_cards');
    }
}
