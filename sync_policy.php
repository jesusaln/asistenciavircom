<?php
$empresaId = 8;
$tipo = 'egreso';
$fecha = '2026-05-08';
$concepto = 'Pago Sipare Imss Abril 26';
$total = 31647.06;
$userId = 1;

// 1. Obtener/Crear cuentas padre
$padres = [
    '102.01' => 'BANCOS NACIONALES',
    '213' => 'IMPUESTOS POR PAGAR',
    '602' => 'GASTOS DE ADMINISTRACION'
];
$padreIds = [];
foreach($padres as $cod => $nom) {
    $padreIds[$cod] = DB::table('contab_cuentas')->where('codigo', $cod)->value('id');
    if(!$padreIds[$cod]) {
        echo "Error: Cuenta padre $cod no encontrada en producción.\n";
        return;
    }
}

// 2. Crear subcuentas si no existen
$cuentas = [
    ['codigo' => '102.01-001', 'nombre' => 'BBVA 7504', 'padre_id' => $padreIds['102.01'], 'nivel' => 3, 'tipo' => 'activo', 'naturaleza' => 'deudora'],
    ['codigo' => '213.03', 'nombre' => 'IMSS por Pagar', 'padre_id' => $padreIds['213'], 'nivel' => 3, 'tipo' => 'pasivo', 'naturaleza' => 'acreedora'],
    ['codigo' => '213.04', 'nombre' => 'SAR e Infonavit por Pagar', 'padre_id' => $padreIds['213'], 'nivel' => 3, 'tipo' => 'pasivo', 'naturaleza' => 'acreedora'],
    ['codigo' => '602.05', 'nombre' => 'Cuotas Patronales IMSS', 'padre_id' => $padreIds['602'], 'nivel' => 3, 'tipo' => 'egreso', 'naturaleza' => 'deudora'],
    ['codigo' => '602.06', 'nombre' => 'SAR e Infonavit', 'padre_id' => $padreIds['602'], 'nivel' => 3, 'tipo' => 'egreso', 'naturaleza' => 'deudora'],
];
foreach($cuentas as $c) {
    if(!DB::table('contab_cuentas')->where('codigo', $c['codigo'])->exists()) {
        DB::table('contab_cuentas')->insert(array_merge($c, [
            'empresa_id' => $empresaId, 
            'es_detalle' => true, 
            'created_at' => now(), 
            'updated_at' => now()
        ]));
        echo "Cuenta {$c['codigo']} creada en Producción.\n";
    }
}

// 3. Crear Póliza
$ultimoFolio = DB::table('contab_polizas')
    ->where('empresa_id', $empresaId)
    ->where('tipo', $tipo)
    ->orderBy('numero', 'desc')
    ->value('numero') ?? 'E00000';
$num = (int)substr($ultimoFolio, 1);
$nuevoNumero = 'E' . str_pad($num + 1, 5, '0', STR_PAD_LEFT);

$soportes = [[
    'name' => 'imss080526.pdf',
    'path' => 'contab/soportes/JOZP6CORngC8qNOP9I0VNWOetKFB1lm82uHJev2O.pdf',
    'url' => 'https://climasdeldesierto.com/storage/contab/soportes/JOZP6CORngC8qNOP9I0VNWOetKFB1lm82uHJev2O.pdf',
    'date' => '2026-05-08 17:18'
]];

DB::beginTransaction();
try {
    $polizaId = DB::table('contab_polizas')->insertGetId([
        'empresa_id' => $empresaId,
        'tipo' => $tipo,
        'fecha' => $fecha,
        'numero' => $nuevoNumero,
        'concepto' => $concepto,
        'total' => $total,
        'estado' => 'asentada',
        'created_by' => $userId,
        'soportes' => json_encode($soportes),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $asientos = [
        ['cuenta' => '602.05', 'debe' => 28609.14, 'haber' => 0],
        ['cuenta' => '602.06', 'debe' => 3037.92, 'haber' => 0],
        ['cuenta' => '102.01-001', 'debe' => 0, 'haber' => 31647.06],
    ];

    foreach ($asientos as $asiento) {
        $cuentaId = DB::table('contab_cuentas')->where('codigo', $asiento['cuenta'])->value('id');
        DB::table('contab_asientos')->insert([
            'poliza_id' => $polizaId,
            'cuenta_id' => $cuentaId,
            'debe' => $asiento['debe'],
            'haber' => $asiento['haber'],
            'referencia' => 'SIPARE ABRIL 26',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    DB::commit();
    echo "Póliza $nuevoNumero migrada exitosamente a Producción.\n";
} catch (Exception $e) {
    DB::rollBack();
    echo "Error en Producción: " . $e->getMessage() . "\n";
}
