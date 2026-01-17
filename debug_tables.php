<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Tables in DB:\n";
try {
    // Try retrieving tables (works on recent Laravel)
    $tables = Schema::getTableListing();
    print_r($tables);
} catch (\Exception $e) {
    echo "Error listing tables: " . $e->getMessage() . "\n";
}

// Check 'clientes' explicitly
if (Schema::hasTable('clientes')) {
    echo "Table 'clientes' exists.\n";
} else {
    echo "Table 'clientes' DOES NOT exist.\n";
}

// Check 'empresas' explicitly
if (Schema::hasTable('empresas')) {
    echo "Table 'empresas' exists.\n";
} else {
    echo "Table 'empresas' DOES NOT exist.\n";
}
