<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Admin pode ter company_id NULL (não pertence a uma empresa específica;
            // escolhe pela tela /select-company). Agente/supervisor terá um valor.
            $table->foreignId('company_id')
                ->nullable()
                ->after('department_id')
                ->constrained()
                ->nullOnDelete();

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
