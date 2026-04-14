<x-layouts.auth>
    <div class="auth-card w-full form-reveal" style="max-width: 420px; padding: 40px; margin: 16px; position: relative; z-index: 10;">

        {{-- Top accent line --}}
        <div style="position:absolute; top:0; left:40px; right:40px; height:1px; background: linear-gradient(90deg, transparent, rgba(178,255,0,0.6), transparent); border-radius:1px;"></div>

        {{-- Header --}}
        <div style="text-align:center; margin-bottom: 36px;">
            {{-- Logo --}}
            <div style="margin-bottom:20px;">
                <img src="/images/logo-flut.webp" alt="CRM Flut" style="height:48px; width:auto; display:inline-block;">
            </div>

            <p style="font-size:13px; color:rgba(255,255,255,0.35); letter-spacing:0.02em;">
                Atendimento Inteligente
            </p>
        </div>

        {{-- Divider --}}
        <div style="height:1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent); margin-bottom:32px;"></div>

        {{-- Form --}}
        <form method="POST" action="{{ route('login') }}">
            @csrf

            {{-- Email --}}
            <div class="form-reveal" style="margin-bottom:18px;">
                <label style="display:block; font-size:11px; font-weight:500; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:8px;">
                    E-mail
                </label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="auth-input {{ $errors->has('email') ? 'error' : '' }}"
                    placeholder="agente@empresa.com"
                >
                @error('email')
                    <p style="color:#f87171; font-size:12px; margin-top:6px; display:flex; align-items:center; gap:4px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Password --}}
            <div class="form-reveal" style="margin-bottom:24px;">
                <label style="display:block; font-size:11px; font-weight:500; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:8px;">
                    Senha
                </label>
                <input
                    type="password"
                    name="password"
                    required
                    class="auth-input"
                    placeholder="••••••••"
                >
            </div>

            {{-- Remember me --}}
            <div class="form-reveal" style="display:flex; align-items:center; gap:8px; margin-bottom:28px;">
                <input type="checkbox" name="remember" class="auth-checkbox">
                <span style="font-size:13px; color:rgba(255,255,255,0.35);">Manter sessão ativa</span>
            </div>

            {{-- Submit --}}
            <div class="form-reveal">
                <button type="submit" class="auth-btn">
                    Acessar sistema
                </button>
            </div>
        </form>

        {{-- Footer --}}
        <p style="text-align:center; font-size:11px; color:rgba(255,255,255,0.15); margin-top:28px; letter-spacing:0.03em;">
            CRM Flut &copy; {{ date('Y') }}
        </p>

        {{-- Bottom corner accent --}}
        <div style="position:absolute; bottom:0; right:0; width:80px; height:80px; pointer-events:none; overflow:hidden; border-radius:0 0 24px 0;">
            <div style="position:absolute; bottom:-1px; right:-1px; width:40px; height:40px; border-right:1px solid rgba(178,255,0,0.2); border-bottom:1px solid rgba(178,255,0,0.2); border-radius:0 0 24px 0;"></div>
        </div>
    </div>
</x-layouts.auth>
