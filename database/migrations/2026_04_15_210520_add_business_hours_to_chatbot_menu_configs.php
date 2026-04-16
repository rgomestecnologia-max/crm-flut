<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_menu_configs', function (Blueprint $table) {
            $table->boolean('business_hours_enabled')->default(false)->after('after_selection_message');
            $table->json('business_hours')->nullable()->after('business_hours_enabled');
            $table->text('outside_hours_message')->nullable()->after('business_hours');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_menu_configs', function (Blueprint $table) {
            $table->dropColumn(['business_hours_enabled', 'business_hours', 'outside_hours_message']);
        });
    }
};
