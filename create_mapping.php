<?php
use App\Models\Contab\CuentaContable;
use Illuminate\Support\Facades\DB;

$nombre = 'GOBIERNO DEL ESTADO DE SONORA';
$cuentaCodigo = '602.07';
$empresaId = 8;

$rfc = DB::table('cfdis')->where('nombre_emisor', 'ILIKE', '%' . $nombre . '%')->value('rfc_emisor') ?: 'GES790913CT0';
$cuentaId = CuentaContable::where('codigo', $cuentaCodigo)->where('empresa_id', $empresaId)->value('id');

if($cuentaId) {
    DB::table('contab_rfc_mappings')->updateOrInsert(
        ['rfc' => $rfc, 'empresa_id' => $empresaId],
        ['cuenta_id' => $cuentaId, 'created_at' => now(), 'updated_at' => now()]
    );
    echo "Regla creada: $nombre ($rfc) -> $cuentaCodigo\n";
} else {
    echo "Error: No se encontro la cuenta $cuentaCodigo\n";
}
