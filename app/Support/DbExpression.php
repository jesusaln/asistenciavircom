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
        $column = trim($column);

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $column)) {
            throw new \InvalidArgumentException("Invalid column identifier [{$column}]");
        }

        $wrappedColumn = DB::connection()->getQueryGrammar()->wrap($column);

        return DB::raw("CAST({$wrappedColumn} AS TEXT)");
    }
}
