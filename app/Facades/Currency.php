<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string formatFCFA($amount)
 * @method static int parseFCFA($formatted)
 * @method static string formatNumber($amount)
 * @method static int calculatePercentage($amount, $percentage)
 * @method static int addFixedFee($amount, $fixedFee)
 * @method static array calculateCommission($amount, $rate, $fixedFee = 0)
 * 
 * @see \App\Helpers\CurrencyHelper
 */
class Currency extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Helpers\CurrencyHelper::class;
    }
}