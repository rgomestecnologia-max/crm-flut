<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('is_group');
        });

        // Migrar conversas que estavam com status='archived' para is_archived=true
        DB::table('conversations')->where('status', 'archived')->update([
            'is_archived' => true,
            'status'      => 'open',
        ]);
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('is_archived');
        });
    }
};
