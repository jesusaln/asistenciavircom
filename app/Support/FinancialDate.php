<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Fechas para CFDI y operaciones contables/fiscales con zona horaria canónica.
 */
class FinancialDate
{
    public static function now(): Carbon
    {
        $tz = config('app.financial_timezone', config('app.timezone'));

        return Carbon::now($tz);
    }
}
