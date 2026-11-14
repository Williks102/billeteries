<?php

namespace App\Mail\Concerns;

use App\Support\MailDiagnostic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Base des e-mails transactionnels (commande, paiement, notifications) : ils partent TOUJOURS par la file d'attente,
 * jamais pendant la requête de l'acheteur.
 *
 * - ShouldQueueAfterCommit : le job n'est publié qu'après la validation de la transaction en cours (un rollback
 *   n'envoie rien) ;
 * - la charge utile du job ne contient que des identifiants de modèles (SerializesModels) : le destinataire est
 *   résolu AU MOMENT DE L'ENVOI par `recipientEmail()` (Envelope::to), il n'est donc jamais copié dans `jobs` ;
 * - un échec (SMTP injoignable, PDF impossible) ne touche jamais le statut d'une commande : seul `failed()` est
 *   appelé, et il ne journalise que l'identifiant et un extrait expurgé (jamais d'adresse ni de secret).
 */
abstract class TransactionalMail extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable, SerializesModels;

    /** Tentatives maximales avant échec définitif (table failed_jobs). */
    public $tries = 3;

    /** Secondes d'attente avant la 2e puis la 3e tentative. */
    public $backoff = [60, 300];

    /** Durée maximale d'un job (génération du PDF des billets comprise). */
    public $timeout = 120;

    /** Adresse du destinataire, calculée à l'envoi (jamais stockée dans le job). */
    abstract public function recipientEmail(): ?string;

    /** Contexte non personnel pour le journal : identifiants seulement. */
    protected function logContext(): array
    {
        return [];
    }

    /** Destinataire pour l'enveloppe : tableau vide si l'adresse est inconnue (le transport refusera alors proprement). */
    protected function recipients(): array
    {
        $email = trim((string) $this->recipientEmail());

        return $email === '' ? [] : [$email];
    }

    /**
     * L'exception d'un transport SMTP peut citer l'hôte, l'identifiant ou l'adresse du destinataire, et le worker la
     * journalise telle quelle (et failed_jobs la conserve) : on la remplace par une exception au message expurgé.
     * L'origine (classe, fichier, ligne) reste indiquée pour le diagnostic.
     */
    public function send($mailer)
    {
        try {
            return parent::send($mailer);
        } catch (\Throwable $e) {
            throw new MailDeliveryFailed(
                get_class($e).' ('.basename($e->getFile()).':'.$e->getLine().') : '.MailDiagnostic::redact($e->getMessage())
            );
        }
    }

    public function failed(\Throwable $e): void
    {
        try {
            Log::error('Échec définitif d\'un e-mail en file d\'attente (la commande n\'est pas modifiée)', $this->logContext() + [
                'mail' => class_basename($this),
                'exception' => get_class($e),
                'error' => MailDiagnostic::redact($e->getMessage()),
            ]);
        } catch (\Throwable) {
            // Un échec de journalisation ne doit jamais masquer l'échec d'origine.
        }
    }
}
