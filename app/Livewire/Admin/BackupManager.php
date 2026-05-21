<?php

namespace App\Livewire\Admin;

use App\Jobs\GenerateCompanyBackup;
use App\Jobs\RestoreCompanyBackup;
use App\Models\CompanyBackup;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class BackupManager extends Component
{
    use WithFileUploads;

    public $restoreFile = null;

    public function generateBackup(): void
    {
        $company   = app(\App\Services\CurrentCompany::class)->model();
        $companyId = $company->id;
        $slug      = $company->slug ?? 'company-' . $companyId;
        $filename  = "{$slug}_" . now()->format('Y-m-d_His') . '.json.gz';

        $backup = CompanyBackup::create([
            'company_id' => $companyId,
            'filename'   => $filename,
            'status'     => 'generating',
            'created_by' => auth()->id(),
        ]);

        GenerateCompanyBackup::dispatch($companyId, $backup);

        $this->dispatch('toast', type: 'success', message: 'Backup em andamento... atualize a página em alguns segundos.');
    }

    public function downloadBackup(int $id): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $backup = CompanyBackup::findOrFail($id);
        $path   = "backups/{$backup->filename}";

        if (!Storage::disk('local')->exists($path)) {
            $this->dispatch('toast', type: 'error', message: 'Arquivo de backup não encontrado.');
            return response()->noContent();
        }

        return Storage::disk('local')->download($path, $backup->filename);
    }

    public function restoreBackup(): void
    {
        if (!$this->restoreFile) {
            $this->dispatch('toast', type: 'error', message: 'Selecione um arquivo de backup.');
            return;
        }

        $companyId = app(\App\Services\CurrentCompany::class)->id();
        $filename  = 'restore_' . now()->format('Y-m-d_His') . '.json.gz';

        // Salva o arquivo uploaded
        $this->restoreFile->storeAs('backups', $filename, 'local');

        // Valida o conteúdo
        $compressed = Storage::disk('local')->get("backups/{$filename}");
        $json = @gzdecode($compressed);
        if (!$json || !json_decode($json, true)) {
            Storage::disk('local')->delete("backups/{$filename}");
            $this->dispatch('toast', type: 'error', message: 'Arquivo inválido. Deve ser um backup .json.gz gerado pelo sistema.');
            return;
        }

        RestoreCompanyBackup::dispatch($companyId, $filename);

        $this->restoreFile = null;
        $this->dispatch('toast', type: 'success', message: 'Restauração em andamento... os dados serão atualizados em alguns segundos.');
    }

    public function deleteBackup(int $id): void
    {
        $backup = CompanyBackup::findOrFail($id);
        Storage::disk('local')->delete("backups/{$backup->filename}");
        $backup->delete();
        $this->dispatch('toast', type: 'success', message: 'Backup removido.');
    }

    public function render()
    {
        $backups = CompanyBackup::with('creator')->orderByDesc('created_at')->get();
        return view('livewire.admin.backup-manager', compact('backups'));
    }
}
