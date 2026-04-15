<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_menu_configs', function (Blueprint $table) {
            $table->boolean('reply_in_groups')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_menu_configs', function (Blueprint $table) {
            $table->dropColumn('reply_in_groups');
        });
    }
};
