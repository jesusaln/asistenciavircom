<?php

use Illuminate\Support\Facades\Schema;

// Add missing columns to productos table
if (!Schema::hasColumn('productos', 'estado')) {
    Schema::table('productos', function ($table) {
        $table->string('estado')->default('activo');
    });
    echo "estado added\n";
}

if (!Schema::hasColumn('productos', 'precio_venta')) {
    Schema::table('productos', function ($table) {
        $table->decimal('precio_venta', 15, 2)->default(0);
    });
    echo "precio_venta added\n";
}

if (!Schema::hasColumn('productos', 'imagen')) {
    Schema::table('productos', function ($table) {
        $table->string('imagen')->nullable();
    });
    echo "imagen added\n";
}

if (!Schema::hasColumn('productos', 'deleted_at')) {
    Schema::table('productos', function ($table) {
        $table->softDeletes();
    });
    echo "deleted_at added\n";
}

echo "Done\n";
