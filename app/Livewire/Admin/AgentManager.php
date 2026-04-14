<?php

namespace App\Livewire\Admin;

use App\Models\Department;
use App\Models\User;
use App\Services\CurrentCompany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AgentManager extends Component
{
    public bool   $showForm    = false;
    public ?int   $editingId   = null;
    public string $name        = '';
    public string $email       = '';
    public string $password    = '';
    public string $role        = 'agent';
    public ?int   $department_id = null;
    /** Departamentos adicionais (além do principal). */
    public array  $extra_department_ids = [];
    public bool   $is_active   = true;

    public function openCreate(): void
    {
        $this->reset('editingId', 'name', 'email', 'password', 'role', 'department_id', 'extra_department_ids', 'is_active');
        $this->role      = 'agent';
        $this->is_active = true;
        $this->showForm  = true;
    }

    public function openEdit(int $id): void
    {
        $user = $this->findAgentInCurrentCompany($id);
        $user->load('departments');
        $this->editingId            = $id;
        $this->name                 = $user->name;
        $this->email                = $user->email;
        $this->password             = '';
        $this->role                 = $user->role;
        $this->department_id        = $user->department_id;
        $this->extra_department_ids = $user->departments
            ->pluck('id')
            ->reject(fn($id) => $id === $user->department_id)
            ->values()
            ->all();
        $this->is_active            = $user->is_active;
        $this->showForm             = true;
    }

    public function updatedDepartmentId($value): void
    {
        // Se o usuário move um departamento extra para principal, remove da lista de extras.
        $value = $value ? (int) $value : null;
        $this->extra_department_ids = array_values(array_filter(
            $this->extra_department_ids,
            fn($id) => (int) $id !== $value
        ));
    }

    public function save(): void
    {
        $rules = [
            'name'                   => 'required|string|max:100',
            'email'                  => ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingId)],
            'role'                   => ['required', Rule::in(['supervisor', 'agent'])],
            'department_id'          => 'required|exists:departments,id',
            'extra_department_ids'   => 'array',
            'extra_department_ids.*' => 'integer|exists:departments,id',
            'is_active'              => 'boolean',
        ];

        if (!$this->editingId) {
            $rules['password'] = 'required|string|min:6';
        } else {
            $rules['password'] = 'nullable|string|min:6';
        }

        $validated = $this->validate($rules);

        // Limpa duplicado/principal entre os extras
        $extraIds = collect($validated['extra_department_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->reject(fn($id) => $id === (int) $validated['department_id'])
            ->unique()
            ->values()
            ->all();

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // extra_department_ids não é coluna de users — separar antes do save
        unset($validated['extra_department_ids']);

        $currentCompanyId = app(CurrentCompany::class)->id();

        if ($this->editingId) {
            // Defesa: só pode editar agentes da empresa atual.
            $user = $this->findAgentInCurrentCompany($this->editingId);
            $user->update($validated);
            $this->dispatch('toast', type: 'success', message: 'Agente atualizado.');
        } else {
            // Auto-preenche company_id com a empresa do admin logado.
            $validated['company_id'] = $currentCompanyId;
            $user = User::create($validated);
            $this->dispatch('toast', type: 'success', message: 'Agente criado.');
        }

        // Sincroniza pivô com principal + extras (sempre inclui o principal).
        $allDeptIds = array_values(array_unique(array_merge(
            [(int) $validated['department_id']],
            $extraIds,
        )));
        $user->departments()->sync($allDeptIds);

        $this->showForm = false;
        $this->reset('editingId', 'name', 'email', 'password', 'role', 'department_id', 'extra_department_ids');
    }

    public function toggleActive(int $id): void
    {
        $user = $this->findAgentInCurrentCompany($id);
        $user->update(['is_active' => !$user->is_active]);
        $this->dispatch('toast', type: 'success', message: $user->is_active ? 'Agente ativado.' : 'Agente desativado.');
    }

    public function render()
    {
        $companyId = app(CurrentCompany::class)->id();

        $agents = User::with(['department', 'departments'])
            ->where('role', '!=', 'admin')
            ->where('company_id', $companyId)
            ->latest()
            ->get();
        $departments = Department::active()->get();
        return view('livewire.admin.agent-manager', compact('agents', 'departments'));
    }

    /**
     * Busca um agente garantindo que pertence à empresa atual.
     * Lança 404 se for de outra empresa — defesa contra payload manipulado.
     */
    protected function findAgentInCurrentCompany(int $id): User
    {
        $companyId = app(CurrentCompany::class)->id();

        return User::where('id', $id)
            ->where('company_id', $companyId)
            ->where('role', '!=', 'admin')
            ->firstOrFail();
    }
}
