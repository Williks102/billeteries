<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Journal des échecs de connexion (`auth.login_failed`) et des verrouillages (`auth.lockout`).
 *
 * - AUCUNE donnée personnelle : ni e-mail, ni mot de passe, ni IP en clair. Seulement la raison, une empreinte
 *   HMAC de l'identifiant saisi (clé de l'application) et l'empreinte HMAC de l'IP (ajoutée par ActivityLogger) ;
 * - ANTI-INONDATION : un attaquant ne doit pas pouvoir remplir la table. Au plus UNE ligne par minute et par
 *   identifiant, UNE par minute et par IP (Cache::add, atomique), et un plafond GLOBAL par minute ;
 * - ne ralentit ni ne casse jamais la connexion : tout est dans un try/catch, aucune exception ne remonte.
 */
class LogAuthFailure
{
    /** Plafond global de lignes écrites par minute (toutes empreintes confondues). */
    public const GLOBAL_PER_MINUTE = 30;

    public function handleFailed(Failed $event): void
    {
        $identifier = $event->credentials['email'] ?? $event->credentials['username'] ?? null;

        $this->log(
            ActivityLog::AUTH_LOGIN_FAILED,
            'Échec de connexion',
            is_string($identifier) ? $identifier : null,
            $event->user === null ? 'compte_inconnu' : 'mot_de_passe_incorrect'
        );
    }

    public function handleLockout(Lockout $event): void
    {
        $identifier = $event->request->input('email');

        $this->log(
            ActivityLog::AUTH_LOCKOUT,
            'Verrouillage de connexion (trop de tentatives)',
            is_string($identifier) ? $identifier : null,
            'trop_de_tentatives'
        );
    }

    private function log(string $action, string $description, ?string $identifier, string $reason): void
    {
        try {
            $key = (string) config('app.key');
            $idHash = hash_hmac('sha256', 'login:'.mb_strtolower(trim((string) $identifier)), $key);
            $ipHash = hash_hmac('sha256', 'login-ip:'.(string) request()->ip(), $key);
            $minute = now()->format('YmdHi');

            // Une ligne par minute et par identifiant, puis par IP (Cache::add : vrai seulement au premier appel).
            if (! Cache::add("authlog:{$action}:id:{$idHash}", 1, 60)) {
                return;
            }
            if (! Cache::add("authlog:{$action}:ip:{$ipHash}", 1, 60)) {
                return;
            }

            // Plafond global par minute (anti-rotation d'identifiants et d'IP).
            $globalKey = "authlog:global:{$minute}";
            Cache::add($globalKey, 0, 120);
            if ((int) Cache::increment($globalKey) > self::GLOBAL_PER_MINUTE) {
                return;
            }

            ActivityLogger::record($action, null, $description, [
                'reason' => $reason,
                'identifier_hash' => $idHash,
            ]);
        } catch (\Throwable $e) {
            try {
                Log::warning('Journal des connexions : écriture impossible', ['exception' => get_class($e)]);
            } catch (\Throwable) {
                // rien : ne jamais casser la connexion
            }
        }
    }
}
