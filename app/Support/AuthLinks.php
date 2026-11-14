<?php

namespace App\Support;

/**
 * Liens envoyés par e-mail pour l'authentification (réinitialisation et activation de compte).
 *
 * Ils sont TOUJOURS construits sur l'adresse officielle du site (`APP_URL`), jamais sur l'en-tête `Host` (ou
 * `X-Forwarded-Host`) de la requête qui déclenche l'envoi : sinon un attaquant fait émettre à sa victime un e-mail
 * authentique dont le lien pointe vers son propre domaine, rejoue ensuite le jeton sur le vrai site et prend le compte
 * (empoisonnement de l'en-tête Host).
 */
class AuthLinks
{
    public static function resetUrl(string $token, string $email): string
    {
        return rtrim((string) config('app.url'), '/').route('password.reset', ['token' => $token, 'email' => $email], false);
    }
}
