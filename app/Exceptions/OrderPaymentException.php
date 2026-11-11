<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Confirmation de paiement impossible pour une raison métier (message affichable à l'admin).
 */
class OrderPaymentException extends RuntimeException
{
}
