<?php

namespace App\Mail\Concerns;

/** Échec d'envoi d'un e-mail transactionnel, au message déjà expurgé (aucune adresse, aucun identifiant). */
class MailDeliveryFailed extends \RuntimeException
{
}
