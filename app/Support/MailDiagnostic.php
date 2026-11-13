<?php

namespace App\Support;

/**
 * Aide au diagnostic de l'envoi d'e-mails (test de l'admin).
 *
 * - `configurationHint()` repère SANS rien envoyer les oublis de configuration les plus courants (hôte non défini,
 *   identifiants manquants) ; il ne cite que des NOMS de variables, jamais leurs valeurs ;
 * - `explain()` traduit l'erreur du serveur d'envoi en une consigne en français choisie dans une liste fixe : le
 *   texte de l'exception n'est jamais renvoyé à l'écran ;
 * - `redact()` prépare un extrait de l'erreur pour le journal du serveur, sans adresse e-mail ni identifiant.
 */
class MailDiagnostic
{
    /** Oubli de configuration détectable avant l'envoi (smtp seulement), ou null. */
    public static function configurationHint(): ?string
    {
        if ((string) config('mail.default') !== 'smtp') {
            return null;
        }

        $smtp = (array) config('mail.mailers.smtp', []);
        $host = trim((string) ($smtp['host'] ?? ''));

        if ($host === '' || in_array($host, ['127.0.0.1', 'localhost'], true)) {
            return "MAIL_HOST n'est pas défini (l'application vise le serveur local, inexistant). Ajoutez MAIL_HOST (smtp.gmail.com) et MAIL_PORT (587) dans Render.";
        }
        if (trim((string) ($smtp['username'] ?? '')) === '' || trim((string) ($smtp['password'] ?? '')) === '') {
            return 'MAIL_USERNAME ou MAIL_PASSWORD est vide dans Render.';
        }

        return null;
    }

    /** Consigne en français pour une erreur d'envoi, choisie dans une liste fixe (jamais le texte de l'exception). */
    public static function explain(\Throwable $e): ?string
    {
        $m = mb_strtolower($e->getMessage());

        return match (true) {
            (bool) preg_match('/\b535\b|authenticat|username and password|badcredentials|credentials/', $m)
                => "Le serveur d'envoi a refusé l'identifiant ou le mot de passe. Vérifiez MAIL_USERNAME et MAIL_PASSWORD : mot de passe d'application de 16 lettres, sans espaces, avec la validation en 2 étapes activée sur le compte Google.",
            (bool) preg_match('/certificate|\bssl\b|\btls\b|starttls|crypto/', $m)
                => 'Échec du chiffrement de la connexion. Vérifiez MAIL_SCHEME (smtp) et MAIL_PORT (587).',
            (bool) preg_match('/could not be established|connection refused|timed out|timeout|unable to connect|getaddrinfo|name or service not known|network is unreachable|php_network/', $m)
                => "Connexion impossible au serveur d'envoi. Vérifiez MAIL_HOST (smtp.gmail.com) et MAIL_PORT (587).",
            (bool) preg_match('/\b55[0-4]\b|sender|recipient|relay|not allowed|from address/', $m)
                => "Le serveur a refusé l'expéditeur ou le destinataire. MAIL_FROM_ADDRESS doit être l'adresse Gmail du compte.",
            default => null,
        };
    }

    /** Extrait de l'erreur pour le journal : sans adresse e-mail, sans identifiants dans une URL, tronqué. */
    public static function redact(string $message): string
    {
        $message = preg_replace('#://[^@\s/]+@#', '://[masqué]@', $message) ?? '';
        $message = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[adresse]', $message) ?? '';
        $message = preg_replace('/\s+/', ' ', $message) ?? '';

        return mb_substr(trim($message), 0, 300);
    }
}
