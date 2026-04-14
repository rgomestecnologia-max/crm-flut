<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // E-mail no contato
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
        });

        // Datas de entrada/saída no card
        Schema::table('crm_cards', function (Blueprint $table) {
            $table->dateTime('checkin_at')->nullable()->after('due_date');
            $table->dateTime('checkout_at')->nullable()->after('checkin_at');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('email');
        });
        Schema::table('crm_cards', function (Blueprint $table) {
            $table->dropColumn(['checkin_at', 'checkout_at']);
        });
    }
};
