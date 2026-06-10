<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

// Helper to check columns
function hasCol($table, $col)
{
    return Schema::hasColumn($table, $col);
}

$empresa = DB::table('empresas')->first();
if (!$empresa) {
    echo "Creating Empresa...\n";
    $empresaId = DB::table('empresas')->insertGetId([
        'nombre_fiscal' => 'Empresa Demo SA de CV',
        'nombre_comercial' => 'Empresa Demo',
        'created_at' => now(),
        'updated_at' => now()
    ]);

    // Create config
    DB::table('empresa_configuracion')->insert([
        'empresa_id' => $empresaId,
        'nombre_empresa' => 'Empresa Demo',
        'email' => 'admin@vircom.com',
        'created_at' => now(),
        'updated_at' => now()
    ]);
} else {
    $empresaId = $empresa->id;
}

$cliente = DB::table('clientes')->first();
if (!$cliente) {
    echo "Creating Client...\n";
    $data = [
        'nombre_razon_social' => 'Cliente Deuda Test',
        'email' => 'deuda@vircom.com',
        'created_at' => now(),
        'updated_at' => now()
    ];

    if (hasCol('clientes', 'password'))
        $data['password'] = Hash::make('password123');
    if (hasCol('clientes', 'empresa_id'))
        $data['empresa_id'] = $empresaId;
    if (hasCol('clientes', 'activo'))
        $data['activo'] = true;
    if (hasCol('clientes', 'uuid'))
        $data['uuid'] = (string) Str::uuid();

    $clienteId = DB::table('clientes')->insertGetId($data);
    $cliente = DB::table('clientes')->where('id', $clienteId)->first();
}

$almacen = DB::table('almacenes')->first();
$almacenId = $almacen ? $almacen->id : null;
if (!$almacenId) {
    $almacenId = DB::table('almacenes')->insertGetId([
        'nombre' => 'Almacen General',
        'empresa_id' => $empresaId,
        'created_at' => now()
    ]);
}

echo "Creating Venta...\n";
$ventaId = DB::table('ventas')->insertGetId([
    'cliente_id' => $cliente->id,
    'empresa_id' => $empresaId,
    'almacen_id' => $almacenId,
    'vendedor_id' => 1,
    'numero_venta' => 'P-DEUDA-' . rand(1000, 9999),
    'fecha' => now()->subDays(60), // Muy vencida
    'estado' => 'vencida',
    'total' => 9999.00,
    'pagado' => false,
    'saldo_pendiente' => 9999.00,
    'notas' => 'Prueba Deuda Modal',
    'moneda' => 'MXN',
    'tipo_cambio' => 1,
    'created_at' => now(),
    'updated_at' => now()
]);

echo "OK\n";
echo "Venta Creada ID: $ventaId\n";
echo "Cliente Login: " . $cliente->email . "\n";
