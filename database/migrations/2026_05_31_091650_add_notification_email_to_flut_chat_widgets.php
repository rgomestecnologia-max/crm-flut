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
        Schema::table('flut_chat_widgets', function (Blueprint $table) {
            $table->string('notification_email')->nullable()->after('whatsapp_message');
        });
    }

    public function down(): void
    {
        Schema::table('flut_chat_widgets', function (Blueprint $table) {
            $table->dropColumn('notification_email');
        });
    }
};
