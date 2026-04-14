<?php

use App\Models\Conversation;
use App\Models\Department;
use App\Services\CurrentCompany;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal por departamento — verificações cumulativas:
//  1) o departamento existe e pertence à empresa "permitida" para o user;
//  2) o user pertence ao departamento (ou é admin).
Broadcast::channel('department.{departmentId}', function ($user, $departmentId) {
    // Sempre busca sem o global scope porque o canal /broadcasting/auth não passa
    // pelo middleware EnsureCurrentCompany.
    $department = Department::withoutCompanyScope()->find($departmentId);
    if (!$department) return false;

    // Empresa do recurso precisa bater com a empresa "ativa" do user.
    if (!userCanAccessCompany($user, (int) $department->company_id)) {
        return false;
    }

    if ($user->isAdmin()) return true;
    return $user->belongsToDepartment((int) $departmentId);
});

// Canal por conversa — agente de qualquer departamento da conversa, assignado, ou admin.
// Cumulativo: também valida que a empresa da conversa é acessível para o user.
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::withoutCompanyScope()->find($conversationId);
    if (!$conversation) return false;

    if (!userCanAccessCompany($user, (int) $conversation->company_id)) {
        return false;
    }

    if ($user->isAdmin()) return true;

    return $user->belongsToDepartment((int) $conversation->department_id)
        || (int) $user->id === (int) $conversation->assigned_to;
});

/**
 * Helper: verifica se o user pode acessar dados de uma empresa específica.
 *  - Agente/supervisor: precisa ter company_id == X.
 *  - Admin: precisa ter X selecionado na sessão atual (CurrentCompany).
 *    Isso impede que um admin "logado em RSG Group" receba broadcasts de outra empresa.
 */
function userCanAccessCompany($user, int $companyId): bool
{
    if (!$user->isAdmin()) {
        return (int) $user->company_id === $companyId;
    }
    return (int) app(CurrentCompany::class)->id() === $companyId;
}
