#!/bin/bash

# =====================================================
# CRM Multiatendimento — Inicialização completa
# Laravel + Reverb + ngrok
# =====================================================

export PATH="/opt/homebrew/bin:/usr/local/bin:$PATH"
export PATH="$PATH:/Users/rogerio/.nvm/versions/node/v24.14.1/bin"

PORT=8000
APP_DIR="$(cd "$(dirname "$0")" && pwd)"
PHP=/opt/homebrew/bin/php

echo ""
echo "╔══════════════════════════════════════════════╗"
echo "║      CRM Multiatendimento — Iniciando...      ║"
echo "╚══════════════════════════════════════════════╝"
echo ""

# Mata processos anteriores
pkill -f "php artisan serve"   2>/dev/null
pkill -f "php artisan reverb"  2>/dev/null
pkill -f "ngrok http"          2>/dev/null
pkill -f "lt --port"           2>/dev/null
sleep 1

cd "$APP_DIR"

# ── 1. Laravel ──────────────────────────────────────
echo "▶ [1/3] Iniciando Laravel na porta $PORT..."
$PHP artisan serve --port=$PORT > /tmp/laravel.log 2>&1 &
LARAVEL_PID=$!
sleep 2

if ! curl -s -o /dev/null -w "%{http_code}" http://localhost:$PORT/up | grep -q "200"; then
    echo "✗ Laravel não iniciou. Log:"
    tail -10 /tmp/laravel.log
    exit 1
fi
echo "  ✓ Laravel rodando (PID $LARAVEL_PID)"

# ── 2. Reverb (WebSockets) ───────────────────────────
echo "▶ [2/3] Iniciando Reverb (WebSockets)..."
$PHP artisan reverb:start > /tmp/reverb.log 2>&1 &
REVERB_PID=$!
sleep 2

if ps -p $REVERB_PID > /dev/null 2>&1; then
    echo "  ✓ Reverb rodando na porta 8080 (PID $REVERB_PID)"
else
    echo "  ✗ Reverb não iniciou. Log:"
    tail -10 /tmp/reverb.log
fi

# ── 3. ngrok ────────────────────────────────────────
echo "▶ [3/3] Iniciando ngrok..."
ngrok http $PORT --log=stdout > /tmp/ngrok.log 2>&1 &
NGROK_PID=$!
sleep 5

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
    echo "  ✗ ngrok não iniciou. Log:"
    tail -10 /tmp/ngrok.log
    echo ""
    echo "  Verifique se o authtoken está configurado:"
    echo "  ngrok config add-authtoken SEU_TOKEN"
    kill $LARAVEL_PID $REVERB_PID 2>/dev/null
    exit 1
fi

echo "  ✓ ngrok ativo"

# Atualiza APP_URL no .env
sed -i '' "s|APP_URL=.*|APP_URL=$TUNNEL_URL|" "$APP_DIR/.env"
$PHP artisan config:clear > /dev/null 2>&1

# ── Resumo ──────────────────────────────────────────
echo ""
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║  ✓ SISTEMA PRONTO                                                ║"
echo "╠══════════════════════════════════════════════════════════════════╣"
printf "║  CRM Local:     %-50s ║\n" "http://localhost:$PORT"
printf "║  URL Pública:   %-50s ║\n" "$TUNNEL_URL"
printf "║  Webhook Z-API: %-50s ║\n" "$TUNNEL_URL/api/webhook/zapi"
printf "║  Reverb WS:     %-50s ║\n" "ws://localhost:8080"
printf "║  ngrok Dashboard: %-48s ║\n" "http://localhost:4040"
echo "╠══════════════════════════════════════════════════════════════════╣"
echo "║  ⚠  Atualize o webhook no painel Z-API se a URL mudou           ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""
echo "  Logs:"
echo "    Laravel : tail -f /tmp/laravel.log"
echo "    Reverb  : tail -f /tmp/reverb.log"
echo "    ngrok   : tail -f /tmp/ngrok.log"
echo "    CRM app : tail -f storage/logs/laravel.log"
echo ""
echo "  Pressione Ctrl+C para encerrar tudo."
echo ""

# ── Cleanup ao Ctrl+C ───────────────────────────────
cleanup() {
    echo ""
    echo "Encerrando todos os processos..."
    kill $LARAVEL_PID $REVERB_PID $NGROK_PID 2>/dev/null
    sed -i '' "s|APP_URL=.*|APP_URL=http://localhost:$PORT|" "$APP_DIR/.env"
    $PHP artisan config:clear > /dev/null 2>&1
    echo "APP_URL restaurado para http://localhost:$PORT"
    exit 0
}
trap cleanup INT TERM

wait
