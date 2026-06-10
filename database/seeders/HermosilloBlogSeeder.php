<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BlogPost;
use App\Models\EmpresaConfiguracion;
use Illuminate\Support\Str;

class HermosilloBlogSeeder extends Seeder
{
    public function run()
    {
        $empresa = EmpresaConfiguracion::first(); // Asumimos la primera empresa disponible
        $empresaId = $empresa ? $empresa->id : 1;

        $titulo = 'Sobreviviendo al calor de Hermosillo: La guía definitiva para tu aire acondicionado';
        
        BlogPost::updateOrCreate(
            ['titulo' => $titulo],
            [
                'empresa_id' => $empresaId,
                'slug' => Str::slug($titulo),
                'resumen' => 'Descubre por qué un mantenimiento a tiempo no solo salva tu billetera, sino que es clave para enfrentar temperaturas de 45°C en la Ciudad del Sol.',
                'contenido' => 'Hermosillo es conocida mundialmente como la "Ciudad del Sol", y no es casualidad. Con temperaturas que superan fácilmente los 45°C en verano, nuestro sistema de aire acondicionado deja de ser un lujo para convertirse en una necesidad básica de supervivencia.

En **Climas del Desierto**, sabemos que no hay nada peor que llegar a casa tras un día bajo el sol sonorense y descubrir que tu equipo no está enfriando. Por eso, hemos preparado esta guía rápida para que tu sistema no te falle cuando más lo necesites.

## 1. El Mantenimiento Preventivo: Tu mejor aliado
No esperes a que el equipo se detenga. Un mantenimiento preventivo antes de que inicie la temporada fuerte (marzo-abril) puede:
- Aumentar la vida útil de tu equipo hasta en 5 años.
- Reducir tu consumo eléctrico en un 20-30%.
- Evitar ruidos molestos y escurrimientos de agua.

## 2. Limpieza de Filtros: Lo que tú puedes hacer
¿Sabías que un filtro sucio es la causa #1 de equipos congelados? En Hermosillo, debido al polvo fino del desierto, recomendamos limpiar los filtros de tus minisplits cada 15 días durante el verano. Es un proceso sencillo que toma 5 minutos y salva tu recibo de luz.

## 3. La Temperatura Ideal
Aunque afuera estemos a 48°C, programar tu equipo a 16°C no hará que enfríe más rápido, solo hará que el compresor nunca descanse. La temperatura recomendada por expertos para nuestra región es entre **23°C y 25°C**. Tu cuerpo estará cómodo y tu bolsillo te lo agradecerá.

En **Climas del Desierto** estamos listos para apoyarte. No dejes que el calor te gane la partida este año.

**¡Contáctanos hoy para tu servicio de temporada!**',
                'imagen_portada' => '/images/blog/hermosillo-clima.png',
                'categoria' => 'Consejos',
                'status' => 'published',
                'publicado_at' => now(),
                'meta_titulo' => 'Aire Acondicionado en Hermosillo: Consejos y Mantenimiento',
                'meta_descripcion' => 'Guía definitiva para mantener tu aire acondicionado funcionando en el calor de Hermosillo, Sonora.',
            ]
        );
    }
}
