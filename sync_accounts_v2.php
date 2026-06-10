<?php
use App\Models\Contab\CuentaContable;

$empresaId = 8;
$padreId = CuentaContable::where('codigo', '602')->where('empresa_id', $empresaId)->first()?->id;

$cuentas = [
    ['codigo' => '602.07', 'nombre' => 'Impuesto sobre Nóminas (3% Estatal)'],
    ['codigo' => '602.08', 'nombre' => 'Honorarios Profesionales'],
    ['codigo' => '602.09', 'nombre' => 'Papelería y Artículos de Oficina'],
    ['codigo' => '602.10', 'nombre' => 'Energía Eléctrica, Agua y Teléfono'],
    ['codigo' => '602.11', 'nombre' => 'Arrendamientos (Rentas)'],
];

foreach ($cuentas as $c) {
    CuentaContable::updateOrCreate(
        ['codigo' => $c['codigo'], 'empresa_id' => $empresaId],
        [
            'nombre' => $c['nombre'],
            'tipo' => 'egreso', // Corregido a 'egreso'
            'nivel' => 2,
            'padre_id' => $padreId,
            'activa' => true,
            'naturaleza' => 'deudora'
        ]
    );
    echo "Cuenta sincronizada: {$c['codigo']} - {$c['nombre']}\n";
}
