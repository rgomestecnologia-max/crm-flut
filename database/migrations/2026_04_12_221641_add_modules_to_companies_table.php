<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->json('modules')->nullable()->after('is_active');
        });

        // Empresas existentes recebem todos os módulos habilitados.
        $allModules = json_encode([
            'dashboard', 'chat', 'crm',
            'admin.crm', 'admin.departments', 'admin.agents',
            'admin.chatbot', 'admin.ia', 'admin.automation',
        ]);

        DB::table('companies')->update(['modules' => $allModules]);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('modules');
        });
    }
};
