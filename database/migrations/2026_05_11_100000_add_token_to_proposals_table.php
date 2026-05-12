<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->string('token', 32)->nullable()->unique()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn('token');
        });
    }
};
