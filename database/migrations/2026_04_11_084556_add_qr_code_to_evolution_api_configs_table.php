<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evolution_api_configs', function (Blueprint $table) {
            $table->text('qr_code')->nullable()->after('connection_status');
            $table->string('pairing_code')->nullable()->after('qr_code');
        });
    }

    public function down(): void
    {
        Schema::table('evolution_api_configs', function (Blueprint $table) {
            $table->dropColumn(['qr_code', 'pairing_code']);
        });
    }
};
