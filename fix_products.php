<?php
use Illuminate\Support\Facades\Schema;

if (!Schema::hasColumn('productos', 'marca_id')) {
    Schema::table('productos', function ($t) {
        $t->unsignedBigInteger('marca_id')->nullable();
    });
    echo "marca_id added\n";
}

if (!Schema::hasColumn('productos', 'categoria_id')) {
    Schema::table('productos', function ($t) {
        $t->unsignedBigInteger('categoria_id')->nullable();
    });
    echo "categoria_id added\n";
}

echo "Done\n";
