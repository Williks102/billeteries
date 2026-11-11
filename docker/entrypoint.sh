#!/bin/bash
# Démarrage du conteneur de démo (Render) : migrations, caches de production, seed de démo optionnel,
# puis planificateur Laravel (arrière-plan) + Apache (premier plan). Si l'un des deux s'arrête, le conteneur
# s'arrête (Render le redémarre). Aucun secret n'est écrit ni affiché par ce script.
set -eu

cd /var/www/html

log() { echo "[entrypoint] $*"; }
die() { echo "[entrypoint] ERREUR : $*" >&2; exit 1; }

# Les commandes Artisan tournent sous www-data : les fichiers créés (caches, vues) restent écrivables par Apache.
art() { runuser -u www-data -- php artisan "$@"; }

# 1. Variables indispensables (on n'affiche que leur NOM, jamais leur valeur)
for var in APP_KEY APP_URL DB_HOST DB_DATABASE DB_USERNAME DB_PASSWORD; do
    if [ -z "$(printenv "$var" || true)" ]; then
        die "la variable d'environnement $var est absente ou vide (voir docs/deploiement-render.md)."
    fi
done

# 2. Port : Render injecte PORT (10000 par défaut). Apache le lit via ${PORT} dans sa configuration.
export PORT="${PORT:-10000}"
log "Apache écoutera sur le port $PORT."

# 3. Dossiers inscriptibles (le système de fichiers est éphémère : recréés à chaque démarrage)
mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 4. Migrations : un échec arrête le conteneur, l'erreur reste visible dans les journaux Render.
log "Migrations…"
if ! art migrate --force; then
    die "php artisan migrate a échoué (voir le message ci-dessus). Le conteneur s'arrête volontairement."
fi

# 5. Lien public/storage (recréé : le système de fichiers est neuf à chaque déploiement)
php artisan storage:link --force || log "avertissement : storage:link a échoué (les images téléversées ne seront pas servies)."

# 6. Seed de démo, optionnel et idempotent (ne fait rien si la table users n'est pas vide).
#    Lu AVANT config:cache : avec le cache de configuration, .env n'est plus relu.
if [ "${DEMO_SEED:-false}" = "true" ]; then
    log "DEMO_SEED=true : seed de démo (si la base est vide)…"
    if ! art demo:seed; then
        die "demo:seed a échoué (ADMIN_EMAIL valide et ADMIN_PASSWORD de 12 caractères minimum requis). Le conteneur s'arrête."
    fi
else
    log "DEMO_SEED différent de true : pas de seed."
fi

# 7. Caches de production
log "Caches de production…"
art config:cache
art route:cache
art view:cache

# 8. Planificateur (pas de cron gratuit sur Render) + serveur web ; le conteneur s'arrête si l'un des deux meurt.
SCHED_PID=""
WEB_PID=""

stop_all() {
    trap - TERM INT
    log "Signal reçu, arrêt en cours…"
    [ -n "$SCHED_PID" ] && kill -TERM "$SCHED_PID" 2>/dev/null || true
    [ -n "$WEB_PID" ] && kill -TERM "$WEB_PID" 2>/dev/null || true
    wait 2>/dev/null || true
    exit 0
}
trap stop_all TERM INT

log "Démarrage du planificateur (schedule:work)…"
runuser -u www-data -- php artisan schedule:work &
SCHED_PID=$!

log "Démarrage d'Apache…"
apache2-foreground &
WEB_PID=$!

# Attend le premier des deux qui se termine
set +e
wait -n "$SCHED_PID" "$WEB_PID"
STATUS=$?
set -e

log "Un processus s'est arrêté (code $STATUS) : arrêt du conteneur pour redémarrage par Render."
kill -TERM "$SCHED_PID" "$WEB_PID" 2>/dev/null || true
wait 2>/dev/null || true
exit "$([ "$STATUS" -eq 0 ] && echo 1 || echo "$STATUS")"
