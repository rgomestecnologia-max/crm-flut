#!/usr/bin/env bash
# Inicia a Evolution API v2
# Uso: ./docker/evolution-api-start.sh [--recreate]
#
# --recreate  Remove e recria o container (necessário após atualizar a imagem)

set -euo pipefail

CONTAINER="evolution-api"
IMAGE="atendai/evolution-api:v1.8.7"
PORT=8080
API_KEY="430BCF13-8AA4-47DA-9723-6053BAC23F9B"

# ─── Funções ────────────────────────────────────────────────────────────────

log()  { echo "[$(date '+%H:%M:%S')] $*"; }
ok()   { echo "[$(date '+%H:%M:%S')] ✓ $*"; }
fail() { echo "[$(date '+%H:%M:%S')] ✗ $*" >&2; exit 1; }

wait_ready() {
    log "Aguardando Evolution API responder na porta $PORT..."
    local attempts=0
    until curl -sf "http://localhost:$PORT" -o /dev/null 2>/dev/null; do
        attempts=$((attempts + 1))
        [ $attempts -ge 60 ] && fail "Timeout: Evolution API não respondeu em 60s"
        sleep 1
    done
    ok "Evolution API está respondendo"
}

start_container() {
    log "Iniciando container $CONTAINER com imagem $IMAGE..."
    docker run -d \
        --name "$CONTAINER" \
        --restart always \
        --platform linux/amd64 \
        -p "$PORT:8080" \
        -e AUTHENTICATION_API_KEY="$API_KEY" \
        -e DATABASE_ENABLED=false \
        -e CACHE_REDIS_ENABLED=false \
        -e CACHE_LOCAL_ENABLED=true \
        -e TZ=America/Sao_Paulo \
        "$IMAGE" > /dev/null
    ok "Container iniciado"
}

# ─── Main ───────────────────────────────────────────────────────────────────

RECREATE=false
[[ "${1:-}" == "--recreate" ]] && RECREATE=true

if docker ps -a --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
    if $RECREATE; then
        log "Removendo container existente..."
        docker stop "$CONTAINER" > /dev/null 2>&1 || true
        docker rm   "$CONTAINER" > /dev/null 2>&1 || true
        start_container
    else
        if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
            log "Container parado — iniciando..."
            docker start "$CONTAINER" > /dev/null
            ok "Container iniciado"
        else
            ok "Container já está rodando"
        fi
    fi
else
    start_container
fi

wait_ready

echo ""
ok "Evolution API v2 pronta em http://localhost:$PORT"
echo "   API Key global : $API_KEY"
echo "   Para recriar   : $0 --recreate"
