<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagesTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $tableName = 'messages';

    /**
     * Run the migrations.
     * @table messages
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
            $table->char('conversation_id', 36);
            $table->longText('content');
            $table->string('read', 45);

            $table->index(["user_id"], 'fk_messages_1_idx');

            $table->index(["conversation_id"], 'fk_messages_2_idx');
            $table->nullableTimestamps();


            $table->foreign('user_id', 'fk_messages_1_idx')
                ->references('id')->on('users')
                ->onDelete('no action')
                ->onUpdate('no action');

            $table->foreign('conversation_id', 'fk_messages_2_idx')
                ->references('id')->on('conversations')
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
