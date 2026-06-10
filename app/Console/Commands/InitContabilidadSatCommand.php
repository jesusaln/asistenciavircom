<?php

namespace App\Console\Commands;

use App\Models\Contab\CuentaContable;
use App\Models\Empresa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InitContabilidadSatCommand extends Command
{
    protected $signature = 'contabilidad:init-sat {empresa_id? : ID de la empresa a inicializar}';
    protected $description = 'Inicializa el catálogo de cuentas base del SAT para una empresa';

    public function handle()
    {
        $empresaId = $this->argument('empresa_id');

        if ($empresaId) {
            $this->initEmpresa($empresaId);
        } else {
            $empresas = Empresa::all();
            foreach ($empresas as $empresa) {
                $this->initEmpresa($empresa->id);
            }
        }

        $this->info('Proceso de inicialización completado.');
    }

    private function initEmpresa($empresaId)
    {
        $empresa = Empresa::find($empresaId);
        if (!$empresa) {
            $this->error("Empresa $empresaId no encontrada.");
            return;
        }

        $this->info("Inicializando catálogo SAT para: {$empresa->nombre_empresa}");

        // Definición del catálogo base del SAT (Anexo 24 simplificado)
        $catalogo = [
            // ACTIVO
            ['codigo' => '1', 'nombre' => 'ACTIVO', 'tipo' => 'activo', 'naturaleza' => 'deudora', 'nivel' => 1, 'sat_codigo' => '1', 'es_detalle' => false],
            ['codigo' => '1.1', 'nombre' => 'ACTIVO CIRCULANTE', 'tipo' => 'activo', 'naturaleza' => 'deudora', 'nivel' => 2, 'sat_codigo' => '1.1', 'padre_codigo' => '1', 'es_detalle' => false],
            ['codigo' => '101', 'nombre' => 'CAJA', 'tipo' => 'activo', 'naturaleza' => 'deudora', 'nivel' => 3, 'sat_codigo' => '101', 'padre_codigo' => '1.1', 'es_detalle' => true],
            ['codigo' => '102', 'nombre' => 'BANCOS', 'tipo' => 'activo', 'naturaleza' => 'deudora', 'nivel' => 3, 'sat_codigo' => '102', 'padre_codigo' => '1.1', 'es_detalle' => false],
            ['codigo' => '102.01', 'nombre' => 'BANCOS NACIONALES', 'tipo' => 'activo', 'naturaleza' => 'deudora', 'nivel' => 4, 'sat_codigo' => '102.01', 'padre_codigo' => '102', 'es_detalle' => true],
            ['codigo' => '105', 'nombre' => 'CLIENTES', 'tipo' => 'activo', 'naturaleza' => 'deudora', 'nivel' => 3, 'sat_codigo' => '105', 'padre_codigo' => '1.1', 'es_detalle' => false],
            ['codigo' => '105.01', 'nombre' => 'CLIENTES NACIONALES', 'tipo' => 'activo', 'naturaleza' => 'deudora', 'nivel' => 4, 'sat_codigo' => '105.01', 'padre_codigo' => '105', 'es_detalle' => true],
            ['codigo' => '107', 'nombre' => 'DEUDORES DIVERSOS', 'tipo' => 'activo', 'naturaleza' => 'deudora', 'nivel' => 3, 'sat_codigo' => '107', 'padre_codigo' => '1.1', 'es_detalle' => true],
            ['codigo' => '113', 'nombre' => 'IMPUESTOS A FAVOR', 'tipo' => 'activo', 'naturaleza' => 'deudora', 'nivel' => 3, 'sat_codigo' => '113', 'padre_codigo' => '1.1', 'es_detalle' => false],
            ['codigo' => '113.01', 'nombre' => 'IVA A FAVOR', 'tipo' => 'activo', 'naturaleza' => 'deudora', 'nivel' => 4, 'sat_codigo' => '113.01', 'padre_codigo' => '113', 'es_detalle' => true],
            ['codigo' => '118', 'nombre' => 'IVA ACREDITABLE PAGADO', 'tipo' => 'activo', 'naturaleza' => 'deudora', 'nivel' => 3, 'sat_codigo' => '118', 'padre_codigo' => '1.1', 'es_detalle' => true],
            ['codigo' => '119', 'nombre' => 'IVA PENDIENTE DE PAGO', 'tipo' => 'activo', 'naturaleza' => 'deudora', 'nivel' => 3, 'sat_codigo' => '119', 'padre_codigo' => '1.1', 'es_detalle' => true],

            // PASIVO
            ['codigo' => '2', 'nombre' => 'PASIVO', 'tipo' => 'pasivo', 'naturaleza' => 'acreedora', 'nivel' => 1, 'sat_codigo' => '2', 'es_detalle' => false],
            ['codigo' => '2.1', 'nombre' => 'PASIVO CIRCULANTE', 'tipo' => 'pasivo', 'naturaleza' => 'acreedora', 'nivel' => 2, 'sat_codigo' => '2.1', 'padre_codigo' => '2', 'es_detalle' => false],
            ['codigo' => '201', 'nombre' => 'PROVEEDORES', 'tipo' => 'pasivo', 'naturaleza' => 'acreedora', 'nivel' => 3, 'sat_codigo' => '201', 'padre_codigo' => '2.1', 'es_detalle' => false],
            ['codigo' => '201.01', 'nombre' => 'PROVEEDORES NACIONALES', 'tipo' => 'pasivo', 'naturaleza' => 'acreedora', 'nivel' => 4, 'sat_codigo' => '201.01', 'padre_codigo' => '201', 'es_detalle' => true],
            ['codigo' => '205', 'nombre' => 'ACREEDORES DIVERSOS', 'tipo' => 'pasivo', 'naturaleza' => 'acreedora', 'nivel' => 3, 'sat_codigo' => '205', 'padre_codigo' => '2.1', 'es_detalle' => true],
            ['codigo' => '208', 'nombre' => 'IMPUESTOS RETENIDOS', 'tipo' => 'pasivo', 'naturaleza' => 'acreedora', 'nivel' => 3, 'sat_codigo' => '208', 'padre_codigo' => '2.1', 'es_detalle' => false],
            ['codigo' => '208.01', 'nombre' => 'IVA RETENIDO', 'tipo' => 'pasivo', 'naturaleza' => 'acreedora', 'nivel' => 4, 'sat_codigo' => '208.01', 'padre_codigo' => '208', 'es_detalle' => true],
            ['codigo' => '209', 'nombre' => 'IVA TRASLADADO COBRADO', 'tipo' => 'pasivo', 'naturaleza' => 'acreedora', 'nivel' => 3, 'sat_codigo' => '209', 'padre_codigo' => '2.1', 'es_detalle' => true],
            ['codigo' => '210', 'nombre' => 'IVA PENDIENTE DE COBRO', 'tipo' => 'pasivo', 'naturaleza' => 'acreedora', 'nivel' => 3, 'sat_codigo' => '210', 'padre_codigo' => '2.1', 'es_detalle' => true],

            // CAPITAL
            ['codigo' => '3', 'nombre' => 'CAPITAL CONTABLE', 'tipo' => 'capital', 'naturaleza' => 'acreedora', 'nivel' => 1, 'sat_codigo' => '3', 'es_detalle' => false],
            ['codigo' => '301', 'nombre' => 'CAPITAL SOCIAL', 'tipo' => 'capital', 'naturaleza' => 'acreedora', 'nivel' => 2, 'sat_codigo' => '301', 'padre_codigo' => '3', 'es_detalle' => true],
            ['codigo' => '304', 'nombre' => 'RESULTADOS DE EJERCICIOS ANTERIORES', 'tipo' => 'capital', 'naturaleza' => 'acreedora', 'nivel' => 2, 'sat_codigo' => '304', 'padre_codigo' => '3', 'es_detalle' => true],
            ['codigo' => '305', 'nombre' => 'RESULTADO DEL EJERCICIO', 'tipo' => 'capital', 'naturaleza' => 'acreedora', 'nivel' => 2, 'sat_codigo' => '305', 'padre_codigo' => '3', 'es_detalle' => true],

            // INGRESOS
            ['codigo' => '4', 'nombre' => 'INGRESOS', 'tipo' => 'ingreso', 'naturaleza' => 'acreedora', 'nivel' => 1, 'sat_codigo' => '4', 'es_detalle' => false],
            ['codigo' => '401', 'nombre' => 'VENTAS Y/O SERVICIOS', 'tipo' => 'ingreso', 'naturaleza' => 'acreedora', 'nivel' => 2, 'sat_codigo' => '401', 'padre_codigo' => '4', 'es_detalle' => false],
            ['codigo' => '401.01', 'nombre' => 'VENTAS TASA GENERAL', 'tipo' => 'ingreso', 'naturaleza' => 'acreedora', 'nivel' => 3, 'sat_codigo' => '401.01', 'padre_codigo' => '401', 'es_detalle' => true],

            // EGRESOS / GASTOS
            ['codigo' => '5', 'nombre' => 'COSTOS Y GASTOS', 'tipo' => 'egreso', 'naturaleza' => 'deudora', 'nivel' => 1, 'sat_codigo' => '5', 'es_detalle' => false],
            ['codigo' => '501', 'nombre' => 'COSTO DE VENTAS', 'tipo' => 'egreso', 'naturaleza' => 'deudora', 'nivel' => 2, 'sat_codigo' => '501', 'padre_codigo' => '5', 'es_detalle' => true],
            ['codigo' => '601', 'nombre' => 'GASTOS DE VENTA', 'tipo' => 'egreso', 'naturaleza' => 'deudora', 'nivel' => 2, 'sat_codigo' => '601', 'padre_codigo' => '5', 'es_detalle' => false],
            ['codigo' => '601.01', 'nombre' => 'GASTOS DE VENTA GENERAL', 'tipo' => 'egreso', 'naturaleza' => 'deudora', 'nivel' => 3, 'sat_codigo' => '601.01', 'padre_codigo' => '601', 'es_detalle' => true],
            ['codigo' => '601.03', 'nombre' => 'COMBUSTIBLES Y LUBRICANTES', 'tipo' => 'egreso', 'naturaleza' => 'deudora', 'nivel' => 3, 'sat_codigo' => '601.03', 'padre_codigo' => '601', 'es_detalle' => true],
            ['codigo' => '602', 'nombre' => 'GASTOS DE ADMINISTRACION', 'tipo' => 'egreso', 'naturaleza' => 'deudora', 'nivel' => 2, 'sat_codigo' => '602', 'padre_codigo' => '5', 'es_detalle' => false],
            ['codigo' => '602.01', 'nombre' => 'GASTOS DE ADMINISTRACION GENERAL', 'tipo' => 'egreso', 'naturaleza' => 'deudora', 'nivel' => 3, 'sat_codigo' => '602.01', 'padre_codigo' => '602', 'es_detalle' => true],
            ['codigo' => '602.03', 'nombre' => 'COMBUSTIBLES Y LUBRICANTES', 'tipo' => 'egreso', 'naturaleza' => 'deudora', 'nivel' => 3, 'sat_codigo' => '602.03', 'padre_codigo' => '602', 'es_detalle' => true],
            ['codigo' => '603', 'nombre' => 'GASTOS FINANCIEROS', 'tipo' => 'egreso', 'naturaleza' => 'deudora', 'nivel' => 2, 'sat_codigo' => '603', 'padre_codigo' => '5', 'es_detalle' => true],
        ];

        DB::transaction(function () use ($empresaId, $catalogo) {
            foreach ($catalogo as $item) {
                $padreId = null;
                if (isset($item['padre_codigo'])) {
                    $padre = CuentaContable::where('empresa_id', $empresaId)
                        ->where('codigo', $item['padre_codigo'])
                        ->first();
                    $padreId = $padre?->id;
                }

                CuentaContable::updateOrCreate(
                    ['empresa_id' => $empresaId, 'codigo' => $item['codigo']],
                    [
                        'nombre' => $item['nombre'],
                        'tipo' => $item['tipo'],
                        'naturaleza' => $item['naturaleza'],
                        'nivel' => $item['nivel'],
                        'padre_id' => $padreId,
                        'sat_codigo' => $item['sat_codigo'],
                        'es_detalle' => $item['es_detalle'],
                    ]
                );
            }
        });

        $this->info("Catálogo inicializado para empresa $empresaId.");
    }
}
