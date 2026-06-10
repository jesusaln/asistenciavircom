<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BlogPost;
use App\Models\EmpresaConfiguracion;
use Illuminate\Support\Str;

class AddSummerTipsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $empresa = EmpresaConfiguracion::first();
        if (!$empresa) {
            $empresaId = 8; // ID por defecto si falla el first()
        } else {
            $empresaId = $empresa->id;
        }

        $titulo = '¡Adiós al Calor! Tips para sobrevivir al Verano en Hermosillo';
        $slug = 'adios-calor-tips-verano-hermosillo';
        
        // Limpiar para evitar duplicados
        BlogPost::withTrashed()->where('slug', $slug)->forceDelete();

        $resumen = 'Hermosillo es famoso por sus temperaturas extremas. Descubre cómo mantener tu hogar fresco sin que tu recibo de luz se dispare este mes.';
        $contenido = '
        <p>Llegó el mes de marzo y con él, el inicio de la temporada de calor en Sonora. En Climas del Desierto sabemos que los hermosillenses no solo sobrevivimos al calor, ¡nos adaptamos a él!</p>

        <h3>1. Mantenimiento Preventivo: El Secreto del Ahorro</h3>
        <p>Antes de que las temperaturas lleguen a los 45°C, es vital que tu minisplit esté en óptimas condiciones. Un equipo sucio puede consumir hasta un 30% más de energía.</p>

        <h3>2. La Temperatura Ideal</h3>
        <p>¿Sabías que por cada grado que bajas el termostato por debajo de los 24°C, el consumo aumenta un 8%? Mantenerlo en 24°C o 25°C es el punto de equilibrio perfecto entre confort y ahorro.</p>

        <h3>3. Aislamiento Térmico</h3>
        <p>Cierra cortinas durante las horas de sol directo y asegúrate de que no haya fugas de aire en puertas y ventanas. ¡No dejes que el frío se escape!</p>

        <h3>4. Tecnología Neo Magnum Inverter</h3>
        <p>Instalar un equipo de alta eficiencia como el <strong>Neo Magnum Inverter</strong> no es solo una cuestión de confort, es una inversión en tu economía familiar para las zonas de alta plusvalía como La Joya.</p>

        <p>Recuerda que en <strong>Climas del Desierto</strong> contamos con pólizas de mantenimiento para que tu única preocupación sea disfrutar de un ambiente fresco.</p>
        ';

        BlogPost::create([
            'empresa_id' => $empresaId,
            'titulo' => $titulo,
            'slug' => $slug,
            'resumen' => $resumen,
            'contenido' => $contenido,
            'imagen_portada' => 'https://vircom-app.s3.amazonaws.com/brain/c3b5756d-2500-463c-8adf-97440914dd5e/neo_magnum_inverter_joya_1774500885489.png',
            'categoria' => 'Consejos de Verano',
            'status' => 'published',
            'publicado_at' => now(),
            'visitas' => 0,
            'meta_titulo' => 'Consejos para sobrevivir al calor en Hermosillo | Climas del Desierto',
            'meta_descripcion' => $resumen,
        ]);
    }
}
