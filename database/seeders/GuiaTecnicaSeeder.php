<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GuiaTecnica;

class GuiaTecnicaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        GuiaTecnica::firstOrCreate(
            ['route_name' => 'soporte.guias.discos'],
            [
                'nombre' => 'Mantenimiento de Discos (HDD/SSD/M1)',
                'descripcion' => 'Guía completa para el diagnóstico, reparación y optimización de unidades de almacenamiento físicas y lógicas.',
                'checklist_default' => [
                    'Verificar espacio en disco disponible',
                    'Ejecutar análisis S.M.A.R.T.',
                    'Comprobar estado de fragmentación (solo HDD)',
                    'Verificar ejecución de TRIM (SSD)',
                    'Revisar logs de sistema por errores de E/S',
                ]
            ]
        );
    }
}
