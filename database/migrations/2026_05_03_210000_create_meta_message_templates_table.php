<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('template_id')->nullable();
            $table->string('name');
            $table->string('language')->default('pt_BR');
            $table->string('category')->nullable();
            $table->string('status')->default('APPROVED');
            $table->json('components')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'name', 'language']);
        });

        Schema::table('automations', function (Blueprint $table) {
            $table->string('meta_template_name')->nullable()->after('message_template');
        });

        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            $table->string('meta_template_name')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_message_templates');
        Schema::table('automations', fn (Blueprint $t) => $t->dropColumn('meta_template_name'));
        Schema::table('broadcast_campaigns', fn (Blueprint $t) => $t->dropColumn('meta_template_name'));
    }
};
