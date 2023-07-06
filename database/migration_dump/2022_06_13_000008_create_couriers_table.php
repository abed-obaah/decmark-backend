<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCouriersTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $tableName = 'couriers';

    /**
     * Run the migrations.
     * @table couriers
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->engine = '';
            $table->uuid('id');
            $table->primary('id');
            $table->char('user_id', 36);
            $table->char('artisan_id', 36);
            $table->string('title', 45);
            $table->bigInteger('price');
            $table->string('from_place_id', 100)->nullable();
            $table->string('to_place_id', 100)->nullable();
            $table->tinyInteger('artisan_accept')->default('0');
            $table->tinyInteger('user_accept')->default('0');
            $table->longText('description');
            $table->enum('status', ['pending', 'ongoing', 'completed'])->nullable();

            $table->index(["user_id"], 'fk_services_1_idx');

            $table->index(["artisan_id"], 'fk_services_2_idx');
            $table->nullableTimestamps();


            $table->foreign('user_id', 'fk_services_1_idx')
                ->references('id')->on('users')
                ->onDelete('no action')
                ->onUpdate('no action');

            $table->foreign('artisan_id', 'fk_services_2_idx')
                ->references('id')->on('users')
                ->onDelete('no action')
                ->onUpdate('no action');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->tableName);
    }
}
