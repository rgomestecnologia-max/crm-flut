@if($current)
<div style="padding:10px 12px; border-bottom:1px solid rgba(255,255,255,0.05); flex-shrink:0; position:relative;"
     x-data="{ open: false }"
     @click.outside="open = false">

    @if(auth()->user()->isAdmin())
    {{-- Botão clicável pra admin --}}
    <button @click="open = !open"
            type="button"
            style="display:flex; align-items:center; gap:8px; width:100%; background:transparent; border:none; cursor:pointer; padding:0; text-align:left; color:inherit; font-family:inherit;">
        <div style="width:26px; height:26px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:{{ $current->color }}1a; border:1px solid {{ $current->color }}40; flex-shrink:0;">
            @if($current->logo_url)
                <img src="{{ $current->logo_url }}" alt="" style="width:24px; height:24px; border-radius:7px; object-fit:cover;">
            @else
                <span style="font-size:11px; font-weight:800; color:{{ $current->color }};">
                    {{ strtoupper(substr($current->name, 0, 1)) }}
                </span>
            @endif
        </div>
        <div x-show="$root.closest('aside').style.width !== '56px'" style="flex:1; min-width:0; overflow:hidden;">
            <p style="font-size:9px; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; line-height:1; margin-bottom:2px;">Empresa</p>
            <p style="font-size:11px; font-weight:700; color:white; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                {{ $current->name }}
            </p>
        </div>
        <svg x-show="$root.closest('aside').style.width !== '56px'" width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"
             style="color:rgba(255,255,255,0.3); transition:transform 0.15s; flex-shrink:0;"
             :style="open ? 'transform: rotate(180deg)' : ''">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Dropdown fixo (evita corte do overflow:hidden da sidebar) --}}
    <template x-teleport="body">
        <div x-show="open"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.outside="open = false"
             style="position:fixed; top:130px; left:12px; z-index:99990; min-width:220px; max-width:280px; background:linear-gradient(145deg, #11182F 0%, #0B0F1C 100%); border:1px solid rgba(255,255,255,0.1); border-radius:12px; box-shadow:0 20px 50px rgba(0,0,0,0.6); overflow:hidden; max-height:380px; overflow-y:auto;">

            <p style="font-size:9px; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; padding:10px 12px 6px;">Trocar para</p>

            @foreach($companies as $company)
                @php $isCurrent = $company->id === $current->id; @endphp
                <form method="POST" action="{{ route('companies.enter', $company) }}" style="margin:0;">
                    @csrf
                    <button type="submit"
                            @click="open = false"
                            style="display:flex; align-items:center; gap:9px; width:100%; padding:8px 12px; background:{{ $isCurrent ? 'rgba(178,255,0,0.06)' : 'transparent' }}; border:none; cursor:pointer; transition:background 0.12s; text-align:left; color:inherit; font-family:inherit;"
                            onmouseover="this.style.background='rgba(255,255,255,0.04)'"
                            onmouseout="this.style.background='{{ $isCurrent ? 'rgba(178,255,0,0.06)' : 'transparent' }}'">
                        <div style="width:24px; height:24px; border-radius:7px; display:flex; align-items:center; justify-content:center; background:{{ $company->color }}1a; border:1px solid {{ $company->color }}40; flex-shrink:0;">
                            @if($company->logo_url)
                                <img src="{{ $company->logo_url }}" alt="" style="width:22px; height:22px; border-radius:6px; object-fit:cover;">
                            @else
                                <span style="font-size:10px; font-weight:800; color:{{ $company->color }};">
                                    {{ strtoupper(substr($company->name, 0, 1)) }}
                                </span>
                            @endif
                        </div>
                        <p style="flex:1; min-width:0; font-size:11px; font-weight:600; color:{{ $isCurrent ? '#b2ff00' : 'rgba(255,255,255,0.8)' }}; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            {{ $company->name }}
                        </p>
                        @if($isCurrent)
                        <svg width="12" height="12" fill="none" stroke="#b2ff00" viewBox="0 0 24 24" style="flex-shrink:0;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                        @endif
                    </button>
                </form>
            @endforeach

            <div style="border-top:1px solid rgba(255,255,255,0.05); padding:6px;">
                <a href="{{ route('admin.companies.index') }}"
                   @click="open = false"
                   style="display:flex; align-items:center; gap:7px; padding:7px 8px; font-size:11px; color:rgba(255,255,255,0.5); text-decoration:none; border-radius:7px; transition:all 0.12s;"
                   onmouseover="this.style.background='rgba(255,255,255,0.04)'; this.style.color='white'"
                   onmouseout="this.style.background='transparent'; this.style.color='rgba(255,255,255,0.5)'">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Gerenciar empresas
                </a>
            </div>
        </div>
    </template>

    @else
    {{-- Não-admin: só mostra a empresa, sem dropdown --}}
    <div style="display:flex; align-items:center; gap:8px;">
        <div style="width:26px; height:26px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:{{ $current->color }}1a; border:1px solid {{ $current->color }}40; flex-shrink:0;">
            @if($current->logo_url)
                <img src="{{ $current->logo_url }}" alt="" style="width:24px; height:24px; border-radius:7px; object-fit:cover;">
            @else
                <span style="font-size:11px; font-weight:800; color:{{ $current->color }};">
                    {{ strtoupper(substr($current->name, 0, 1)) }}
                </span>
            @endif
        </div>
        <div style="flex:1; min-width:0; overflow:hidden;">
            <p style="font-size:9px; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; line-height:1; margin-bottom:2px;">Empresa</p>
            <p style="font-size:11px; font-weight:700; color:white; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                {{ $current->name }}
            </p>
        </div>
    </div>
    @endif
</div>
@endif
