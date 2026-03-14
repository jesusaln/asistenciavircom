<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SeoLandingPage;
use App\Models\Empresa;

class SeoLandingSeeder extends Seeder
{
    public function run()
    {
        $empresa = Empresa::first();
        if (!$empresa)
            return;

        SeoLandingPage::updateOrCreate(
            ['slug' => 'camaras-de-seguridad-hermosillo', 'empresa_id' => $empresa->id],
            [
                'titulo_h1' => 'Instalación de Cámaras de Seguridad en Hermosillo',
                'meta_description' => 'Expertos en videovigilancia y cámaras de seguridad CCTV en Hermosillo, Sonora. Instalación profesional para empresas y hogares con monitoreo móvil.',
                'hero_title' => 'Protege lo que más importa con Cámaras de Seguridad',
                'hero_description' => 'Servicio profesional de instalación de cámaras IP, análogas y sistemas de monitoreo remoto en Hermosillo. Vigilancia 24/7 desde tu celular.',
                'service_category' => 'Cámaras',
                'location' => 'Hermosillo, Sonora',
                'hero_image_url' => 'https://images.unsplash.com/photo-1557597774-9d273605dfa9?q=80&w=2065&auto=format&fit=crop',
                'features' => [
                    ['icon' => '🛡️', 'title' => 'Monitoreo 24/7', 'desc' => 'Visualiza tus cámaras en tiempo real desde cualquier parte del mundo.'],
                    ['icon' => '💾', 'title' => 'Grabación Segura', 'desc' => 'Almacenamiento local y en la nube con discos de alta capacidad.'],
                    ['icon' => '🚨', 'title' => 'Detección de Movimiento', 'desc' => 'Recibe alertas inmediatas en tu smartphone ante cualquier actividad.'],
                ],
                'content_blocks' => [
                    [
                        'title' => '¿Por qué elegir Asistencia Vircom para tu seguridad?',
                        'content' => '<p>En Asistencia Vircom somos expertos en soluciones de seguridad tecnológica. Contamos con técnicos certificados y equipos de las mejores marcas como Hikvision y Dahua.</p><p>Nuestro servicio incluye levantamiento técnico sin costo, cableado profesional y configuración de red para acceso remoto.</p>'
                    ],
                    [
                        'title' => 'Servicio Local en Hermosillo',
                        'content' => '<p>Estamos ubicados en Hermosillo, lo que nos permite dar un soporte post-venta rápido y efectivo. No esperes días a que un técnico te atienda.</p>'
                    ]
                ],
                'is_active' => true
            ]
        );

        echo "Landing SEO 'camaras-de-seguridad-hermosillo' creada con éxito.\n";
    }
}
