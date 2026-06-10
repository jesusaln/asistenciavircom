<?php
use App\Models\Contab\CuentaContable;
use Illuminate\Support\Facades\DB;

$empresaId = 8;
$padreId = CuentaContable::where('codigo', '602')->where('empresa_id', $empresaId)->first()?->id;

// 1. Crear la cuenta de mantenimiento de oficina
$cuenta = CuentaContable::updateOrCreate(
    ['codigo' => '602.12', 'empresa_id' => $empresaId],
    [
        'nombre' => 'Mantenimiento de Oficina y Edificio',
        'tipo' => 'egreso',
        'nivel' => 2,
        'padre_id' => $padreId,
        'activa' => true,
        'naturaleza' => 'deudora'
    ]
);
echo "Cuenta creada: 602.12 - Mantenimiento de Oficina y Edificio\n";

// 2. Crear la regla para DISTRIBUIDORA QUEZADA
$rfc = 'DQU1711297VA';
DB::table('contab_rfc_mappings')->updateOrInsert(
    ['rfc' => $rfc, 'empresa_id' => $empresaId],
    ['cuenta_id' => $cuenta->id, 'created_at' => now(), 'updated_at' => now()]
);
echo "Regla creada: DISTRIBUIDORA QUEZADA ($rfc) -> 602.12\n";
