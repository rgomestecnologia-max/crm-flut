<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_menu_configs', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(false);
            $table->string('company_name')->default('');
            $table->text('welcome_template');
            $table->string('menu_prompt')->default('Digite o *número* do setor que deseja falar:');
            $table->text('invalid_option_message');
            $table->text('after_selection_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_menu_configs');
    }
};
