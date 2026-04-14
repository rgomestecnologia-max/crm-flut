<?php

namespace App\Livewire\Admin;

use App\Models\Company;
use App\Services\CompanyProvisioner;
use App\Services\CurrentCompany;
use Illuminate\Support\Facades\Auth;
use App\Services\MediaStorage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class CompanyManager extends Component
{
    use WithFileUploads;

    public bool   $showForm   = false;
    public ?int   $editingId  = null;
    public string $name       = '';
    public string $color      = '#b2ff00';
    public bool   $is_active  = true;
    public array  $modules    = [];
    public        $logoUpload = null;
    public ?string $existingLogo = null;

    /** Estado do modal de exclusão. */
    public ?int   $deletingId       = null;
    public string $deleteConfirmName = '';

    public function mount(): void
    {
        if (!Auth::user()?->isAdmin()) {
            abort(403);
        }
    }

    public function openCreate(): void
    {
        $this->reset('editingId', 'name', 'color', 'is_active', 'modules', 'logoUpload', 'existingLogo');
        $this->color     = '#b2ff00';
        $this->is_active = true;
        $this->modules   = Company::allModuleKeys(); // por padrão todos habilitados
        $this->showForm  = true;
    }

    public function openEdit(int $id): void
    {
        $company = Company::findOrFail($id);
        $this->editingId    = $id;
        $this->name         = $company->name;
        $this->color        = $company->color ?? '#b2ff00';
        $this->is_active    = $company->is_active;
        $this->modules      = $company->modules ?? [];
        $this->existingLogo = $company->logo;
        $this->logoUpload   = null;
        $this->showForm     = true;
    }

    public function removeExistingLogo(): void
    {
        if (!$this->editingId) return;

        $company = Company::findOrFail($this->editingId);
        if ($company->logo) {
            MediaStorage::delete($company->logo);
            $company->update(['logo' => null]);
        }
        $this->existingLogo = null;
        $this->dispatch('toast', type: 'success', message: 'Logo removida.');
    }

    public function save(CompanyProvisioner $provisioner): void
    {
        $rules = [
            'name'       => 'required|string|max:100',
            'color'      => 'nullable|string|max:9',
            'is_active'  => 'boolean',
            'modules'    => 'array',
            'modules.*'  => 'string',
            'logoUpload' => 'nullable|image|max:2048',
        ];

        $validated = $this->validate($rules);

        // Filtra só módulos válidos
        $validModules = array_values(array_intersect(
            $this->modules,
            Company::allModuleKeys()
        ));

        // Persiste a logo se enviou nova
        $logoPath = null;
        if ($this->logoUpload) {
            $logoPath = MediaStorage::store($this->logoUpload, 'companies');
        }

        $attributes = [
            'name'      => $validated['name'],
            'color'     => $validated['color'] ?? '#b2ff00',
            'is_active' => $validated['is_active'] ?? true,
            'modules'   => $validModules,
        ];

        if ($this->editingId) {
            $company = Company::findOrFail($this->editingId);
            // Substitui logo: deleta a antiga só se estamos enviando uma nova
            if ($logoPath) {
                if ($company->logo) {
                    MediaStorage::delete($company->logo);
                }
                $attributes['logo'] = $logoPath;
            }
            $company->update($attributes);
            $this->dispatch('toast', type: 'success', message: 'Empresa atualizada.');
        } else {
            if ($logoPath) {
                $attributes['logo'] = $logoPath;
            }
            $company = $provisioner->create($attributes);
            $this->dispatch('toast', type: 'success', message: "Empresa \"{$company->name}\" criada com template inicial (1 departamento + 1 pipeline).");
        }

        $this->showForm = false;
        $this->reset('editingId', 'name', 'color', 'is_active', 'logoUpload', 'existingLogo');
    }

    public function toggleActive(int $id): void
    {
        $company = Company::findOrFail($id);
        $company->update(['is_active' => !$company->is_active]);
        $this->dispatch('toast', type: 'success', message: $company->is_active ? 'Empresa ativada.' : 'Empresa desativada.');
    }

    public function openDelete(int $id): void
    {
        $this->deletingId        = $id;
        $this->deleteConfirmName = '';
    }

    public function cancelDelete(): void
    {
        $this->deletingId        = null;
        $this->deleteConfirmName = '';
    }

    public function confirmDelete(): void
    {
        if (!$this->deletingId) return;

        $company = Company::findOrFail($this->deletingId);

        // Defesa 1: não permite deletar a empresa atual em uso pelo admin.
        $current = app(CurrentCompany::class)->id();
        if ($current && (int) $current === (int) $company->id) {
            $this->dispatch('toast', type: 'error', message: 'Você está dentro dessa empresa agora. Troque para outra antes de excluir.');
            return;
        }

        // Defesa 2: não permite deletar a última empresa do sistema.
        if (Company::count() <= 1) {
            $this->dispatch('toast', type: 'error', message: 'Não é possível excluir a única empresa do sistema.');
            return;
        }

        // Defesa 3: a confirmação textual deve bater com o nome.
        if (trim($this->deleteConfirmName) !== $company->name) {
            $this->addError('deleteConfirmName', 'O nome digitado não bate com o nome da empresa.');
            return;
        }

        // Apaga a logo do storage se houver
        if ($company->logo) {
            MediaStorage::delete($company->logo);
        }

        $name = $company->name;
        // Cascade delete via FK derruba todas as tabelas dependentes (Fase 2 configurou cascadeOnDelete).
        $company->delete();

        $this->deletingId        = null;
        $this->deleteConfirmName = '';
        $this->dispatch('toast', type: 'success', message: "Empresa \"{$name}\" excluída com todos os seus dados.");
    }

    public function render()
    {
        $companies = Company::orderBy('name')->get()->loadCount('users');
        return view('livewire.admin.company-manager', compact('companies'));
    }
}
