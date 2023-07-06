<?php

use App\Enums\AttachmentTypeEnum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttachmentsTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $tableName = 'attachments';

    /**
     * Run the migrations.
     * @table attachments
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->index();
            $table->string('owner_id', 100);
            $table->string('owner_type', 45);
            $table->enum('type', AttachmentTypeEnum::values());
            $table->string('name', 200);
            $table->string('mime_type', 45);
            $table->string('extention', 20);
            $table->unsignedBigInteger('size');
            $table->string('file', 300);
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')->cascadeOnDelete();
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
