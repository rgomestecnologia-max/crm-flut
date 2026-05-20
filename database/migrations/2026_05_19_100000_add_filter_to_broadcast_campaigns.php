<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            $table->string('recipient_mode')->default('all')->after('total_recipients');
            $table->string('filter_tag')->nullable()->after('recipient_mode');
            $table->json('manual_recipient_ids')->nullable()->after('filter_tag');
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            $table->dropColumn(['recipient_mode', 'filter_tag', 'manual_recipient_ids']);
        });
    }
};
