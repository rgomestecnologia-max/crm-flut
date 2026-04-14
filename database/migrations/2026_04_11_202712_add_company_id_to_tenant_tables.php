<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-tenancy Fase 2: adiciona company_id em todas as tabelas de domínio,
 * faz backfill apontando para a empresa "RSG Group" (id=1) e converte unique
 * constraints simples em compostas com company_id.
 *
 * Após este migrate, todos os models que usarem o trait BelongsToCompany
 * passarão a filtrar automaticamente por empresa.
 */
return new class extends Migration
{
    /**
     * Tabelas que recebem company_id NOT NULL.
     * Order matters: foreign-key parents primeiro.
     */
    private array $tables = [
        'departments',
        'contacts',
        'conversations',
        'messages',
        'transfer_logs',
        'quick_replies',
        'tags',
        'ai_bot_configs',
        'ai_bot_products',
        'chatbot_menu_configs',
        'evolution_api_configs',
        'zapi_configs',
        'automations',
        'api_tokens',
        'crm_pipelines',
        'crm_stages',
        'crm_cards',
        'crm_card_activities',
        'crm_card_field_values',
        'crm_custom_fields',
    ];

    public function up(): void
    {
        $defaultCompanyId = DB::table('companies')->where('slug', 'rsg-group')->value('id') ?? 1;

        // 1) Adiciona a coluna nullable + FK em cada tabela.
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'company_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->cascadeOnDelete();
                $table->index('company_id');
            });
        }

        // 2) Backfill: tudo que existe vira da RSG Group.
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'company_id')) {
                continue;
            }
            DB::table($tableName)->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
        }

        // 3) Sobe pra NOT NULL.
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'company_id')) {
                continue;
            }
            // change() exige doctrine/dbal em versões antigas; Laravel 11 já traz suporte nativo.
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('company_id')->nullable(false)->change();
            });
        }

        // 4) Converte unique constraints simples → compostas com company_id.
        // Cada empresa pode ter o mesmo telefone, mesmo protocolo, mesma chave, etc.

        // contacts.phone (unique → composite)
        if (Schema::hasTable('contacts')) {
            try { Schema::table('contacts', fn(Blueprint $t) => $t->dropUnique(['phone'])); } catch (\Throwable) {}
            Schema::table('contacts', fn(Blueprint $t) => $t->unique(['company_id', 'phone']));

            // contacts.chat_lid (unique → composite)
            try { Schema::table('contacts', fn(Blueprint $t) => $t->dropUnique(['chat_lid'])); } catch (\Throwable) {}
            Schema::table('contacts', fn(Blueprint $t) => $t->unique(['company_id', 'chat_lid']));
        }

        // conversations.protocol
        if (Schema::hasTable('conversations')) {
            try { Schema::table('conversations', fn(Blueprint $t) => $t->dropUnique(['protocol'])); } catch (\Throwable) {}
            Schema::table('conversations', fn(Blueprint $t) => $t->unique(['company_id', 'protocol']));
        }

        // messages.zapi_message_id
        if (Schema::hasTable('messages')) {
            try { Schema::table('messages', fn(Blueprint $t) => $t->dropUnique(['zapi_message_id'])); } catch (\Throwable) {}
            Schema::table('messages', fn(Blueprint $t) => $t->unique(['company_id', 'zapi_message_id']));
        }

        // crm_custom_fields.key
        if (Schema::hasTable('crm_custom_fields')) {
            try { Schema::table('crm_custom_fields', fn(Blueprint $t) => $t->dropUnique(['key'])); } catch (\Throwable) {}
            Schema::table('crm_custom_fields', fn(Blueprint $t) => $t->unique(['company_id', 'key']));
        }

        // api_tokens.token (hash sha256 — colisão entre empresas é improvável,
        // mas torna composto pra ficar consistente)
        if (Schema::hasTable('api_tokens')) {
            try { Schema::table('api_tokens', fn(Blueprint $t) => $t->dropUnique(['token'])); } catch (\Throwable) {}
            Schema::table('api_tokens', fn(Blueprint $t) => $t->unique(['company_id', 'token']));
        }
    }

    public function down(): void
    {
        // Reverte: dropa as compostas, restaura as simples e remove a coluna company_id.

        if (Schema::hasTable('api_tokens')) {
            try { Schema::table('api_tokens', fn(Blueprint $t) => $t->dropUnique(['company_id', 'token'])); } catch (\Throwable) {}
            try { Schema::table('api_tokens', fn(Blueprint $t) => $t->unique(['token'])); } catch (\Throwable) {}
        }
        if (Schema::hasTable('crm_custom_fields')) {
            try { Schema::table('crm_custom_fields', fn(Blueprint $t) => $t->dropUnique(['company_id', 'key'])); } catch (\Throwable) {}
            try { Schema::table('crm_custom_fields', fn(Blueprint $t) => $t->unique(['key'])); } catch (\Throwable) {}
        }
        if (Schema::hasTable('messages')) {
            try { Schema::table('messages', fn(Blueprint $t) => $t->dropUnique(['company_id', 'zapi_message_id'])); } catch (\Throwable) {}
            try { Schema::table('messages', fn(Blueprint $t) => $t->unique(['zapi_message_id'])); } catch (\Throwable) {}
        }
        if (Schema::hasTable('conversations')) {
            try { Schema::table('conversations', fn(Blueprint $t) => $t->dropUnique(['company_id', 'protocol'])); } catch (\Throwable) {}
            try { Schema::table('conversations', fn(Blueprint $t) => $t->unique(['protocol'])); } catch (\Throwable) {}
        }
        if (Schema::hasTable('contacts')) {
            try { Schema::table('contacts', fn(Blueprint $t) => $t->dropUnique(['company_id', 'chat_lid'])); } catch (\Throwable) {}
            try { Schema::table('contacts', fn(Blueprint $t) => $t->unique(['chat_lid'])); } catch (\Throwable) {}
            try { Schema::table('contacts', fn(Blueprint $t) => $t->dropUnique(['company_id', 'phone'])); } catch (\Throwable) {}
            try { Schema::table('contacts', fn(Blueprint $t) => $t->unique(['phone'])); } catch (\Throwable) {}
        }

        foreach (array_reverse($this->tables) as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'company_id')) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }
    }
};
