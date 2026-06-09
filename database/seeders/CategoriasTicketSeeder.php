<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class CategoriasTicketSeeder extends Seeder
{
    public function run()
    {
        $empresas = Empresa::all();

        if ($empresas->isEmpty()) {
            return;
        }

        $categories = [
            [
                'nombre' => 'Sistema',
                'descripcion' => 'Problemas relacionados con software o sistemas.',
                'color' => '#3B82F6', // Blue
                'sla_horas' => 24,
                'activo' => true,
            ],
            [
                'nombre' => 'Equipo de Cómputo',
                'descripcion' => 'Problemas de hardware en computadoras/laptops.',
                'color' => '#10B981', // Green
                'sla_horas' => 48,
                'activo' => true,
            ],
            [
                'nombre' => 'Alarma',
                'descripcion' => 'Incidencias con sistemas de alarma.',
                'color' => '#EF4444', // Red
                'sla_horas' => 12,
                'activo' => true,
            ],
            [
                'nombre' => 'Cámaras',
                'descripcion' => 'Problemas con cámaras de seguridad (CCTV).',
                'color' => '#F59E0B', // Yellow
                'sla_horas' => 48,
                'activo' => true,
            ],
        ];

        foreach ($empresas as $empresa) {
            foreach ($categories as $category) {
                // Check if category exists for this empresa by name
                $exists = TicketCategory::where('empresa_id', $empresa->id)
                    ->where('nombre', $category['nombre'])
                    ->exists();

                if (!$exists) {
                    TicketCategory::create(array_merge($category, ['empresa_id' => $empresa->id]));
                }
            }
        }
    }
}
