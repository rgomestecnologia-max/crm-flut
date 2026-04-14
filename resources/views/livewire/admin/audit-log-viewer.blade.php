<div>
    {{-- Header --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <div>
            <h2 style="font-size:18px; font-weight:800; color:white; font-family:'Syne',sans-serif; letter-spacing:-0.02em;">Auditoria</h2>
            <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-top:2px;">Registro de todas as movimentações da empresa</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div style="display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap;">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por usuário ou recurso..."
               style="flex:1; min-width:200px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:8px 14px; font-size:12px; color:white; outline:none; font-family:inherit; box-sizing:border-box;"
               onfocus="this.style.borderColor='rgba(178,255,0,0.4)'" onblur="this.style.borderColor='rgba(255,255,255,0.08)'">

        <select wire:model.live="filterAction"
                style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:8px 14px; font-size:12px; color:white; outline:none; font-family:inherit;">
            <option value="">Todas ações</option>
            <option value="created">Criações</option>
            <option value="updated">Alterações</option>
            <option value="deleted">Exclusões</option>
        </select>

        <select wire:model.live="filterModel"
                style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:8px 14px; font-size:12px; color:white; outline:none; font-family:inherit;">
            <option value="">Todos os recursos</option>
            @foreach($modelTypes as $type)
                @php $label = (new $type)->model_label ?? class_basename($type); @endphp
                <option value="{{ $type }}">{{ app(\App\Models\AuditLog::class)->setAttribute('auditable_type', $type)->model_label }}</option>
            @endforeach
        </select>
    </div>

    {{-- Table --}}
    <div style="background:linear-gradient(145deg, rgba(17,24,39,0.8) 0%, rgba(11,15,28,0.9) 100%); border:1px solid rgba(255,255,255,0.06); border-radius:16px; overflow:hidden;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                    <th style="text-align:left; padding:12px 16px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Data</th>
                    <th style="text-align:left; padding:12px 16px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Usuário</th>
                    <th style="text-align:left; padding:12px 16px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Ação</th>
                    <th style="text-align:left; padding:12px 16px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Recurso</th>
                    <th style="text-align:left; padding:12px 16px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Detalhes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr style="border-bottom:1px solid rgba(255,255,255,0.03); transition:background 0.15s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.02)'"
                    onmouseout="this.style.background='transparent'"
                    x-data="{ open: false }">
                    <td style="padding:10px 16px; white-space:nowrap;">
                        <p style="font-size:11px; color:rgba(255,255,255,0.5);">{{ $log->created_at->format('d/m/Y') }}</p>
                        <p style="font-size:10px; color:rgba(255,255,255,0.25);">{{ $log->created_at->format('H:i:s') }}</p>
                    </td>
                    <td style="padding:10px 16px;">
                        <p style="font-size:12px; font-weight:600; color:rgba(255,255,255,0.75);">{{ $log->user_name }}</p>
                        @if($log->ip_address)
                            <p style="font-size:9px; color:rgba(255,255,255,0.2); font-family:monospace;">{{ $log->ip_address }}</p>
                        @endif
                    </td>
                    <td style="padding:10px 16px;">
                        <span style="font-size:10px; font-weight:700; padding:3px 8px; border-radius:20px; background:{{ $log->action_color }}18; color:{{ $log->action_color }}; border:1px solid {{ $log->action_color }}40;">
                            {{ $log->action_label }}
                        </span>
                    </td>
                    <td style="padding:10px 16px;">
                        <p style="font-size:11px; color:rgba(255,255,255,0.4);">{{ $log->model_label }}</p>
                        <p style="font-size:12px; font-weight:600; color:rgba(255,255,255,0.7);">{{ \Illuminate\Support\Str::limit($log->auditable_label, 40) }}</p>
                    </td>
                    <td style="padding:10px 16px;">
                        @if($log->old_values || $log->new_values)
                        <button @click="open = !open"
                                style="font-size:10px; font-weight:600; color:#b2ff00; background:rgba(178,255,0,0.08); border:1px solid rgba(178,255,0,0.2); border-radius:6px; padding:4px 10px; cursor:pointer; transition:all 0.15s;"
                                onmouseover="this.style.background='rgba(178,255,0,0.16)'"
                                onmouseout="this.style.background='rgba(178,255,0,0.08)'"
                                x-text="open ? 'Ocultar' : 'Ver'">
                        </button>
                        @else
                            <span style="font-size:10px; color:rgba(255,255,255,0.15);">—</span>
                        @endif
                    </td>
                </tr>
                {{-- Linha expandida com detalhes --}}
                @if($log->old_values || $log->new_values)
                <tr x-show="open" x-cloak style="background:rgba(255,255,255,0.01);">
                    <td colspan="5" style="padding:0 16px 14px;">
                        <div style="display:flex; gap:16px; padding:12px; background:rgba(0,0,0,0.2); border-radius:10px; margin-top:4px; overflow-x:auto;">
                            @if($log->old_values)
                            <div style="flex:1; min-width:200px;">
                                <p style="font-size:9px; font-weight:700; color:rgba(239,68,68,0.6); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;">Antes</p>
                                @foreach($log->old_values as $key => $value)
                                    <div style="display:flex; gap:6px; margin-bottom:3px;">
                                        <span style="font-size:10px; color:rgba(255,255,255,0.3); min-width:100px;">{{ $key }}:</span>
                                        <span style="font-size:10px; color:rgba(255,255,255,0.5); word-break:break-all;">{{ is_array($value) ? json_encode($value) : \Illuminate\Support\Str::limit((string)$value, 80) }}</span>
                                    </div>
                                @endforeach
                            </div>
                            @endif
                            @if($log->new_values)
                            <div style="flex:1; min-width:200px;">
                                <p style="font-size:9px; font-weight:700; color:rgba(34,197,94,0.6); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;">Depois</p>
                                @foreach($log->new_values as $key => $value)
                                    <div style="display:flex; gap:6px; margin-bottom:3px;">
                                        <span style="font-size:10px; color:rgba(255,255,255,0.3); min-width:100px;">{{ $key }}:</span>
                                        <span style="font-size:10px; color:rgba(255,255,255,0.5); word-break:break-all;">{{ is_array($value) ? json_encode($value) : \Illuminate\Support\Str::limit((string)$value, 80) }}</span>
                                    </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="5" style="padding:48px 20px; text-align:center;">
                        <div style="display:flex; flex-direction:column; align-items:center; gap:10px; color:rgba(255,255,255,0.15);">
                            <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity:0.4;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p style="font-size:13px;">Nenhuma movimentação registrada ainda.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($logs->hasPages())
    <div style="padding:12px; font-size:12px; color:rgba(255,255,255,0.3);">
        {{ $logs->links() }}
    </div>
    @endif
</div>
