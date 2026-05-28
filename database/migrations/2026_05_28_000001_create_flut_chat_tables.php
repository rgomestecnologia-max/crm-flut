<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flut_chat_widgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('public_id', 36)->unique();
            $table->string('title')->default('Olá! Como posso ajudar?');
            $table->string('subtitle')->nullable();
            $table->string('color', 7)->default('#b2ff00');
            $table->string('logo_url')->nullable();
            $table->string('position', 20)->default('bottom-right');
            $table->string('whatsapp_number', 20)->nullable();
            $table->string('whatsapp_message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('company_id');
        });

        Schema::create('flut_chat_flows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreignId('widget_id')->constrained('flut_chat_widgets')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('company_id');
        });

        Schema::create('flut_chat_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreignId('flow_id')->constrained('flut_chat_flows')->cascadeOnDelete();
            $table->string('type', 20); // message, input, options, action
            $table->text('content')->nullable();
            $table->string('input_key')->nullable(); // key para salvar resposta (nome, email, etc)
            $table->string('input_placeholder')->nullable();
            $table->json('options')->nullable(); // [{label, next_step_id}]
            $table->unsignedBigInteger('next_step_id')->nullable(); // próximo step (para message/input)
            $table->string('action_type', 20)->nullable(); // whatsapp, lead, ia, redirect
            $table->string('action_value')->nullable(); // URL, número, etc
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['flow_id', 'sort_order']);
            $table->index('company_id');
        });

        Schema::create('flut_chat_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreignId('widget_id')->constrained('flut_chat_widgets')->cascadeOnDelete();
            $table->json('data'); // {nome: "...", email: "...", telefone: "..."}
            $table->string('action_taken', 20)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('page_url')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'created_at']);
            $table->index('widget_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flut_chat_leads');
        Schema::dropIfExists('flut_chat_steps');
        Schema::dropIfExists('flut_chat_flows');
        Schema::dropIfExists('flut_chat_widgets');
    }
};
