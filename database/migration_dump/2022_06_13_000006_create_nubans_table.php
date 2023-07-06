<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNubansTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $tableName = 'nubans';

    /**
     * Run the migrations.
     * @table nubans
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
            $table->string('number', 45);
            $table->string('name', 45);
            $table->string('bank_code', 45);
            $table->string('bank_name', 45);
            $table->string('driver', 45);

            $table->index(["user_id"], 'fk_nubans_1_idx');
            $table->nullableTimestamps();


            $table->foreign('user_id', 'fk_nubans_1_idx')
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
