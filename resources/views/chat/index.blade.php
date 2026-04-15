<x-layouts.app>
    <x-slot:title>Atendimento — {{ config('app.name') }}</x-slot:title>

    <div x-data="{ chatOpen: {{ $activeConversation ? 'true' : 'false' }} }"
         @conversation-selected.window="chatOpen = true"
         @conversation-deleted.window="chatOpen = false"
         style="display:flex; height:100%; min-height:0; flex:1; overflow:hidden;">

        {{-- Painel: Lista de conversas --}}
        <div :class="{ 'mobile-hide': chatOpen }"
             class="chat-list-panel"
             style="width:380px; min-width:380px; max-width:380px; height:100%; border-right:1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; flex-shrink:0;">
            <livewire:chat.conversation-list :activeId="$activeConversation?->id" />
        </div>

        {{-- Painel: Área do chat --}}
        <div :class="{ 'mobile-hide': !chatOpen }"
             class="chat-area-panel"
             style="flex:1; height:100%; display:flex; flex-direction:column; min-width:0;">
            <livewire:chat.chat-area :conversationId="$activeConversation?->id" />
        </div>
    </div>

    <style>
        @media (max-width: 768px) {
            .chat-list-panel {
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100% !important;
                border-right: none !important;
            }
            .chat-area-panel {
                width: 100% !important;
            }
        }
    </style>
</x-layouts.app>
