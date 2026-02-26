# Cloudflare Worker: salle d'attente avec Upstash Redis

## Fichiers
- `waiting-room-worker.js` : worker complet (admission + page d'attente HTML).
- `wrangler.toml.example` : configuration de base Wrangler.

## Déploiement rapide

```bash
cd cloudflare
cp wrangler.toml.example wrangler.toml
wrangler secret put UPSTASH_REDIS_REST_URL
wrangler secret put UPSTASH_REDIS_REST_TOKEN
wrangler deploy
```

## Variables
- `MAX_CONCURRENT_USERS` (défaut `100`) : nombre max d'utilisateurs simultanés admis.
- `SESSION_TTL_SECONDS` (défaut `120`) : durée de présence active sans nouvelle requête.

## Fonctionnement
1. Chaque visiteur reçoit un cookie `wr_uid`.
2. Le worker stocke les utilisateurs actifs dans un Sorted Set Redis avec une date d'expiration (score).
3. Un script Lua Upstash fait le contrôle de capacité de façon atomique.
4. Si la capacité est dépassée, le worker répond avec une page HTML de salle d'attente (HTTP `429`) qui se recharge automatiquement.
