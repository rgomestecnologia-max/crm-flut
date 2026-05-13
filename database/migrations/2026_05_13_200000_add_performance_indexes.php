<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->index('company_id', 'contacts_company_id_index');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'created_at'], 'messages_conv_created_index');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->index(['company_id', 'status', 'department_id'], 'conversations_company_status_dept_index');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', fn(Blueprint $t) => $t->dropIndex('contacts_company_id_index'));
        Schema::table('messages', fn(Blueprint $t) => $t->dropIndex('messages_conv_created_index'));
        Schema::table('conversations', fn(Blueprint $t) => $t->dropIndex('conversations_company_status_dept_index'));
    }
};
