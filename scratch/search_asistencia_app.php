<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$searchValue = 'VERDUJO';
$searchValue2 = 'WA-0811F9';
$searchValue3 = '6624271895';

echo "Searching database cdd_app for: $searchValue, $searchValue2, $searchValue3" . PHP_EOL;

$tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");

foreach ($tables as $table) {
    $tableName = $table->table_name;
    if (Schema::hasTable($tableName)) {
        $columns = Schema::getColumnListing($tableName);
        foreach ($columns as $column) {
            try {
                $rows = DB::table($tableName)
                    ->where(function($q) use ($column, $searchValue, $searchValue2, $searchValue3) {
                        $q->whereRaw("CAST($column AS TEXT) ILIKE ?", ["%$searchValue%"])
                          ->orWhereRaw("CAST($column AS TEXT) ILIKE ?", ["%$searchValue2%"])
                          ->orWhereRaw("CAST($column AS TEXT) ILIKE ?", ["%$searchValue3%"]);
                    })
                    ->get();
                    
                if ($rows->count() > 0) {
                    echo "Found in Table: $tableName | Column: $column" . PHP_EOL;
                    foreach ($rows as $row) {
                        print_r($row);
                    }
                }
            } catch (\Exception $e) {
                // ignore
            }
        }
    }
}
