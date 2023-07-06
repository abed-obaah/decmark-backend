<?php

use App\Enums\ServiceStatusEnum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServicesTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $tableName = 'services';

    /**
     * Run the migrations.
     * @table services
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('artisan_id')->nullable()->index();
            $table->point('coordinate');
            $table->string('title', 75)->index();
            $table->string('type', 45)->index();
            $table->unsignedBigInteger('price')->index();
            $table->longText('description')->nullable();
            $table->integer('duration')->default(0);
            $table->enum('status', ServiceStatusEnum::values());
            $table->dateTime('scheduled_at')->nullable();
            $table->timestamps();


            $table->foreign('user_id')
                ->references('id')->on('users')->onDelete('cascade');

            $table->foreign('artisan_id')
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
        Schema::dropIfExists($this->tableName);
    }
}
