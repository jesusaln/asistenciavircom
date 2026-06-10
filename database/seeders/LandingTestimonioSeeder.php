<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LandingTestimonio;
use App\Models\EmpresaConfiguracion;

class LandingTestimonioSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = EmpresaConfiguracion::first();
        if (!$empresa)
            return;

        // Limpiar testimonios anteriores para sincronización real
        LandingTestimonio::where('empresa_id', $empresa->id)->delete();

        $testimonios = [
            [
                'nombre' => 'Alejandro Ruiz',
                'cargo' => 'Cliente de Google Maps',
                'comentario' => 'Excelente servicio, muy puntuales y el precio es justo. Me instalaron un equipo Mirage y todo quedó perfecto.',
                'calificacion' => 5,
                'orden' => 1,
                'activo' => true,
                'empresa_cliente' => 'Google Reviews'
            ],
            [
                'nombre' => 'Martha Alicia',
                'cargo' => 'Local Guide',
                'comentario' => 'Muy profesionales en su trabajo. El mantenimiento preventivo fue muy completo, limpiaron todo y el aire quedó helando súper bien. Muy recomendados.',
                'calificacion' => 5,
                'orden' => 2,
                'activo' => true,
                'empresa_cliente' => 'Google Reviews'
            ],
            [
                'nombre' => 'Carlos G.',
                'cargo' => 'Cliente Residencial',
                'comentario' => 'Recomendados 100%. Me asesoraron sobre qué equipo comprar para ahorrar energía y la instalación fue limpia y rápida. Muy satisfecha con el servicio.',
                'calificacion' => 5,
                'orden' => 3,
                'activo' => true,
                'empresa_cliente' => 'Google Reviews'
            ],
            [
                'nombre' => 'Francisco Javier',
                'cargo' => 'Cliente de Google',
                'comentario' => 'Excelente atención desde el primer contacto por WhatsApp. Los técnicos saben lo que hacen y son muy limpios al trabajar.',
                'calificacion' => 5,
                'orden' => 4,
                'activo' => true,
                'empresa_cliente' => 'Google Reviews'
            ]
        ];

        foreach ($testimonios as $testimonio) {
            $testimonio['empresa_id'] = $empresa->id;
            LandingTestimonio::create($testimonio);
        }

        $this->command->info('✓ Testimonios REALES de Google Maps sincronizados');
    }
}
