#!/bin/bash
# Démarrage du conteneur de démo (Render) : migrations, caches de production, seed de démo optionnel,
# puis planificateur Laravel + worker de file (arrière-plan) + Apache (premier plan). Si l'un des deux s'arrête, le conteneur
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

# 1b. APP_KEY tolérante (I2) : une clé base64 de 32 octets sans préfixe « base64: » (par exemple celle générée par
#     Render) reçoit le préfixe ; une clé de 32 caractères bruts est acceptée. Sinon on s'arrête avec un message clair.
#     La clé n'est JAMAIS affichée : la commande l'écrit sur la sortie standard, capturée ici sans être journalisée.
if ! NORMALIZED_APP_KEY="$(art app:normalize-key --print)"; then
    die "APP_KEY invalide (voir le message ci-dessus, la valeur n'est pas affichée ; voir docs/deploiement-render.md)."
fi
case "$NORMALIZED_APP_KEY" in
    base64:*) ;;
    ????????????????????????????????) ;;
    *) die "APP_KEY : la normalisation a renvoyé une valeur inattendue (la valeur n'est pas affichée)." ;;
esac
export APP_KEY="$NORMALIZED_APP_KEY"
unset NORMALIZED_APP_KEY

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
QUEUE_PID=""

stop_all() {
    trap - TERM INT
    log "Signal reçu, arrêt en cours…"
    [ -n "$SCHED_PID" ] && kill -TERM "$SCHED_PID" 2>/dev/null || true
    [ -n "$QUEUE_PID" ] && kill -TERM "$QUEUE_PID" 2>/dev/null || true
    [ -n "$WEB_PID" ] && kill -TERM "$WEB_PID" 2>/dev/null || true
    wait 2>/dev/null || true
    exit 0
}
trap stop_all TERM INT

log "Démarrage du planificateur (schedule:work)…"
runuser -u www-data -- php artisan schedule:work &
SCHED_PID=$!

# Worker de la file d'attente (e-mails transactionnels). Boucle de supervision : si queue:work s'arrête (limite de
# durée --max-time, mémoire --memory, plantage), il est relancé 5 s plus tard. Non démarré si QUEUE_CONNECTION=sync
# (rien à traiter). Tourne sous www-data comme le planificateur ; sortie vers les journaux du conteneur.
if [ "${QUEUE_CONNECTION:-database}" != "sync" ]; then
    log "Démarrage du worker de file d'attente (queue:work)…"
    queue_loop() {
        local child=""
        # À l'arrêt du conteneur on relaie le signal à queue:work (sinon il resterait orphelin, un job en cours coupé)
        trap '[ -n "$child" ] && kill -TERM "$child" 2>/dev/null; exit 0' TERM INT
        while true; do
            runuser -u www-data -- php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --memory=128 &
            child=$!
            wait "$child" || true
            child=""
            sleep 5 &
            wait $! || true
        done
    }
    queue_loop &
    QUEUE_PID=$!
else
    log "QUEUE_CONNECTION=sync : pas de worker de file."
fi

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
[ -n "$QUEUE_PID" ] && kill -TERM "$QUEUE_PID" 2>/dev/null || true
wait 2>/dev/null || true
exit "$([ "$STATUS" -eq 0 ] && echo 1 || echo "$STATUS")"
