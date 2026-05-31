<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('flut_chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('widget_id');
            $table->string('visitor_id', 64)->index();
            $table->string('visitor_name')->nullable();
            $table->string('status', 20)->default('active'); // active, closed
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->foreign('widget_id')->references('id')->on('flut_chat_widgets')->cascadeOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('flut_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('conversation_id');
            $table->string('sender_type', 10); // visitor, agent, bot
            $table->unsignedBigInteger('sender_id')->nullable(); // user_id for agent
            $table->text('content');
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('flut_chat_conversations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flut_chat_messages');
        Schema::dropIfExists('flut_chat_conversations');
    }
};
