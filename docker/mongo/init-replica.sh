#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Inicializa MongoDB como replica set de un solo nodo (rs0).
#
# Las transacciones de MongoDB requieren un replica set, incluso en local.
# El script es idempotente: se puede ejecutar en cada `docker compose up`.
# ---------------------------------------------------------------------------
set -euo pipefail

MONGO_HOST="${MONGO_HOST:-mongo}"
MONGO_PORT="${MONGO_PORT:-27017}"
MONGO_URI="mongodb://${MONGO_HOST}:${MONGO_PORT}"

echo "→ Esperando a MongoDB en ${MONGO_URI}..."
until mongosh "${MONGO_URI}" --quiet --eval "db.adminCommand('ping').ok" >/dev/null 2>&1; do
    sleep 2
done

# rs.status() falla si el replica set aún no está inicializado.
if mongosh "${MONGO_URI}" --quiet --eval "rs.status().ok" >/dev/null 2>&1; then
    echo "→ El replica set rs0 ya estaba inicializado."
else
    echo "→ Inicializando replica set rs0..."
    mongosh "${MONGO_URI}" --quiet --eval "
        rs.initiate({
            _id: 'rs0',
            members: [{ _id: 0, host: '${MONGO_HOST}:${MONGO_PORT}' }]
        })
    " >/dev/null
fi

# Espera a que el nodo sea primario (myState === 1).
for _ in $(seq 1 60); do
    STATE="$(mongosh "${MONGO_URI}" --quiet --eval \
        'try { rs.status().myState } catch (e) { -1 }' 2>/dev/null || echo -1)"
    if [ "${STATE}" = "1" ]; then
        echo "→ MongoDB listo: PRIMARY en ${MONGO_HOST}:${MONGO_PORT}"
        exit 0
    fi
    sleep 2
done

echo "✖ No se pudo elegir un PRIMARY en rs0" >&2
exit 1
