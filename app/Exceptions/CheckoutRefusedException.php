<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Commande refusée pour une raison métier dont le message est affichable tel quel à l'acheteur
 * (stock indisponible, plafond de commandes en attente). Toute autre exception reste interne.
 */
class CheckoutRefusedException extends RuntimeException
{
}
