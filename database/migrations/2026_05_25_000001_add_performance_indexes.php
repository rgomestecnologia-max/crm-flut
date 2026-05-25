<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'sender_type', 'created_at'], 'msg_conv_sender_created');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->index('is_archived');
        });

        Schema::table('crm_cards', function (Blueprint $table) {
            $table->index(['pipeline_id', 'stage_id'], 'cards_pipeline_stage');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('msg_conv_sender_created');
        });
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['is_archived']);
        });
        Schema::table('crm_cards', function (Blueprint $table) {
            $table->dropIndex('cards_pipeline_stage');
        });
    }
};
