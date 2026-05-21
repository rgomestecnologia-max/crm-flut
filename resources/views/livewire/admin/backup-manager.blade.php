<div style="max-width:800px; margin:0 auto; padding:24px 16px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
        <div>
            <h2 style="font-size:18px; font-weight:800; color:white; font-family:'Syne',sans-serif;">Backup & Restauração</h2>
            <p style="font-size:11px; color:rgba(255,255,255,0.3); margin-top:2px;">Gere backups completos dos dados da empresa (JSON compactado, sem mídias)</p>
        </div>
        <button wire:click="generateBackup"
                wire:confirm="Gerar backup completo de todos os dados da empresa?"
                style="display:flex; align-items:center; gap:6px; padding:8px 16px; background:rgba(178,255,0,0.08); border:1px solid rgba(178,255,0,0.2); border-radius:8px; color:#b2ff00; font-size:12px; font-weight:600; cursor:pointer;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Gerar Backup
        </button>
    </div>

    {{-- Lista de backups --}}
    @if($backups->isEmpty())
        <div style="text-align:center; padding:40px; color:rgba(255,255,255,0.3);">
            <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 12px; opacity:0.4;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            <p style="font-size:13px;">Nenhum backup gerado.</p>
        </div>
    @else
        <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:24px;">
            @foreach($backups as $bk)
            <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:12px; padding:14px 16px; display:flex; align-items:center; justify-content:space-between; gap:12px;">
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                        <span style="font-size:13px; font-weight:600; color:white;">{{ $bk->filename }}</span>
                        @if($bk->status === 'generating')
                        <span style="font-size:9px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(251,191,36,0.15); color:#fbbf24; border:1px solid rgba(251,191,36,0.3);">Gerando...</span>
                        @elseif($bk->status === 'ready')
                        <span style="font-size:9px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.15); color:#4ade80; border:1px solid rgba(34,197,94,0.3);">Pronto</span>
                        @else
                        <span style="font-size:9px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3);">Falhou</span>
                        @endif
                    </div>
                    <div style="display:flex; gap:16px; font-size:11px; color:rgba(255,255,255,0.4);">
                        <span>{{ $bk->created_at->format('d/m/Y H:i') }}</span>
                        @if($bk->status === 'ready')
                        <span>{{ $bk->sizeFormatted() }}</span>
                        <span>{{ number_format($bk->records_count, 0, ',', '.') }} registros</span>
                        <span>{{ $bk->tables_count }} tabelas</span>
                        @endif
                        @if($bk->creator)
                        <span>por {{ $bk->creator->name }}</span>
                        @endif
                    </div>
                </div>
                <div style="display:flex; gap:6px; flex-shrink:0;">
                    @if($bk->status === 'ready')
                    <button wire:click="downloadBackup({{ $bk->id }})"
                            style="font-size:11px; font-weight:600; color:#60a5fa; background:rgba(96,165,250,0.08); border:1px solid rgba(96,165,250,0.2); border-radius:6px; padding:4px 12px; cursor:pointer;">Download</button>
                    @endif
                    <button wire:click="deleteBackup({{ $bk->id }})" wire:confirm="Remover este backup?"
                            style="font-size:11px; color:#f87171; background:none; border:none; cursor:pointer;">Excluir</button>
                </div>
            </div>
            @endforeach
        </div>
    @endif

    {{-- Restauração --}}
    <div style="background:rgba(239,68,68,0.04); border:1px solid rgba(239,68,68,0.15); border-radius:16px; padding:20px;">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
            <div style="width:2px; height:16px; background:#ef4444; border-radius:2px;"></div>
            <h3 style="font-size:13px; font-weight:700; color:white;">Restaurar Backup</h3>
        </div>
        <p style="font-size:11px; color:rgba(239,68,68,0.6); margin-bottom:16px; line-height:1.5;">
            Atenção: a restauração <strong>substitui todos os dados atuais</strong> da empresa pelos dados do backup.
            Esta ação não pode ser desfeita. Recomendamos gerar um backup antes de restaurar.
        </p>
        <div style="display:flex; align-items:center; gap:10px;">
            <input type="file" wire:model="restoreFile" accept=".gz"
                   style="flex:1; font-size:12px; color:rgba(255,255,255,0.5);">
            <button wire:click="restoreBackup"
                    wire:confirm="ATENÇÃO: Isso vai SUBSTITUIR TODOS os dados da empresa. Tem certeza?"
                    wire:loading.attr="disabled"
                    style="padding:8px 16px; background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3); border-radius:8px; color:#f87171; font-size:12px; font-weight:700; cursor:pointer; white-space:nowrap;">
                <span wire:loading.remove wire:target="restoreBackup">Restaurar</span>
                <span wire:loading wire:target="restoreBackup">Restaurando...</span>
            </button>
        </div>
    </div>
</div>
