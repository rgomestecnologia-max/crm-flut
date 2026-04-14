<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // descrição: "Site Institucional"
            $table->string('token', 64)->unique();           // hash SHA-256
            $table->foreignId('default_pipeline_id')->nullable()->constrained('crm_pipelines')->nullOnDelete();
            $table->foreignId('default_stage_id')->nullable()->constrained('crm_stages')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};
