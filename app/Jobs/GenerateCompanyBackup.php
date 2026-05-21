<?php

namespace App\Jobs;

use App\Models\CompanyBackup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateCompanyBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 1;

    public function __construct(
        public int           $companyId,
        public CompanyBackup $backup,
    ) {}

    public function handle(): void
    {
        try {
            $tables = [
                'contacts', 'conversations', 'messages', 'departments', 'tags',
                'crm_pipelines', 'crm_stages', 'crm_cards', 'crm_card_activities',
                'crm_card_field_values', 'crm_custom_fields', 'automations',
                'ai_bot_configs', 'ai_bot_products', 'chatbot_menu_configs',
                'broadcast_contacts', 'broadcast_campaigns', 'broadcast_campaign_runs',
                'broadcast_campaign_recipients', 'evolution_api_configs',
                'meta_whatsapp_configs', 'meta_message_templates', 'ddd_routing_rules',
                'api_tokens', 'quick_replies', 'transfer_logs',
            ];

            $data = [
                'version'    => '1.0',
                'company_id' => $this->companyId,
                'created_at' => now()->toISOString(),
                'tables'     => [],
            ];

            $totalRecords = 0;

            foreach ($tables as $table) {
                $rows = DB::table($table)
                    ->where('company_id', $this->companyId)
                    ->get()
                    ->map(fn($r) => (array) $r)
                    ->toArray();

                $data['tables'][$table] = $rows;
                $totalRecords += count($rows);
            }

            // Pivot tables (sem company_id direto)
            $conversationIds = collect($data['tables']['conversations'] ?? [])->pluck('id')->toArray();
            $departmentIds   = collect($data['tables']['departments'] ?? [])->pluck('id')->toArray();

            if (!empty($conversationIds)) {
                $data['tables']['conversation_tag'] = DB::table('conversation_tag')
                    ->whereIn('conversation_id', $conversationIds)
                    ->get()->map(fn($r) => (array) $r)->toArray();
                $totalRecords += count($data['tables']['conversation_tag']);
            }

            if (!empty($departmentIds)) {
                $data['tables']['department_user'] = DB::table('department_user')
                    ->whereIn('department_id', $departmentIds)
                    ->get()->map(fn($r) => (array) $r)->toArray();
                $totalRecords += count($data['tables']['department_user']);
            }

            // Users da empresa (sem senha)
            $users = DB::table('users')
                ->where('company_id', $this->companyId)
                ->get()
                ->map(function ($r) {
                    $u = (array) $r;
                    unset($u['password'], $u['remember_token']);
                    return $u;
                })->toArray();
            $data['tables']['users'] = $users;
            $totalRecords += count($users);

            $json       = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $compressed = gzencode($json, 6);
            $filename   = $this->backup->filename;

            Storage::disk('local')->put("backups/{$filename}", $compressed);

            $this->backup->update([
                'status'        => 'ready',
                'size_bytes'    => strlen($compressed),
                'tables_count'  => count($data['tables']),
                'records_count' => $totalRecords,
            ]);

            Log::info('Backup gerado', [
                'company'  => $this->companyId,
                'filename' => $filename,
                'size'     => strlen($compressed),
                'records'  => $totalRecords,
            ]);
        } catch (\Throwable $e) {
            $this->backup->update(['status' => 'failed']);
            Log::error('Backup falhou', ['error' => $e->getMessage()]);
        }
    }
}
