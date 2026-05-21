<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RestoreCompanyBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;
    public int $tries   = 1;

    public function __construct(
        public int    $companyId,
        public string $filename,
    ) {}

    public function handle(): void
    {
        try {
            $compressed = Storage::disk('local')->get("backups/{$this->filename}");
            $json = gzdecode($compressed);
            $data = json_decode($json, true);

            if (!$data || !isset($data['tables'])) {
                Log::error('Restauração: arquivo inválido');
                return;
            }

            DB::beginTransaction();

            // Ordem de exclusão (respeita foreign keys)
            $deleteOrder = [
                'conversation_tag', 'department_user',
                'broadcast_campaign_recipients', 'broadcast_campaign_runs',
                'transfer_logs', 'crm_card_field_values', 'crm_card_activities',
                'messages', 'crm_cards', 'crm_stages',
                'conversations', 'quick_replies', 'tags',
                'automations', 'ai_bot_products', 'ai_bot_configs',
                'chatbot_menu_configs', 'broadcast_contacts', 'broadcast_campaigns',
                'meta_message_templates', 'meta_whatsapp_configs', 'evolution_api_configs',
                'ddd_routing_rules', 'api_tokens', 'crm_custom_fields', 'crm_pipelines',
                'contacts', 'departments',
            ];

            foreach ($deleteOrder as $table) {
                if ($table === 'conversation_tag' || $table === 'department_user') continue;
                DB::table($table)->where('company_id', $this->companyId)->delete();
            }

            // Pivot tables
            if (!empty($data['tables']['conversation_tag'])) {
                $convIds = DB::table('conversations')->where('company_id', $this->companyId)->pluck('id');
                if ($convIds->isNotEmpty()) {
                    DB::table('conversation_tag')->whereIn('conversation_id', $convIds)->delete();
                }
            }
            if (!empty($data['tables']['department_user'])) {
                $deptIds = DB::table('departments')->where('company_id', $this->companyId)->pluck('id');
                if ($deptIds->isNotEmpty()) {
                    DB::table('department_user')->whereIn('department_id', $deptIds)->delete();
                }
            }

            // Limpar restante
            foreach ($deleteOrder as $table) {
                if ($table === 'conversation_tag' || $table === 'department_user') continue;
                DB::table($table)->where('company_id', $this->companyId)->delete();
            }

            // Ordem de inserção (respeita foreign keys)
            $insertOrder = [
                'departments', 'contacts', 'crm_pipelines', 'crm_custom_fields',
                'crm_stages', 'conversations', 'tags', 'crm_cards',
                'messages', 'crm_card_activities', 'crm_card_field_values',
                'automations', 'ai_bot_configs', 'ai_bot_products',
                'chatbot_menu_configs', 'broadcast_contacts', 'broadcast_campaigns',
                'broadcast_campaign_runs', 'broadcast_campaign_recipients',
                'evolution_api_configs', 'meta_whatsapp_configs', 'meta_message_templates',
                'ddd_routing_rules', 'api_tokens', 'quick_replies', 'transfer_logs',
                'conversation_tag', 'department_user',
            ];

            $restored = 0;
            foreach ($insertOrder as $table) {
                $rows = $data['tables'][$table] ?? [];
                if (empty($rows)) continue;

                // Atualiza company_id para o destino
                foreach ($rows as &$row) {
                    if (isset($row['company_id'])) {
                        $row['company_id'] = $this->companyId;
                    }
                }
                unset($row);

                // Insere em chunks para evitar limite de placeholders
                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
                $restored += count($rows);
            }

            // Users (restaurar sem senha — mantém users existentes)
            if (!empty($data['tables']['users'])) {
                foreach ($data['tables']['users'] as $userData) {
                    $userData['company_id'] = $this->companyId;
                    $userData['password']   = bcrypt('changeme123');
                    unset($userData['remember_token']);
                    DB::table('users')->updateOrInsert(
                        ['email' => $userData['email']],
                        $userData
                    );
                }
            }

            DB::commit();

            Log::info('Restauração concluída', [
                'company'  => $this->companyId,
                'filename' => $this->filename,
                'records'  => $restored,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Restauração falhou', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }
}
