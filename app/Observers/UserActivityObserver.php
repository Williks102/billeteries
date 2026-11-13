<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;

/**
 * Journal des changements de rôle et de statut (vérification d'e-mail) d'un utilisateur.
 * Seuls l'identifiant et les valeurs de rôle/statut sont stockés : ni nom, ni e-mail, ni téléphone.
 */
class UserActivityObserver
{
    public function updated(User $user): void
    {
        $changes = $user->getChanges();

        if (array_key_exists('role', $changes)) {
            ActivityLogger::record(
                ActivityLog::USER_ROLE_CHANGED,
                $user,
                sprintf('Utilisateur n°%d : rôle %s vers %s', $user->id, $user->getOriginal('role'), $changes['role']),
                ['role' => ['from' => $user->getOriginal('role'), 'to' => $changes['role']]]
            );
        }

        if (array_key_exists('email_verified_at', $changes)) {
            $from = $user->getOriginal('email_verified_at') !== null;
            $to = $changes['email_verified_at'] !== null;
            if ($from !== $to) {
                ActivityLogger::record(
                    ActivityLog::USER_STATUS_CHANGED,
                    $user,
                    sprintf('Utilisateur n°%d : e-mail %s', $user->id, $to ? 'marqué vérifié' : 'marqué non vérifié'),
                    ['email_verified' => ['from' => $from, 'to' => $to]]
                );
            }
        }
    }
}
