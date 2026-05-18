<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_bot_configs', function (Blueprint $table) {
            $table->text('handoff_prompt')->nullable()->after('handoff_message');
        });
    }

    public function down(): void
    {
        Schema::table('ai_bot_configs', function (Blueprint $table) {
            $table->dropColumn('handoff_prompt');
        });
    }
};
