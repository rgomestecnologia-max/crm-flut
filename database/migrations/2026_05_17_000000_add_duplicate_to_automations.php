<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automations', function (Blueprint $table) {
            $table->unsignedBigInteger('duplicate_to_pipeline_id')->nullable()->after('move_on_reply_to_stage_id');
            $table->unsignedBigInteger('duplicate_to_stage_id')->nullable()->after('duplicate_to_pipeline_id');
        });
    }

    public function down(): void
    {
        Schema::table('automations', function (Blueprint $table) {
            $table->dropColumn(['duplicate_to_pipeline_id', 'duplicate_to_stage_id']);
        });
    }
};
