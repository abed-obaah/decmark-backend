<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConversationsTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $tableName = 'conversations';

    /**
     * Run the migrations.
     * @table conversations
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->engine = '';
            $table->uuid('id');
            $table->primary('id');
            $table->char('sender_id', 36);
            $table->char('receiver_id', 36);

            $table->index(["sender_id"], 'fk_conversations_1_idx');

            $table->index(["receiver_id"], 'fk_conversations_2_idx');
            $table->nullableTimestamps();


            $table->foreign('sender_id', 'fk_conversations_1_idx')
                ->references('id')->on('users')
                ->onDelete('no action')
                ->onUpdate('no action');

            $table->foreign('receiver_id', 'fk_conversations_2_idx')
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
