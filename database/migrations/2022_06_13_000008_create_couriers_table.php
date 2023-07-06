<?php

use App\Enums\CourierStatusEnum;
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
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->uuid('artisan_id')->nullable()->index();
            $table->string('title', 45);
            $table->bigInteger('price');
            $table->point('origin')->nullable();
            $table->point('destination')->nullable();
            $table->tinyInteger('artisan_accept')->default('0');
            $table->tinyInteger('user_accept')->default('0');
            $table->longText('description');
            $table->enum('status', CourierStatusEnum::values())
                ->default(CourierStatusEnum::PENDING)->nullable();

            $table->nullableTimestamps();


            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('no action')
                ->onUpdate('no action');

            $table->foreign('artisan_id')
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
