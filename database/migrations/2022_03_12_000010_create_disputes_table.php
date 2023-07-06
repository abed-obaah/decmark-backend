<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisputesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('owner_id', 100);
            $table->string('owner_type', 45);
            $table->string('disputable_id', 100);
            $table->string('disputable_type', 45);
            $table->string('resolver_id', 100)->nullable();
            $table->string('resolver_type', 45)->nullable();
            $table->string('flags');
            $table->string('title');
            $table->text('note');
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'owner_type']);
            $table->index(['disputable_id', 'disputable_type']);
            $table->index(['resolver_id', 'resolver_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('disputables');
    }
}
