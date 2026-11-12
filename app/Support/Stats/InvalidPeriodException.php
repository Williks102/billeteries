<?php

namespace App\Support\Stats;

use InvalidArgumentException;

/** Période demandée invalide (preset inconnu, date mal formée, début > fin). */
class InvalidPeriodException extends InvalidArgumentException {}
