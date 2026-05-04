<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class DbExpression
{
    /**
     * CAST(col AS TEXT) helper to centralize raw usage.
     */
    public static function castText(string $column): \Illuminate\Database\Query\Expression
    {
        return DB::raw("CAST({$column} AS TEXT)");
    }
}
