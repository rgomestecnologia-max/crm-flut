<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Definições de campos personalizados
        Schema::create('crm_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // "Data de Entrada"
            $table->string('key')->unique();           // "data_entrada" — usado na API
            $table->string('type')->default('text');   // text|textarea|number|currency|date|time|datetime|email|phone|url
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Valores dos campos por card
        Schema::create('crm_card_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('crm_cards')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('crm_custom_fields')->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['card_id', 'field_id']);
        });

        // Remove colunas que viram campos personalizados
        Schema::table('crm_cards', function (Blueprint $table) {
            $table->dropColumn(['value', 'due_date', 'checkin_at', 'checkout_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_card_field_values');
        Schema::dropIfExists('crm_custom_fields');

        Schema::table('crm_cards', function (Blueprint $table) {
            $table->decimal('value', 10, 2)->nullable()->after('priority');
            $table->date('due_date')->nullable()->after('value');
            $table->dateTime('checkin_at')->nullable()->after('due_date');
            $table->dateTime('checkout_at')->nullable()->after('checkin_at');
        });
    }
};
