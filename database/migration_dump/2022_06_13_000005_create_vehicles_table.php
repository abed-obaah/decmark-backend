<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVehiclesTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $tableName = 'vehicles';

    /**
     * Run the migrations.
     * @table vehicles
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
            $table->enum('type', ['car', 'bike', 'tricycle', 'lorry']);
            $table->string('model', 45);
            $table->string('plate_number', 45);
            $table->string('colour', 45);
            $table->string('registration_number', 45);
            $table->json('drivers_license');
            $table->json('proof_of_ownership');
            $table->json('road_worthiness_certificate');
            $table->enum('status', ['pending', 'active']);

            $table->index(["user_id"], 'fk_vehicles_1_idx');
            $table->nullableTimestamps();


            $table->foreign('user_id', 'fk_vehicles_1_idx')
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
