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
        Schema::table('flut_chat_flows', function (Blueprint $table) {
            $table->integer('inactivity_warning_seconds')->default(120)->after('is_active');
            $table->text('inactivity_warning_message')->nullable()->after('inactivity_warning_seconds');
            $table->integer('inactivity_close_seconds')->default(120)->after('inactivity_warning_message');
            $table->text('inactivity_close_message')->nullable()->after('inactivity_close_seconds');
        });

        // Preenche defaults nos fluxos existentes
        DB::table('flut_chat_flows')->update([
            'inactivity_warning_message' => 'Ainda por aqui? 👀 Se quiser continuar, é só responder. A sessão expira em 2 minutos.',
            'inactivity_close_message'   => 'Opa! A conversa foi encerrada por inatividade. Reinicie o chat para tentar de novo 😉',
        ]);
    }

    public function down(): void
    {
        Schema::table('flut_chat_flows', function (Blueprint $table) {
            $table->dropColumn(['inactivity_warning_seconds', 'inactivity_warning_message', 'inactivity_close_seconds', 'inactivity_close_message']);
        });
    }
};
