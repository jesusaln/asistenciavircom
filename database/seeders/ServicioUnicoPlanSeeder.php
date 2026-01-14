<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlanPoliza;
use App\Models\Empresa;

class ServicioUnicoPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el ID de la empresa por defecto (asumimos 1 o la primera)
        $empresaId = Empresa::first()->id ?? 1;

        PlanPoliza::updateOrCreate(
            ['slug' => 'servicio-unico'],
            [
                'empresa_id' => $empresaId,
                'nombre' => 'Servicio Único a Demanda',
                'descripcion' => 'Solución puntual para problemas técnicos. Remoto en todo México, Presencial solo Hermosillo.',
                'descripcion_corta' => 'Soporte puntual, pago único.',
                'tipo' => 'soporte',
                'icono' => '🚑',
                'color' => '#ef4444', // Red-500 para destacar urgencia/puntualidad
                'precio_mensual' => 560.34, // + 16% IVA = $649.99 (aprox $650)
                'precio_anual' => 0, // No aplica anualidad
                'precio_instalacion' => 0,
                'horas_incluidas' => 2, // 2 horas de servicio por el ticket
                'tickets_incluidos' => 1,
                'sla_horas_respuesta' => 24,
                'costo_hora_extra' => 350.00,
                'beneficios' => [
                    'Atención a 1 Equipo',
                    'Diagnóstico y Reparación',
                    'Remoto o Presencial (según zona)',
                    'Garantía de servicio 7 días'
                ],
                'incluye_servicios' => [],
                'activo' => true,
                'destacado' => false,
                'visible_catalogo' => true,
                'orden' => 10,
                'min_equipos' => 1,
                'max_equipos' => 1,
            ]
        );
    }
}
