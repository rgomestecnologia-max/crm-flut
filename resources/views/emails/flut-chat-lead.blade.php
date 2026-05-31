<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0; padding:0; background:#f4f4f4; font-family:Arial, sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; margin:20px auto; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
    {{-- Header --}}
    <tr>
        <td style="background:#3b82f6; padding:24px 30px;">
            <h1 style="color:#fff; font-size:18px; margin:0;">Um novo lead foi gerado pelo FlutChat 🎉</h1>
        </td>
    </tr>

    {{-- Body --}}
    <tr>
        <td style="padding:30px;">
            @foreach($lead->data ?? [] as $key => $val)
            <div style="margin-bottom:16px; border-bottom:1px solid #eee; padding-bottom:12px;">
                <p style="font-size:12px; color:#888; margin:0 0 4px; text-transform:uppercase;">{{ $key }}</p>
                @php $isPhone = in_array(strtolower($key), ['whatsapp', 'telefone', 'phone', 'celular']); @endphp
                @if($isPhone)
                <p style="font-size:15px; color:#333; margin:0;">
                    {{ $val }}
                    @php $cleanPhone = preg_replace('/\D/', '', $val); if (strlen($cleanPhone) === 11) $cleanPhone = '55' . $cleanPhone; @endphp
                    <a href="https://wa.me/{{ $cleanPhone }}" style="color:#3b82f6; font-size:12px; margin-left:8px;">WhatsApp Web</a> |
                    <a href="https://api.whatsapp.com/send?phone={{ $cleanPhone }}" style="color:#3b82f6; font-size:12px;">WhatsApp App</a>
                </p>
                @else
                <p style="font-size:15px; color:#333; margin:0;">{{ $val }}</p>
                @endif
            </div>
            @endforeach

            @if($lead->page_url)
            <div style="margin-bottom:16px; border-bottom:1px solid #eee; padding-bottom:12px;">
                <p style="font-size:12px; color:#888; margin:0 0 4px; text-transform:uppercase;">URL da conversão</p>
                <p style="font-size:14px; margin:0;"><a href="{{ $lead->page_url }}" style="color:#3b82f6;">{{ $lead->page_url }}</a></p>
            </div>
            @endif

            <div style="margin-bottom:16px; border-bottom:1px solid #eee; padding-bottom:12px;">
                <p style="font-size:12px; color:#888; margin:0 0 4px; text-transform:uppercase;">Fluxo que converteu</p>
                <p style="font-size:14px; color:#333; margin:0;">{{ $widgetName }}</p>
            </div>

            <div style="margin-bottom:16px; border-bottom:1px solid #eee; padding-bottom:12px;">
                <p style="font-size:12px; color:#888; margin:0 0 4px; text-transform:uppercase;">Lead gerado em</p>
                <p style="font-size:14px; color:#333; margin:0;">{{ $lead->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</p>
            </div>

            @if($lead->action_taken)
            <div style="margin-bottom:16px; border-bottom:1px solid #eee; padding-bottom:12px;">
                <p style="font-size:12px; color:#888; margin:0 0 4px; text-transform:uppercase;">Ação realizada</p>
                <p style="font-size:14px; color:#333; margin:0;">{{ ucfirst($lead->action_taken) }}</p>
            </div>
            @endif

            {{-- CTA --}}
            <div style="text-align:center; margin-top:24px;">
                <p style="font-size:13px; color:#666; margin-bottom:12px;">Acompanhe esse lead dentro do painel de leads</p>
                <a href="{{ url('/admin/flut-chat') }}" style="display:inline-block; padding:12px 28px; background:#3b82f6; color:#fff; text-decoration:none; border-radius:6px; font-weight:bold; font-size:14px;">Acessar painel de leads</a>
            </div>
        </td>
    </tr>

    {{-- Footer --}}
    <tr>
        <td style="background:#f8f8f8; padding:16px 30px; text-align:center;">
            <p style="font-size:11px; color:#aaa; margin:0;">FlutChat — CRM Flut</p>
        </td>
    </tr>
</table>
</body>
</html>
