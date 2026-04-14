<div class="w-full" style="max-width: 720px; padding: 40px 16px; position: relative; z-index: 10;">

    {{-- Header --}}
    <div style="text-align:center; margin-bottom:36px;">
        <div style="margin-bottom:18px;">
            <img src="/images/logo-flut.webp" alt="CRM Flut" style="height:40px; width:auto; display:inline-block;">
        </div>

        <h1 class="font-display" style="font-size:24px; font-weight:800; color:white; letter-spacing:-0.02em; line-height:1.2; margin-bottom:8px;">
            Selecionar empresa
        </h1>
        <p style="font-size:13px; color:rgba(255,255,255,0.4); letter-spacing:0.01em;">
            Olá, {{ auth()->user()->name }} — escolha qual empresa você quer gerenciar agora.
        </p>
    </div>

    {{-- Cards --}}
    @if($companies->isEmpty())
        <div style="text-align:center; padding:48px 24px; background:rgba(17,24,39,0.5); border:1px solid rgba(255,255,255,0.06); border-radius:16px;">
            <p style="font-size:13px; color:rgba(255,255,255,0.5);">Nenhuma empresa cadastrada ainda.</p>
        </div>
    @else
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:14px;">
            @foreach($companies as $company)
                <button wire:click="enter({{ $company->id }})"
                        wire:loading.attr="disabled"
                        style="background:linear-gradient(145deg, rgba(17,24,39,0.85) 0%, rgba(11,15,28,0.95) 100%);
                               border:1px solid rgba(255,255,255,0.07);
                               border-radius:16px;
                               padding:22px 20px;
                               cursor:pointer;
                               text-align:left;
                               transition:all 0.2s;
                               position:relative;
                               overflow:hidden;
                               color:inherit;
                               font-family:inherit;"
                        onmouseover="this.style.borderColor='{{ $company->color }}55'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 12px 32px rgba(0,0,0,0.4)';"
                        onmouseout="this.style.borderColor='rgba(255,255,255,0.07)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">

                    {{-- Color accent --}}
                    <div style="position:absolute; top:0; left:0; right:0; height:2px; background:{{ $company->color }}; opacity:0.7;"></div>

                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                        @if($company->logo_url)
                            <img src="{{ $company->logo_url }}" alt="{{ $company->name }}"
                                 style="width:42px; height:42px; border-radius:11px; object-fit:cover; border:1px solid rgba(255,255,255,0.08);">
                        @else
                            <div style="width:42px; height:42px; border-radius:11px; display:flex; align-items:center; justify-content:center; background:{{ $company->color }}1a; border:1px solid {{ $company->color }}40; flex-shrink:0;">
                                <span style="font-size:16px; font-weight:800; color:{{ $company->color }};">
                                    {{ strtoupper(substr($company->name, 0, 1)) }}
                                </span>
                            </div>
                        @endif
                        <div style="flex:1; min-width:0;">
                            <p style="font-size:13px; font-weight:700; color:white; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                {{ $company->name }}
                            </p>
                            <p style="font-size:10px; color:rgba(255,255,255,0.3); margin-top:2px;">{{ $company->slug }}</p>
                        </div>
                    </div>

                    <div style="display:flex; align-items:center; justify-content:space-between;">
                        <span style="font-size:11px; font-weight:600; color:{{ $company->color }};">Entrar →</span>
                        <span style="font-size:9px; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.1em;">Ativa</span>
                    </div>
                </button>
            @endforeach
        </div>
    @endif

    {{-- Footer --}}
    <div style="margin-top:28px; text-align:center;">
        <button wire:click="logout"
                style="font-size:12px; color:rgba(255,255,255,0.35); background:transparent; border:none; cursor:pointer; padding:8px 16px; transition:color 0.15s;"
                onmouseover="this.style.color='rgba(255,255,255,0.7)'"
                onmouseout="this.style.color='rgba(255,255,255,0.35)'">
            ← Sair
        </button>
    </div>
</div>
