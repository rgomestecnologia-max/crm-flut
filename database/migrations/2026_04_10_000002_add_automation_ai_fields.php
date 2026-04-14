<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automations', function (Blueprint $table) {
            $table->boolean('enable_ai_on_reply')->default(false)->after('delay_minutes');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('source_automation_id')
                  ->nullable()
                  ->after('is_group')
                  ->constrained('automations')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Automation::class, 'source_automation_id');
            $table->dropColumn('source_automation_id');
        });

        Schema::table('automations', function (Blueprint $table) {
            $table->dropColumn('enable_ai_on_reply');
        });
    }
};
