<?php

namespace App\Livewire\Admin;

use App\Models\Department;
use Livewire\Component;

class DepartmentManager extends Component
{
    public bool   $showForm    = false;
    public ?int   $editingId   = null;
    public string $name        = '';
    public string $description = '';
    public string $color       = '#b2ff00';
    public string $icon        = 'chat-bubble-left-right';
    public bool   $is_active   = true;
    public int    $sort_order  = 0;

    public function openCreate(): void
    {
        $this->reset('editingId', 'name', 'description', 'color', 'icon', 'is_active', 'sort_order');
        $this->color      = '#b2ff00';
        $this->icon       = 'chat-bubble-left-right';
        $this->is_active  = true;
        $this->sort_order = Department::max('sort_order') + 1;
        $this->showForm   = true;
    }

    public function openEdit(int $id): void
    {
        $dept = Department::findOrFail($id);
        $this->editingId   = $id;
        $this->name        = $dept->name;
        $this->description = $dept->description ?? '';
        $this->color       = $dept->color;
        $this->icon        = $dept->icon;
        $this->is_active   = $dept->is_active;
        $this->sort_order  = $dept->sort_order ?? 0;
        $this->showForm    = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'color'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon'        => 'required|string|max:100',
            'is_active'   => 'boolean',
            'sort_order'  => 'integer|min:0',
        ]);

        if ($this->editingId) {
            Department::findOrFail($this->editingId)->update($validated);
            $this->dispatch('toast', type: 'success', message: 'Departamento atualizado.');
        } else {
            Department::create($validated);
            $this->dispatch('toast', type: 'success', message: 'Departamento criado.');
        }

        $this->showForm = false;
        $this->reset('editingId', 'name', 'description', 'color', 'icon', 'sort_order');
    }

    public function delete(int $id): void
    {
        $dept = Department::findOrFail($id);
        if ($dept->users()->exists()) {
            $this->dispatch('toast', type: 'error', message: 'Remova os agentes antes de excluir.');
            return;
        }
        $dept->delete();
        $this->dispatch('toast', type: 'success', message: 'Departamento removido.');
    }

    public function render()
    {
        $departments = Department::withCount('users', 'conversations')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        return view('livewire.admin.department-manager', compact('departments'));
    }
}
