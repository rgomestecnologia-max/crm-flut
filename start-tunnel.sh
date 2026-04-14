#!/bin/bash

# =====================================================
# Script para expor o CRM localmente via túnel público
# =====================================================

export PATH="/opt/homebrew/bin:/usr/local/bin:$PATH"
export PATH="$PATH:/Users/rogerio/.nvm/versions/node/v24.14.1/bin"

PORT=8000
APP_DIR="$(cd "$(dirname "$0")" && pwd)"

echo ""
echo "╔════════════════════════════════════════════╗"
echo "║     CRM Multiatendimento — Túnel Público    ║"
echo "╚════════════════════════════════════════════╝"
echo ""

# Mata processos anteriores
pkill -f "php artisan serve" 2>/dev/null
pkill -f "ngrok http" 2>/dev/null
pkill -f "lt --port" 2>/dev/null
sleep 1

cd "$APP_DIR"

# Inicia Laravel em background
echo "▶ Iniciando servidor Laravel na porta $PORT..."
php artisan serve --port=$PORT > /tmp/laravel.log 2>&1 &
LARAVEL_PID=$!
sleep 2

if ! curl -s -o /dev/null http://localhost:$PORT/up; then
    echo "✗ Erro ao iniciar Laravel. Verifique /tmp/laravel.log"
    exit 1
fi
echo "✓ Laravel rodando (PID: $LARAVEL_PID)"
echo ""

# Escolhe o tunnel
echo "Escolha o tipo de túnel:"
echo "  1) ngrok        (requer conta gratuita em ngrok.com)"
echo "  2) localtunnel  (sem cadastro, instável às vezes)"
echo ""
read -p "Opção [1/2]: " CHOICE

if [ "$CHOICE" = "2" ]; then
    echo ""
    echo "▶ Iniciando localtunnel..."
    lt --port $PORT > /tmp/tunnel.log 2>&1 &
    TUNNEL_PID=$!
    sleep 4
    TUNNEL_URL=$(grep -o 'https://[^ ]*' /tmp/tunnel.log | head -1)

    if [ -z "$TUNNEL_URL" ]; then
        echo "✗ Localtunnel não iniciou. Veja /tmp/tunnel.log"
        cat /tmp/tunnel.log
        exit 1
    fi

    echo ""
    echo "╔════════════════════════════════════════════════════════════════╗"
    echo "║  ✓ TÚNEL ATIVO (localtunnel)                                   ║"
    echo "╠════════════════════════════════════════════════════════════════╣"
    printf "║  URL Pública:   %-47s ║\n" "$TUNNEL_URL"
    printf "║  Webhook Z-API: %-47s ║\n" "$TUNNEL_URL/api/webhook/zapi"
    echo "╚════════════════════════════════════════════════════════════════╝"

else
    # Verifica se ngrok está autenticado
    if ! ngrok config check > /dev/null 2>&1; then
        echo ""
        echo "⚠  ngrok precisa de autenticação."
        echo "   1. Acesse: https://dashboard.ngrok.com/signup"
        echo "   2. Copie seu authtoken"
        read -p "   Cole o token aqui: " NGROK_TOKEN
        ngrok config add-authtoken "$NGROK_TOKEN"
    fi

    echo ""
    echo "▶ Iniciando ngrok..."
    ngrok http $PORT --log=stdout > /tmp/ngrok.log 2>&1 &
    TUNNEL_PID=$!
    sleep 4

    TUNNEL_URL=$(curl -s http://localhost:4040/api/tunnels 2>/dev/null | python3 -c "
import sys, json
try:
    d = json.load(sys.stdin)
    for t in d.get('tunnels', []):
        if t.get('proto') == 'https':
            print(t['public_url'])
            break
except: pass
" 2>/dev/null)

    if [ -z "$TUNNEL_URL" ]; then
        echo "✗ ngrok não iniciou. Veja /tmp/ngrok.log"
        cat /tmp/ngrok.log | tail -20
        exit 1
    fi

    echo ""
    echo "╔════════════════════════════════════════════════════════════════╗"
    echo "║  ✓ TÚNEL ATIVO (ngrok)                                         ║"
    echo "╠════════════════════════════════════════════════════════════════╣"
    printf "║  URL Pública:   %-47s ║\n" "$TUNNEL_URL"
    printf "║  Webhook Z-API: %-47s ║\n" "$TUNNEL_URL/api/webhook/zapi"
    printf "║  Dashboard:     %-47s ║\n" "http://localhost:4040"
    echo "╚════════════════════════════════════════════════════════════════╝"
fi

echo ""
echo "══════════════════════════════════════════════════════════════════"
echo "  Configure no painel Z-API:"
echo "  → Webhook de Recebimento: $TUNNEL_URL/api/webhook/zapi"
echo "  → Webhook de Status:      $TUNNEL_URL/api/webhook/zapi"
echo "══════════════════════════════════════════════════════════════════"
echo ""
echo "  Atualize o APP_URL no .env:"
echo "  APP_URL=$TUNNEL_URL"
echo ""

# Atualiza APP_URL automaticamente
sed -i '' "s|APP_URL=.*|APP_URL=$TUNNEL_URL|" "$APP_DIR/.env"
php artisan config:clear > /dev/null 2>&1
echo "✓ APP_URL atualizado no .env automaticamente"
echo ""
echo "Pressione Ctrl+C para encerrar tudo."
echo ""

# Aguarda
cleanup() {
    echo ""
    echo "Encerrando..."
    kill $LARAVEL_PID $TUNNEL_PID 2>/dev/null
    # Restaura APP_URL local
    sed -i '' "s|APP_URL=.*|APP_URL=http://localhost:8000|" "$APP_DIR/.env"
    php artisan config:clear > /dev/null 2>&1
    echo "APP_URL restaurado para http://localhost:8000"
    exit 0
}
trap cleanup INT TERM

wait
