<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE conversations MODIFY COLUMN status ENUM('open','pending','resolved','transferred','waiting_human','archived') DEFAULT 'open'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE conversations MODIFY COLUMN status ENUM('open','pending','resolved','transferred','waiting_human') DEFAULT 'open'");
    }
};
