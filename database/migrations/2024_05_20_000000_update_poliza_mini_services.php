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

        // 2. Buscar la Póliza Mini y actualizarla
        $planMini = PlanPoliza::where('nombre', 'like', '%Mini%')->first();

        if ($planMini) {
            // Actualizamos incluye_servicios
            // El formato puede ser array de IDs [1, 2] o array de objetos [{id:1}, {id:2}]
            // Usaremos array de IDs simple para empezar, ya que mi código Vue lo soporta:
            // serviceId = Number(item);

            $planMini->incluye_servicios = $idsServicios;
            $planMini->save();

            echo "Plan '{$planMini->nombre}' actualizado con " . count($idsServicios) . " servicios.\n";
        } else {
            echo "No se encontró ningún plan con nombre 'Mini'.\n";
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
