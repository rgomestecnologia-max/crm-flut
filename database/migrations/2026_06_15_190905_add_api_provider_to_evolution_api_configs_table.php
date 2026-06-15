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
        Schema::table('evolution_api_configs', function (Blueprint $table) {
            $table->string('api_provider', 20)->default('evolution')->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('evolution_api_configs', function (Blueprint $table) {
            $table->dropColumn('api_provider');
        });
    }
};
