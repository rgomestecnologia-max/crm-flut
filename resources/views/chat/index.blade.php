<x-layouts.app>
    <x-slot:title>Atendimento — {{ config('app.name') }}</x-slot:title>

    <div style="display:flex; height:100%; min-height:0; flex:1; overflow:hidden;">
        {{-- Painel: Lista de conversas (largura fixa, altura total) --}}
        <div style="width:380px; min-width:380px; max-width:380px; height:100%; border-right:1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; flex-shrink:0;">
            <livewire:chat.conversation-list :activeId="$activeConversation?->id" />
        </div>

        {{-- Painel: Área do chat --}}
        <div style="flex:1; height:100%; display:flex; flex-direction:column; min-width:0;">
            <livewire:chat.chat-area :conversationId="$activeConversation?->id" />
        </div>
    </div>
</x-layouts.app>
