<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_bot_configs', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(false);
            $table->string('openai_api_key')->nullable();
            $table->string('model')->default('gpt-4o-mini');
            $table->text('system_prompt')->nullable();
            $table->text('department_routing_prompt')->nullable();
            $table->text('initial_greeting')->nullable();
            $table->integer('max_bot_turns')->default(5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_bot_configs');
    }
};
