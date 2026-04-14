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
        Schema::create('evolution_api_configs', function (Blueprint $table) {
            $table->id();
            $table->string('server_url');
            $table->string('global_api_key');
            $table->string('instance_name')->default('crm-whatsapp');
            $table->string('instance_api_key')->nullable();
            $table->string('webhook_token')->nullable();
            $table->string('connection_status')->default('disconnected');
            $table->string('phone_number')->nullable();
            $table->string('profile_name')->nullable();
            $table->boolean('groups_ignore')->default(false);
            $table->boolean('always_online')->default(false);
            $table->boolean('read_messages')->default(false);
            $table->boolean('reject_call')->default(false);
            $table->string('msg_call')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evolution_api_configs');
    }
};
