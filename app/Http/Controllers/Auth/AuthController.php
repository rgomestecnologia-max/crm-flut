<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectAfterLogin();
        }
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt(array_merge($credentials, ['is_active' => true]), $remember)) {
            $request->session()->regenerate();
            Auth::user()->update(['status' => 'online']);
            return $this->redirectAfterLogin();
        }

        return back()->withErrors([
            'email' => 'Credenciais inválidas ou conta inativa.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::user()?->update(['status' => 'offline']);
        Auth::logout();
        // Limpa também a empresa atual da sessão antes de invalidar.
        app(\App\Services\CurrentCompany::class)->clear();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    /**
     * Admin → tela de seleção de empresa.
     * Agente/supervisor → dashboard direto (middleware seta a empresa dele).
     */
    protected function redirectAfterLogin(): RedirectResponse
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return redirect()->route('companies.select');
        }
        return redirect()->intended(route('dashboard'));
    }
}
