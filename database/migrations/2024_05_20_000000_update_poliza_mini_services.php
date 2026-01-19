<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Servicio;
use App\Models\PlanPoliza;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Obtener una categoría válida (o crear una por defecto)
        $categoria = \App\Models\Categoria::first();
        if (!$categoria) {
            $categoria = \App\Models\Categoria::create([
                'nombre' => 'Servicios Generales',
                'empresa_id' => 1,
                'estado' => 'activo'
            ]);
        }

        // 2. Asegurar que existan los servicios
        $serviciosRequeridos = [
            [
                'nombre' => 'Soporte Técnico para sistemas Contpaqi',
                'descripcion' => 'Soporte especializado para paquetería Contpaqi',
                'precio' => 0,
            ],
            [
                'nombre' => 'Soporte Técnico para Equipos de Cómputo',
                'descripcion' => 'Mantenimiento y soporte para hardware y SO',
                'precio' => 0,
            ]
        ];

        $idsServicios = [];

        foreach ($serviciosRequeridos as $index => $data) {
            // Buscamos por nombre
            $servicio = Servicio::where('nombre', $data['nombre'])->first();

            if (!$servicio) {
                $servicio = Servicio::create([
                    'nombre' => $data['nombre'],
                    'descripcion' => $data['descripcion'],
                    'precio' => $data['precio'],
                    'categoria_id' => $categoria->id,
                    'empresa_id' => 1, // Asumiendo empresa 1
                    'codigo' => 'SERV-' . strtoupper(Str::random(6)),
                    'unidad_medida' => 'servicio',
                    'estado' => 'activo',
                    'duracion' => 60 // Duración por defecto en minutos
                ]);
            }

            $idsServicios[] = $servicio->id;
        }

        // 3. Buscar la Póliza (Mini o Pyme Estándar) y actualizarla
        $planTarget = PlanPoliza::where('nombre', 'like', '%Estándar%')
            ->orWhere('nombre', 'like', '%Estandar%')
            ->orWhere('nombre', 'like', '%Mini%')
            ->first();

        if ($planTarget) {
            $planTarget->incluye_servicios = $idsServicios;
            $planTarget->save();

            echo "Plan '{$planTarget->nombre}' actualizado con " . count($idsServicios) . " servicios.\n";
        } else {
            echo "No se encontró ningún plan con nombre 'Estándar' ni 'Mini'.\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertimos la creación de servicios para no borrar datos históricos por accidente
        // Solo limpiamos la póliza mini si se quiere
    }
};
