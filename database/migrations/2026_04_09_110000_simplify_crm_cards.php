<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_cards', function (Blueprint $table) {
            // stage_id passa a ser nullable (pipelines são as colunas agora)
            $table->foreignId('stage_id')->nullable()->change();
            // Adiciona prioridade
            $table->enum('priority', ['baixo', 'medio', 'alto', 'critico'])->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('crm_cards', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};
