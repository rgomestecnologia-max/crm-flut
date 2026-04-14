<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $departments = Department::withCount('users', 'conversations')->get();
        return view('admin.departments.index', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'color'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon'        => ['required', 'string', 'max:100'],
        ]);

        Department::create($validated);
        return back()->with('success', 'Departamento criado com sucesso.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'color'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon'        => ['required', 'string', 'max:100'],
            'is_active'   => ['boolean'],
        ]);

        $department->update($validated);
        return back()->with('success', 'Departamento atualizado.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->users()->exists()) {
            return back()->with('error', 'Não é possível excluir um departamento com agentes vinculados.');
        }
        $department->delete();
        return back()->with('success', 'Departamento removido.');
    }
}
