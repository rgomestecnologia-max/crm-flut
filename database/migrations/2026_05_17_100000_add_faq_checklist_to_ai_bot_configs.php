<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_bot_configs', function (Blueprint $table) {
            $table->text('faq')->nullable()->after('website_content');
            $table->text('checklist')->nullable()->after('faq');
        });
    }

    public function down(): void
    {
        Schema::table('ai_bot_configs', function (Blueprint $table) {
            $table->dropColumn(['faq', 'checklist']);
        });
    }
};
