<div>
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
        <div>
            <h2 style="font-size:18px; font-weight:800; color:white; font-family:'Syne',sans-serif;">Agenda de Tarefas</h2>
            <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-top:2px;">Tarefas dos cards CRM organizadas por dia</p>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <button wire:click="$set('selectedDate', '{{ \Carbon\Carbon::parse($selectedDate)->subDay()->format('Y-m-d') }}')"
                    style="padding:6px 10px; font-size:14px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:rgba(255,255,255,0.5); cursor:pointer;">←</button>
            <input wire:model.live="selectedDate" type="date"
                   style="padding:6px 12px; font-size:13px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:white; outline:none; color-scheme:dark; font-weight:600;">
            <button wire:click="$set('selectedDate', '{{ \Carbon\Carbon::parse($selectedDate)->addDay()->format('Y-m-d') }}')"
                    style="padding:6px 10px; font-size:14px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:rgba(255,255,255,0.5); cursor:pointer;">→</button>
            <button wire:click="$set('selectedDate', '{{ now()->format('Y-m-d') }}')"
                    style="padding:6px 12px; font-size:11px; font-weight:600; background:rgba(178,255,0,0.08); border:1px solid rgba(178,255,0,0.2); border-radius:8px; color:#b2ff00; cursor:pointer;">Hoje</button>
        </div>
    </div>

    {{-- Resumo --}}
    <div style="display:flex; gap:12px; margin-bottom:20px;">
        <div style="padding:12px 20px; background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.2); border-radius:10px;">
            <p style="font-size:20px; font-weight:800; color:#f59e0b;">{{ $pending }}</p>
            <p style="font-size:10px; color:rgba(255,255,255,0.4);">Pendentes</p>
        </div>
        <div style="padding:12px 20px; background:rgba(34,197,94,0.08); border:1px solid rgba(34,197,94,0.2); border-radius:10px;">
            <p style="font-size:20px; font-weight:800; color:#22c55e;">{{ $completed }}</p>
            <p style="font-size:10px; color:rgba(255,255,255,0.4);">Concluídas</p>
        </div>
    </div>

    {{-- Lista de tarefas --}}
    @forelse($tasks as $task)
    <div style="display:flex; align-items:flex-start; gap:12px; padding:14px 16px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); border-radius:12px; margin-bottom:8px; transition:all 0.15s;"
         onmouseover="this.style.background='rgba(255,255,255,0.04)'" onmouseout="this.style.background='rgba(255,255,255,0.02)'">
        <input type="checkbox" wire:click="toggleTask({{ $task->id }})" {{ $task->is_completed ? 'checked' : '' }}
               style="margin-top:3px; accent-color:#f59e0b; cursor:pointer; width:16px; height:16px;">
        <div style="flex:1; min-width:0;">
            <p style="font-size:13px; font-weight:600; color:{{ $task->is_completed ? 'rgba(255,255,255,0.3)' : 'white' }}; {{ $task->is_completed ? 'text-decoration:line-through;' : '' }}">
                {{ $task->title }}
            </p>
            <div style="display:flex; align-items:center; gap:12px; margin-top:4px; flex-wrap:wrap;">
                @if($task->due_time)
                <span style="font-size:10px; color:rgba(255,255,255,0.4);">🕐 {{ \Carbon\Carbon::parse($task->due_time)->format('H:i') }}</span>
                @endif
                @if($task->card)
                <span style="font-size:10px; color:rgba(139,92,246,0.8);">📋 {{ $task->card->title }}</span>
                @if($task->card->pipeline)
                <span style="font-size:9px; padding:2px 8px; border-radius:10px; background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.3);">{{ $task->card->pipeline->name }} → {{ $task->card->stage?->name }}</span>
                @endif
                @if($task->card->contact)
                <span style="font-size:10px; color:rgba(255,255,255,0.3);">👤 {{ $task->card->contact->name }}</span>
                @endif
                @endif
            </div>
        </div>
    </div>
    @empty
    <div style="text-align:center; padding:40px; color:rgba(255,255,255,0.15);">
        <p style="font-size:32px; margin-bottom:8px;">📋</p>
        <p style="font-size:13px;">Nenhuma tarefa para {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</p>
    </div>
    @endforelse
</div>
