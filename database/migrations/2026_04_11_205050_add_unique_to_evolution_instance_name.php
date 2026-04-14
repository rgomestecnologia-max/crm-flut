<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O instance_name é o identificador da instância NO SERVIDOR Evolution (e no payload
 * do webhook). Duas empresas no nosso CRM não podem apontar pra mesma instância porque
 * o roteamento do webhook usa esse campo pra descobrir company_id.
 *
 * Por isso o unique é GLOBAL (não composto com company_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evolution_api_configs', function (Blueprint $table) {
            $table->unique('instance_name');
        });
    }

    public function down(): void
    {
        Schema::table('evolution_api_configs', function (Blueprint $table) {
            $table->dropUnique(['instance_name']);
        });
    }
};
