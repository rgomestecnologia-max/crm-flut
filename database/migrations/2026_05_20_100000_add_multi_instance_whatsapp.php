<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->unsignedBigInteger('evolution_api_config_id')->nullable()->after('sort_order');
        });

        Schema::table('evolution_api_configs', function (Blueprint $table) {
            $table->unsignedBigInteger('default_department_id')->nullable()->after('is_active');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('evolution_api_config_id')->nullable()->after('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('evolution_api_config_id');
        });
        Schema::table('evolution_api_configs', function (Blueprint $table) {
            $table->dropColumn('default_department_id');
        });
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('evolution_api_config_id');
        });
    }
};
