<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Empresa;

echo "--- STARTING DB RECOVERY ---\n";

// 1. Fix Users Table (Add empresa_id)
if (!Schema::hasColumn('users', 'empresa_id')) {
    echo "Adding empresa_id to users table...\n";
    Schema::table('users', function (Blueprint $table) {
        $table->foreignId('empresa_id')->nullable()->after('id');
    });
} else {
    echo "users.empresa_id already exists.\n";
}

// 2. Ensure Empresa Exists
$empresaCount = DB::table('empresas')->count();
$empresaId = null;

if ($empresaCount == 0) {
    echo "Creating default Enterprise...\n";
    $empresaId = DB::table('empresas')->insertGetId([
        'nombre_razon_social' => 'Climas del Desierto', // Corrected column name
        'rfc' => 'XAXX010101000',
        'tipo_persona' => 'moral',
        'regimen_fiscal' => '601',
        'uso_cfdi' => 'G03', // Gastos en general
        'codigo_postal' => '83000',
        'pais' => 'México',
        'estado' => 'Sonora',
        'municipio' => 'San Luis Río Colorado',
        'email' => 'admin@climasdeldesierto.com',
        'telefono' => '6535340000',
        'calle' => 'Ave. Libertad',
        'numero_exterior' => '1',
        'colonia' => 'Centro',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "Created Enterprise ID: $empresaId\n";
} else {
    $empresaId = DB::table('empresas')->first()->id;
    echo "Using existing Enterprise ID: $empresaId\n";
}

// 3. Link Users to Enterprise
echo "Linking Users to Enterprise...\n";
DB::table('users')->whereNull('empresa_id')->update(['empresa_id' => $empresaId]);

// 4. Link Clientes to Enterprise (if column exists)
if (Schema::hasColumn('clientes', 'empresa_id')) {
    echo "Linking Clientes to Enterprise...\n";
    DB::table('clientes')->whereNull('empresa_id')->update(['empresa_id' => $empresaId]);
}

// 5. Link Proveedores
if (Schema::hasColumn('proveedores', 'empresa_id')) {
    echo "Linking Proveedores to Enterprise...\n";
    DB::table('proveedores')->whereNull('empresa_id')->update(['empresa_id' => $empresaId]);
}

// 6. Link Cotizaciones
if (Schema::hasColumn('cotizaciones', 'empresa_id')) {
    echo "Linking Cotizaciones to Enterprise...\n";
    DB::table('cotizaciones')->whereNull('empresa_id')->update(['empresa_id' => $empresaId]);
}

// 7. Link Pedidos
if (Schema::hasColumn('pedidos', 'empresa_id')) {
    echo "Linking Pedidos to Enterprise...\n";
    DB::table('pedidos')->whereNull('empresa_id')->update(['empresa_id' => $empresaId]);
}

echo "--- RECOVERY COMPLETE ---\n";
