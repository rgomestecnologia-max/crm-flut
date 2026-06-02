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
        Schema::table('ai_bot_configs', function (Blueprint $table) {
            $table->unsignedInteger('inactivity_followup_minutes')->nullable()->after('max_bot_turns');
            $table->text('inactivity_followup_message')->nullable()->after('inactivity_followup_minutes');
            $table->unsignedInteger('inactivity_close_minutes')->nullable()->after('inactivity_followup_message');
            $table->text('inactivity_close_message')->nullable()->after('inactivity_close_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('ai_bot_configs', function (Blueprint $table) {
            $table->dropColumn(['inactivity_followup_minutes', 'inactivity_followup_message', 'inactivity_close_minutes', 'inactivity_close_message']);
        });
    }
};
