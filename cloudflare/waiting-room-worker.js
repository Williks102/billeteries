/**
 * Cloudflare Worker - Salle d'attente avec Upstash Redis
 *
 * Variables d'environnement nécessaires:
 * - UPSTASH_REDIS_REST_URL   (ex: https://xxxx.upstash.io)
 * - UPSTASH_REDIS_REST_TOKEN (token REST Upstash)
 * - MAX_CONCURRENT_USERS     (optionnel, défaut: 100)
 * - SESSION_TTL_SECONDS      (optionnel, défaut: 120)
 */

const ACTIVE_USERS_ZSET_KEY = 'waiting_room:active_users';
const UID_COOKIE = 'wr_uid';

export default {
  async fetch(request, env, ctx) {
    const maxConcurrentUsers = Number(env.MAX_CONCURRENT_USERS || 100);
    const sessionTtlSeconds = Number(env.SESSION_TTL_SECONDS || 120);

    const cookieHeader = request.headers.get('Cookie') || '';
    const existingUid = getCookie(cookieHeader, UID_COOKIE);
    const uid = existingUid || crypto.randomUUID();

    const nowEpochSeconds = Math.floor(Date.now() / 1000);

    const [allowed, alreadyActive, activeCount] = await evaluateAccess({
      env,
      uid,
      nowEpochSeconds,
      sessionTtlSeconds,
      maxConcurrentUsers,
    });

    if (!allowed) {
      const retryAfter = '5';
      return new Response(renderWaitingRoomHtml(activeCount, maxConcurrentUsers), {
        status: 429,
        headers: {
          'Content-Type': 'text/html; charset=UTF-8',
          'Cache-Control': 'no-store, no-cache, must-revalidate, proxy-revalidate',
          Pragma: 'no-cache',
          Expires: '0',
          'Retry-After': retryAfter,
          'Set-Cookie': makeUidCookie(uid),
        },
      });
    }

    const upstreamResponse = await fetch(request);
    const response = new Response(upstreamResponse.body, upstreamResponse);

    response.headers.set('Set-Cookie', makeUidCookie(uid));
    response.headers.set('X-Waiting-Room-Active-Users', String(activeCount));
    response.headers.set('X-Waiting-Room-Status', alreadyActive ? 'already-active' : 'admitted');

    return response;
  },
};

async function evaluateAccess({ env, uid, nowEpochSeconds, sessionTtlSeconds, maxConcurrentUsers }) {
  const script = `
local activeKey = KEYS[1]
local uid = ARGV[1]
local now = tonumber(ARGV[2])
local ttl = tonumber(ARGV[3])
local maxUsers = tonumber(ARGV[4])

local expiresAt = now + ttl

-- Supprime les sessions expirées
redis.call('ZREMRANGEBYSCORE', activeKey, '-inf', now)

-- Si l'utilisateur est déjà actif, on prolonge sa session
local existing = redis.call('ZSCORE', activeKey, uid)
if existing then
  redis.call('ZADD', activeKey, expiresAt, uid)
  local count = redis.call('ZCARD', activeKey)
  return {1, 1, count}
end

-- Sinon, admettre uniquement si la capacité n'est pas atteinte
local count = redis.call('ZCARD', activeKey)
if count < maxUsers then
  redis.call('ZADD', activeKey, expiresAt, uid)
  return {1, 0, count + 1}
end

return {0, 0, count}
`;

  const result = await upstashCommand(env, [
    'EVAL',
    script,
    '1',
    ACTIVE_USERS_ZSET_KEY,
    uid,
    String(nowEpochSeconds),
    String(sessionTtlSeconds),
    String(maxConcurrentUsers),
  ]);

  if (!Array.isArray(result) || result.length < 3) {
    throw new Error(`Réponse Redis inattendue: ${JSON.stringify(result)}`);
  }

  return [Number(result[0]) === 1, Number(result[1]) === 1, Number(result[2] || 0)];
}

async function upstashCommand(env, args) {
  const url = env.UPSTASH_REDIS_REST_URL;
  const token = env.UPSTASH_REDIS_REST_TOKEN;

  if (!url || !token) {
    throw new Error('UPSTASH_REDIS_REST_URL et UPSTASH_REDIS_REST_TOKEN sont requis');
  }

  const response = await fetch(url, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(args),
  });

  const payload = await response.json();

  if (!response.ok || payload.error) {
    throw new Error(`Erreur Upstash: ${payload.error || response.statusText}`);
  }

  return payload.result;
}

function getCookie(cookieHeader, name) {
  const target = `${name}=`;
  const entries = cookieHeader.split(';');

  for (const rawEntry of entries) {
    const entry = rawEntry.trim();
    if (entry.startsWith(target)) {
      return decodeURIComponent(entry.slice(target.length));
    }
  }

  return null;
}

function makeUidCookie(uid) {
  // SameSite=Lax protège contre la majorité des CSRF tout en laissant la navigation normale.
  return `${UID_COOKIE}=${encodeURIComponent(uid)}; Path=/; HttpOnly; Secure; SameSite=Lax; Max-Age=31536000`;
}

function renderWaitingRoomHtml(activeCount, maxConcurrentUsers) {
  return `<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="refresh" content="5" />
  <title>Salle d'attente</title>
  <style>
    :root { color-scheme: light dark; }
    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
      background: #0f172a;
      color: #e2e8f0;
    }
    .card {
      width: min(92vw, 620px);
      background: rgba(15, 23, 42, 0.75);
      border: 1px solid rgba(148, 163, 184, 0.25);
      border-radius: 16px;
      padding: 28px;
      box-shadow: 0 16px 48px rgba(0, 0, 0, 0.35);
    }
    h1 { margin-top: 0; font-size: 1.6rem; }
    p { line-height: 1.55; }
    .badge {
      display: inline-block;
      padding: 6px 10px;
      border-radius: 999px;
      background: #1d4ed8;
      color: #fff;
      font-size: .86rem;
      margin-top: 8px;
    }
  </style>
</head>
<body>
  <main class="card">
    <h1>Vous êtes en salle d'attente</h1>
    <p>
      Le nombre d'utilisateurs simultanés a atteint la limite. La page se recharge automatiquement toutes les 5 secondes.
    </p>
    <p class="badge">Utilisateurs actifs: ${activeCount} / ${maxConcurrentUsers}</p>
  </main>
</body>
</html>`;
}
