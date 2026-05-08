@php
$labels = [
    'company_name'       => 'Empresa',
    'cnpj'               => 'CNPJ',
    'segment'            => 'Segmento',
    'website'            => 'Site',
    'social_media'       => 'Redes sociais',
    'brand_color'        => 'Cor da marca',
    'whatsapp_number'    => 'WhatsApp',
    'has_whatsapp_business' => 'WhatsApp Business?',
    'agents_count'       => 'Atendentes',
    'departments'        => 'Departamentos',
    'department_leads'   => 'Responsáveis',
    'sales_pipeline'     => 'Pipeline de vendas',
    'custom_fields'      => 'Campos personalizados',
    'company_description'=> 'Descrição da empresa',
    'voice_tone'         => 'Tom de voz',
    'business_hours'     => 'Horário',
    'faq'                => 'FAQ',
    'site_for_ai'        => 'Site para IA',
    'has_catalog'        => 'Tem catálogo?',
    'has_site_leads'     => 'Leads do site?',
    'auto_message'       => 'Mensagem automática',
    'want_followup'      => 'Follow-up?',
    'contact_name'       => 'Contato',
    'contact_email'      => 'Email',
    'contact_phone'      => 'Telefone',
    'notes'              => 'Observações',
    'submitted_at'       => 'Enviado em',
];
$skipKeys = ['_file', 'logo_path', 'catalog_paths', '_token'];
@endphp

<div>
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <div>
            <h2 style="font-size:18px; font-weight:800; color:white; font-family:'Syne',sans-serif;">Onboardings</h2>
            <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-top:2px;">Formulários de implementação recebidos</p>
        </div>
        <span style="font-size:12px; color:rgba(255,255,255,0.3);">{{ $submissions->count() }} recebido(s)</span>
    </div>

    @forelse($submissions as $sub)
    <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid {{ $expandedFile === $sub['_file'] ? 'rgba(178,255,0,0.2)' : 'rgba(255,255,255,0.06)' }}; border-radius:14px; margin-bottom:12px; overflow:hidden; transition:all 0.2s;">
        {{-- Header --}}
        <div wire:click="toggle('{{ $sub['_file'] }}')" style="padding:16px 20px; cursor:pointer; display:flex; align-items:center; gap:14px;"
             onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">

            @if(!empty($sub['brand_color']))
            <div style="width:36px; height:36px; border-radius:10px; background:{{ $sub['brand_color'] }}20; border:1px solid {{ $sub['brand_color'] }}40; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <span style="font-size:14px; font-weight:800; color:{{ $sub['brand_color'] }};">{{ strtoupper(substr($sub['company_name'] ?? '?', 0, 1)) }}</span>
            </div>
            @endif

            <div style="flex:1; min-width:0;">
                <p style="font-size:14px; font-weight:700; color:white;">{{ $sub['company_name'] ?? 'Sem nome' }}</p>
                <p style="font-size:11px; color:rgba(255,255,255,0.3);">
                    {{ $sub['segment'] ?? '' }}
                    @if(!empty($sub['contact_name'])) — {{ $sub['contact_name'] }} @endif
                    @if(!empty($sub['contact_phone'])) ({{ $sub['contact_phone'] }}) @endif
                </p>
            </div>

            <div style="text-align:right; flex-shrink:0;">
                <p style="font-size:11px; color:rgba(255,255,255,0.25);">{{ $sub['submitted_at'] ?? '' }}</p>
                @if(!empty($sub['agents_count']))
                <p style="font-size:10px; color:rgba(255,255,255,0.15);">{{ $sub['agents_count'] }} atendentes</p>
                @endif
            </div>

            <svg width="16" height="16" fill="none" stroke="rgba(255,255,255,0.3)" viewBox="0 0 24 24" style="flex-shrink:0; transition:transform 0.2s; transform:rotate({{ $expandedFile === $sub['_file'] ? '180' : '0' }}deg);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>

        {{-- Detalhes expandidos --}}
        @if($expandedFile === $sub['_file'] && $expandedData)
        <div style="padding:0 20px 20px; border-top:1px solid rgba(255,255,255,0.04);">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:16px;" class="mobile-grid-1">
                @foreach($expandedData as $key => $value)
                    @if(in_array($key, $skipKeys) || !$value) @continue @endif
                    @if($key === 'brand_color')
                    <div style="padding:8px 12px; background:rgba(255,255,255,0.02); border-radius:8px;">
                        <p style="font-size:9px; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:4px;">{{ $labels[$key] ?? $key }}</p>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="width:20px; height:20px; border-radius:4px; background:{{ $value }};"></div>
                            <span style="font-size:12px; color:rgba(255,255,255,0.6); font-family:monospace;">{{ $value }}</span>
                        </div>
                    </div>
                    @else
                    <div style="padding:8px 12px; background:rgba(255,255,255,0.02); border-radius:8px; {{ in_array($key, ['company_description','faq','departments','department_leads','sales_pipeline','auto_message','notes']) ? 'grid-column:span 2;' : '' }}">
                        <p style="font-size:9px; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:4px;">{{ $labels[$key] ?? $key }}</p>
                        <p style="font-size:12px; color:rgba(255,255,255,0.7); white-space:pre-wrap; line-height:1.5;">{{ is_array($value) ? implode(', ', $value) : $value }}</p>
                    </div>
                    @endif
                @endforeach
            </div>

            {{-- Logo e catálogos --}}
            <div style="display:flex; gap:12px; margin-top:14px; flex-wrap:wrap;">
                @if(!empty($expandedData['logo_path']))
                <a href="{{ \App\Services\MediaStorage::url($expandedData['logo_path']) }}" target="_blank"
                   style="display:flex; align-items:center; gap:6px; padding:6px 12px; background:rgba(178,255,0,0.06); border:1px solid rgba(178,255,0,0.15); border-radius:8px; color:#b2ff00; font-size:11px; font-weight:600; text-decoration:none;">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Ver logo
                </a>
                @endif

                @foreach($expandedData['catalog_paths'] ?? [] as $i => $path)
                <a href="{{ \App\Services\MediaStorage::url($path) }}" target="_blank"
                   style="display:flex; align-items:center; gap:6px; padding:6px 12px; background:rgba(59,130,246,0.06); border:1px solid rgba(59,130,246,0.15); border-radius:8px; color:#60a5fa; font-size:11px; font-weight:600; text-decoration:none;">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Catálogo {{ $i + 1 }}
                </a>
                @endforeach
            </div>

            {{-- Excluir --}}
            <div style="margin-top:14px; text-align:right;">
                <button wire:click="delete('{{ $sub['_file'] }}')" wire:confirm="Excluir este onboarding?"
                        style="padding:5px 12px; font-size:10px; color:#f87171; background:rgba(239,68,68,0.06); border:1px solid rgba(239,68,68,0.15); border-radius:6px; cursor:pointer;">
                    Excluir
                </button>
            </div>
        </div>
        @endif
    </div>
    @empty
    <div style="padding:60px 20px; text-align:center; color:rgba(255,255,255,0.2); font-size:13px;">
        Nenhum onboarding recebido ainda.
    </div>
    @endforelse
</div>
