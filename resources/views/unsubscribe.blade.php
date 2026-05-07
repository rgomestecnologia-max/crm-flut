<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cancelar inscrição</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: white; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); max-width: 480px; width: 100%; padding: 40px 32px; text-align: center; }
        h1 { font-size: 20px; color: #111; margin-bottom: 8px; }
        p { font-size: 14px; color: #666; line-height: 1.6; margin-bottom: 20px; }
        .email { font-weight: 600; color: #333; }
        .reasons { display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px; text-align: left; }
        .reason { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border: 1px solid #e0e0e0; border-radius: 10px; cursor: pointer; transition: all 0.15s; }
        .reason:hover { border-color: #999; background: #fafafa; }
        .reason input { accent-color: #ef4444; width: 16px; height: 16px; }
        .reason label { font-size: 13px; color: #333; cursor: pointer; flex: 1; }
        .btn { width: 100%; padding: 12px; background: #ef4444; color: white; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.15s; }
        .btn:hover { background: #dc2626; }
        .btn:disabled { background: #ccc; cursor: not-allowed; }
        .success { color: #16a34a; }
        .icon { font-size: 48px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="card">
        @if(isset($confirmed) && $confirmed)
            <div class="icon">✅</div>
            <h1>Inscrição cancelada</h1>
            <p>O email <span class="email">{{ $email }}</span> foi removido da lista de disparos. Você não receberá mais emails nossos.</p>
        @else
            <div class="icon">📧</div>
            <h1>Cancelar inscrição</h1>
            <p>Deseja parar de receber emails em <span class="email">{{ $email }}</span>?</p>

            <form method="POST" action="{{ url('/unsubscribe/' . $token) }}">
                @csrf
                <div class="reasons">
                    <label class="reason">
                        <input type="radio" name="reason" value="Não tenho interesse" checked>
                        <span>Não tenho interesse</span>
                    </label>
                    <label class="reason">
                        <input type="radio" name="reason" value="Recebo muitos emails">
                        <span>Recebo muitos emails</span>
                    </label>
                    <label class="reason">
                        <input type="radio" name="reason" value="Conteúdo irrelevante">
                        <span>Conteúdo irrelevante</span>
                    </label>
                    <label class="reason">
                        <input type="radio" name="reason" value="Outro">
                        <span>Outro motivo</span>
                    </label>
                </div>

                <button type="submit" class="btn">Cancelar inscrição</button>
            </form>
        @endif
    </div>
</body>
</html>
