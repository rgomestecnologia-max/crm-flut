<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zapi_configs', function (Blueprint $table) {
            $table->id();
            $table->string('instance_id');
            $table->string('token');
            $table->string('client_token')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->enum('connection_status', ['connected', 'disconnected', 'qrcode'])->default('disconnected');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zapi_configs');
    }
};
