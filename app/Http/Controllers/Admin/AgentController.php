<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function index(): View
    {
        $agents      = User::with('department')->where('role', '!=', 'admin')->latest()->get();
        $departments = Department::active()->get();
        return view('admin.agents.index', compact('agents', 'departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                   => ['required', 'string', 'max:100'],
            'email'                  => ['required', 'email', 'unique:users,email'],
            'password'               => ['required', 'string', 'min:6'],
            'role'                   => ['required', Rule::in(['supervisor', 'agent'])],
            'department_id'          => ['required', 'exists:departments,id'],
            'extra_department_ids'   => ['array'],
            'extra_department_ids.*' => ['integer', 'exists:departments,id'],
        ]);

        $extras = $this->normalizeExtras($validated);

        $validated['password'] = Hash::make($validated['password']);
        unset($validated['extra_department_ids']);

        $user = User::create($validated);
        $user->departments()->sync(array_unique(array_merge([(int) $validated['department_id']], $extras)));

        return back()->with('success', 'Agente criado com sucesso.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name'                   => ['required', 'string', 'max:100'],
            'email'                  => ['required', 'email', Rule::unique('users')->ignore($user)],
            'role'                   => ['required', Rule::in(['supervisor', 'agent'])],
            'department_id'          => ['required', 'exists:departments,id'],
            'extra_department_ids'   => ['array'],
            'extra_department_ids.*' => ['integer', 'exists:departments,id'],
            'is_active'              => ['boolean'],
            'password'               => ['nullable', 'string', 'min:6'],
        ]);

        $extras = $this->normalizeExtras($validated);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        unset($validated['extra_department_ids']);

        $user->update($validated);
        $user->departments()->sync(array_unique(array_merge([(int) $validated['department_id']], $extras)));

        return back()->with('success', 'Agente atualizado.');
    }

    private function normalizeExtras(array $validated): array
    {
        return collect($validated['extra_department_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->reject(fn($id) => $id === (int) $validated['department_id'])
            ->unique()
            ->values()
            ->all();
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->with('error', 'Não é possível remover o administrador.');
        }
        $user->delete();
        return back()->with('success', 'Agente removido.');
    }
}
