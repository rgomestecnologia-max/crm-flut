<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_whatsapp_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number_id')->nullable();
            $table->string('whatsapp_business_account_id')->nullable();
            $table->text('access_token')->nullable();
            $table->string('verify_token')->nullable();
            $table->string('phone_display')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('whatsapp_provider')->default('evolution')->after('modules');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_whatsapp_configs');
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('whatsapp_provider');
        });
    }
};
