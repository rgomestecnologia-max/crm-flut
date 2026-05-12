<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->nullable()->after('total_setup');
            $table->decimal('original_total_monthly', 10, 2)->nullable()->after('discount_percent');
            $table->decimal('original_total_setup', 10, 2)->nullable()->after('original_total_monthly');
            $table->string('status', 20)->default('analise')->after('original_total_setup');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'original_total_monthly', 'original_total_setup', 'status']);
        });
    }
};
