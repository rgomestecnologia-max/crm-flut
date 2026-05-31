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
        // Canal na conversa
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('channel', 20)->default('whatsapp')->after('status');
        });

        // Identificador Meta para contatos (PSID Messenger/Instagram)
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('meta_user_id', 100)->nullable()->after('chat_lid');
        });

        // Campos Messenger/Instagram na config Meta
        Schema::table('meta_whatsapp_configs', function (Blueprint $table) {
            $table->string('page_id', 100)->nullable()->after('phone_display');
            $table->text('page_access_token')->nullable()->after('page_id');
            $table->string('instagram_account_id', 100)->nullable()->after('page_access_token');
            $table->boolean('messenger_enabled')->default(false)->after('is_active');
            $table->boolean('instagram_enabled')->default(false)->after('messenger_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', fn(Blueprint $t) => $t->dropColumn('channel'));
        Schema::table('contacts', fn(Blueprint $t) => $t->dropColumn('meta_user_id'));
        Schema::table('meta_whatsapp_configs', fn(Blueprint $t) => $t->dropColumn(['page_id', 'page_access_token', 'instagram_account_id', 'messenger_enabled', 'instagram_enabled']));
    }
};
