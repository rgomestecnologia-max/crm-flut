<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Migra a API key e model da config existente (RSG Group) pra global settings.
        $existingConfig = DB::table('ai_bot_configs')->first();
        if ($existingConfig) {
            DB::table('global_settings')->insertOrIgnore([
                ['key' => 'gemini_api_key', 'value' => $existingConfig->openai_api_key, 'created_at' => now(), 'updated_at' => now()],
                ['key' => 'gemini_model',   'value' => $existingConfig->model ?? 'gemini-2.0-flash', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('global_settings');
    }
};
