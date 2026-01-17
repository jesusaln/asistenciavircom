<?php
use Illuminate\Support\Facades\Schema;

if (!Schema::hasColumn('clientes', 'deleted_at')) {
    Schema::table('clientes', function ($t) {
        $t->softDeletes();
    });
    echo "deleted_at added\n";
} else {
    echo "deleted_at exists\n";
}
