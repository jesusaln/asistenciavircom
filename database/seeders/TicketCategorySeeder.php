<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use App\Support\EmpresaResolver;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    /**
     * Categorías predeterminadas para tickets de soporte de climatización.
     */
    public function run(): void
    {
        $empresaId = EmpresaResolver::resolveId();

        $categorias = [
            [
                'nombre' => 'Instalación',
                'descripcion' => 'Consultas y problemas relacionados con instalación de equipos',
                'color' => '#FF6B35', // Azul
                'icono' => '🔧',
                'sla_horas' => 24,
                'orden' => 1,
            ],
            [
                'nombre' => 'Reparación',
                'descripcion' => 'Solicitudes de reparación de equipos con fallas',
                'color' => '#EF4444', // Rojo
                'icono' => '🛠️',
                'sla_horas' => 8,
                'orden' => 2,
            ],
            [
                'nombre' => 'Mantenimiento',
                'descripcion' => 'Mantenimiento preventivo y correctivo programado',
                'color' => '#10B981', // Verde
                'icono' => '🔄',
                'sla_horas' => 48,
                'orden' => 3,
            ],
            [
                'nombre' => 'Garantía',
                'descripcion' => 'Reclamos y seguimiento de garantías de productos',
                'color' => '#8B5CF6', // Morado
                'icono' => '🛡️',
                'sla_horas' => 24,
                'orden' => 4,
            ],
            [
                'nombre' => 'Consulta Técnica',
                'descripcion' => 'Preguntas técnicas sobre equipos y funcionamiento',
                'color' => '#F59E0B', // Ámbar
                'icono' => '❓',
                'sla_horas' => 72,
                'orden' => 5,
            ],
            [
                'nombre' => 'Cotización',
                'descripcion' => 'Solicitud de cotizaciones para equipos o servicios',
                'color' => '#06B6D4', // Cyan
                'icono' => '💰',
                'sla_horas' => 24,
                'orden' => 6,
            ],
            [
                'nombre' => 'Póliza de Servicio',
                'descripcion' => 'Consultas sobre pólizas de mantenimiento contratadas',
                'color' => '#EC4899', // Rosa
                'icono' => '📋',
                'sla_horas' => 12,
                'orden' => 7,
            ],
            [
                'nombre' => 'Otros',
                'descripcion' => 'Otros temas no clasificados',
                'color' => '#6B7280', // Gris
                'icono' => '📦',
                'sla_horas' => 48,
                'orden' => 99,
            ],
        ];

        foreach ($categorias as $categoria) {
            TicketCategory::firstOrCreate(
                [
                    'nombre' => $categoria['nombre'],
                    'empresa_id' => $empresaId,
                ],
                array_merge($categoria, [
                    'empresa_id' => $empresaId,
                    'activo' => true,
                ])
            );
        }

        $this->command->info('✅ Categorías de tickets creadas correctamente.');
    }
}
