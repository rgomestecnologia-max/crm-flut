<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcast_campaign_recipients', function (Blueprint $table) {
            $table->string('message_id')->nullable()->after('phone');
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('read_at')->nullable()->after('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_campaign_recipients', function (Blueprint $table) {
            $table->dropColumn(['message_id', 'delivered_at', 'read_at']);
        });
    }
};
