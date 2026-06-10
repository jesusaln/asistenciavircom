<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$articulos = [
    [
        'titulo' => '¿Recibo de luz muy alto? 5 errores que disparan tu consumo en Sonora',
        'slug' => Str::slug('¿Recibo de luz muy alto? 5 errores que disparan tu consumo en Sonora'),
        'resumen' => '¿Te asustó el recibo de la CFE? Descubre los errores más comunes al usar tu minisplit en el calor extremo de Sonora y cómo corregirlos para ahorrar dinero.',
        'contenido' => '
            <p>En Sonora, el aire acondicionado no es un lujo, es una necesidad de supervivencia. Sin embargo, esa necesidad suele venir acompañada de recibos de luz que pueden desequilibrar cualquier presupuesto familiar. Aquí te presentamos los 5 errores más comunes que están inflando tu factura eléctrica:</p>
            
            <h3>1. El "Efecto Congelador"</h3>
            <p>Muchos sonorenses llegan a casa y ponen el AC a 16°C pensando que así enfriará más rápido. <strong>Falso.</strong> El equipo enfriará a la misma velocidad, pero trabajará mucho más tiempo sin descanso. Lo ideal es mantenerlo entre 22°C y 24°C.</p>
            
            <h3>2. Ignorar el Mantenimiento Preventivo</h3>
            <p>Un equipo sucio puede consumir hasta un 30% más de energía al esforzarse por pasar aire a través de filtros tapados con el fino polvo del desierto. Una limpieza profesional cada 6 meses es la mejor inversión.</p>
            
            <h3>3. Fugas de Aire en Ventanas y Puertas</h3>
            <p>Si el aire frío se escapa, tu compresor nunca dejará de trabajar. Revisa los sellos de tus puertas y considera usar cortinas térmicas para bloquear el intenso sol de la tarde.</p>
            
            <h3>4. No usar el Modo "Sleep"</h3>
            <p>Durante la noche, el cuerpo necesita menos frío. El modo Sleep ajusta gradualmente la temperatura, ahorrando energía mientras duermes profundamente.</p>
            
            <h3>5. Dejar el equipo encendido todo el día</h3>
            <p>A menos que tengas tecnología Inverter, dejar el AC encendido en una habitación vacía es tirar dinero. Si tienes Inverter, es más eficiente dejarlo a una temperatura moderada que apagarlo y prenderlo constantemente.</p>
            
            <p><strong>¿Quieres ahorrar de verdad?</strong> Acércate a <strong>Climas del Desierto</strong> para una revisión de eficiencia de tus equipos.</p>
        ',
        'imagen_portada' => 'images/blog/recibo-luz-alto.webp',
        'categoria' => 'Tips de Ahorro',
        'status' => 'published',
        'publicado_at' => now(),
        'visitas' => 0,
        'meta_titulo' => 'Cómo bajar el recibo de luz CFE en Sonora | Climas del Desierto',
        'meta_descripcion' => 'Aprende a evitar los errores comunes que elevan tu consumo eléctrico en Hermosillo. Tips de ahorro para tu minisplit este verano.',
    ],
    [
        'titulo' => 'Modo Dry vs. Modo Cool: ¿Cuál usar durante el "monzón" sonorense?',
        'slug' => Str::slug('Modo Dry vs. Modo Cool: ¿Cuál usar durante el monzón sonorense'),
        'resumen' => 'Cuando llega la humedad a Sonora, tu minisplit tiene un arma secreta: el modo Dry. Aprende cuándo usarlo para mejorar tu confort y ahorrar energía.',
        'contenido' => '
            <p>En julio y agosto, Hermosillo vive un cambio drástico: de un calor seco pasamos a la famosa humedad del monzón. Es ese momento donde "el calor se siente más pesado". Aquí es donde el <strong>Modo Dry (Seco)</strong> de tu control remoto se vuelve tu mejor amigo.</p>
            
            <h3>¿Qué hace el Modo Cool?</h3>
            <p>Su función principal es bajar la temperatura. El ventilador sopla con fuerza y el compresor trabaja hasta llegar a los grados seleccionados.</p>
            
            <h3>¿Qué hace el Modo Dry?</h3>
            <p>Su prioridad es <strong>eliminar la humedad del ambiente</strong>. El ventilador funciona a baja velocidad y el serpentín se mantiene muy frío para condensar el agua del aire. No baja la temperatura tan drásticamente, pero elimina esa sensación pegajosa.</p>
            
            <h3>¿Cuándo usar cada uno?</h3>
            <ul>
                <li><strong>Modo Cool:</strong> En los días de "calor seco" de mayo y junio, o cuando necesitas enfriar una habitación rápido.</li>
                <li><strong>Modo Dry:</strong> En los días lluviosos o nublados donde no hace tanto calor, pero la humedad es alta. También es ideal para dormir, ya que es más silencioso y evita que despiertes con la garganta reseca.</li>
            </ul>
            
            <p>Usa el modo Dry sabiamente y notarás un ambiente mucho más fresco sin necesidad de bajar el termostato a extremos.</p>
        ',
        'imagen_portada' => 'images/blog/modo-dry-vs-cool.webp',
        'categoria' => 'Consejos de Verano',
        'status' => 'published',
        'publicado_at' => now(),
        'visitas' => 0,
        'meta_titulo' => 'Cuándo usar el Modo Dry (Seco) en Sonora | Climas del Desierto',
        'meta_descripcion' => 'Descubre la diferencia entre modo Cool y Dry. Mejora tu confort durante la temporada de lluvias en Hermosillo con estos tips.',
    ],
    [
        'titulo' => '¡No dejes que falle! 5 señales de que tu AC necesita reparación urgente',
        'slug' => Str::slug('5 señales de que tu AC necesita reparación urgente'),
        'resumen' => 'Detectar una falla a tiempo puede ahorrarte miles de pesos. Conoce los síntomas que indican que tu minisplit está en peligro antes de que deje de enfriar.',
        'contenido' => '
            <p>Esperar a que el minisplit deje de aventar aire frío por completo suele ser un error costoso. Normalmente, el equipo nos da "avisos" de que algo anda mal. Identificar estas 5 señales te salvará de pasar una noche de insomnio a 40 grados:</p>
            
            <h3>1. Ruidos Extraños</h3>
            <p>Silbidos, golpes metálicos o zumbidos excesivos en la unidad exterior pueden indicar desde un aspa suelta hasta un compresor a punto de colapsar.</p>
            
            <h3>2. Goteo de Agua en el Interior</h3>
            <p>Si ves agua corriendo por tu pared, el drenaje está tapado o el equipo se está congelando por falta de gas. Ignorarlo puede dañar tu pintura y los componentes electrónicos del AC.</p>
            
            <h3>3. Malos Olores</h3>
            <p>Un olor a quemado es una emergencia eléctrica. Un olor a humedad persistente indica proliferación de hongos en la turbina que afectan tu salud respiratoria.</p>
            
            <h3>4. Ciclos Cortos (Se apaga y prende mucho)</h3>
            <p>Si tu equipo se apaga antes de enfriar y vuelve a prender a los pocos minutos, algo está forzando el sistema, probablemente un sensor fallido o sobrecalentamiento.</p>
            
            <h3>5. Hielo en la Unidad Exterior</h3>
            <p>Si ves "nieve" o escarcha en las tuberías de cobre de afuera, tu equipo tiene una fuga de refrigerante o está extremadamente sucio. Detenlo inmediatamente y llama a un técnico.</p>
            
            <p><strong>No te arriesgues.</strong> En <strong>Climas del Desierto</strong> atendemos tus urgencias en tiempo récord con técnicos certificados.</p>
        ',
        'imagen_portada' => 'images/blog/senales-falla-ac.webp',
        'categoria' => 'Servicios técnicos',
        'status' => 'published',
        'publicado_at' => now(),
        'visitas' => 0,
        'meta_titulo' => 'Reparación de Aire Acondicionado en Hermosillo | Climas del Desierto',
        'meta_descripcion' => 'Aprende a identificar las fallas comunes de tu minisplit antes de que sea tarde. Servicio técnico profesional en Sonora.',
    ],
    [
        'titulo' => 'Alergias y Polvo: Aire puro en el interior a pesar del desierto',
        'slug' => Str::slug('Alergias y Polvo: Aire puro en el interior a pesar del desierto'),
        'resumen' => 'Vivir en un desierto significa combatir el polvo constante. Descubre cómo tu minisplit puede ser tu mejor aliado (o tu peor enemigo) para las alergias.',
        'contenido' => '
            <p>Para quienes vivimos en Sonora, el polvo es parte del paisaje. Sin embargo, para las personas con alergias o asma, puede ser un suplicio. Tu sistema de aire acondicionado juega un papel crucial en la calidad del aire que respiras en casa.</p>
            
            <h3>El Pulmón de tu Casa</h3>
            <p>Los filtros de tu minisplit actúan como los pulmones del hogar. En una ciudad como Hermosillo, estos filtros se saturan mucho más rápido que en otros lugares. Recomendamos lavarlos **cada 15 días** durante la temporada alta.</p>
            
            <h3>Tecnología de Purificación</h3>
            <p>Los equipos modernos de marcas como <strong>Mirage</strong> ya integran filtros de alta densidad e incluso ionizadores que ayudan a precipitar las partículas de polvo y eliminar bacterias en el aire.</p>
            
            <h3>Limpieza Profunda de la Turbina</h3>
            <p>Aunque limpies tus filtros, el polvo fino logra pasar y se pega a la turbina húmeda, creando moho. Una limpieza con hidrolavadora a presión (mantenimiento profundo) es esencial al menos una vez al año para asegurar que el aire que sale sea realmente puro.</p>
            
            <p>Respira tranquilo. Asegura la salud de tu familia con un servicio de desinfección de equipos realizado por profesionales.</p>
        ',
        'imagen_portada' => 'images/blog/aire-puro-desierto.webp',
        'categoria' => 'Salud y Bienestar',
        'status' => 'published',
        'publicado_at' => now(),
        'visitas' => 0,
        'meta_titulo' => 'Mejorar Calidad del Aire en Casa Sonora | Climas del Desierto',
        'meta_descripcion' => 'Evita alergias causadas por el polvo del desierto. Recomendaciones para mantener los filtros de tu minisplit limpios y el aire puro.',
    ],
    [
        'titulo' => 'Inverter vs. On/Off: ¿Realmente vale la pena la inversión en Sonora?',
        'slug' => Str::slug('Inverter vs On Off: Vale la pena la inversión en Sonora'),
        'resumen' => 'La pregunta eterna al comprar un AC. Analizamos el costo-beneficio de la tecnología Inverter bajo las temperaturas extremas de nuestro estado.',
        'contenido' => '
            <p>Al entrar a una tienda de aires acondicionados, lo primero que notarás es la diferencia de precio entre un equipo tradicional (On/Off) y uno <strong>Inverter</strong>. ¿Por qué pagar más? La respuesta corta es: porque en Sonora, el Inverter se paga solo.</p>
            
            <h3>La Diferencia Técnica</h3>
            <p>Un equipo On/Off es como un interruptor: o está al 100% o está al 0%. Cada vez que el cuarto se calienta un poco, arranca con un pico de consumo eléctrico enorme. El Inverter es como un acelerador de coche: ajusta su velocidad suavemente, consumiendo solo lo mínimo necesario para mantener la temperatura.</p>
            
            <h3>El Factor Sonora</h3>
            <p>Debido a que en Sonora un AC puede estar encendido 12 o 18 horas al día durante el verano, el ahorro del 50% al 60% en el consumo eléctrico se nota de inmediato en el recibo de la CFE.</p>
            
            <h3>Cálculo de Retorno de Inversión</h3>
            <p>En promedio, un usuario sonorense recupera la diferencia de precio de un equipo Inverter en **una sola temporada de verano (6 meses)** gracias al ahorro en electricidad. A partir del segundo año, todo es dinero que se queda en tu bolsillo.</p>
            
            <p>¿Buscas el mejor precio en equipos Mirage Inverter? <a href="/tienda">Visita nuestra tienda en línea</a> o visítanos.</p>
        ',
        'imagen_portada' => 'images/blog/inverter-vs-onoff.webp',
        'categoria' => 'Guías de Compra',
        'status' => 'published',
        'publicado_at' => now(),
        'visitas' => 0,
        'meta_titulo' => 'Ahorro Inverter en Sonora: Guía Comparativa | Climas del Desierto',
        'meta_descripcion' => '¿Vale la pena comprar un minisplit Inverter en Hermosillo? Calculamos el ahorro y tiempo de recuperación de inversión frente a equipos tradicionales.',
    ],
];

foreach ($articulos as $art) {
    if (!DB::table("blog_posts")->where("slug", $art["slug"])->exists()) {
        DB::table("blog_posts")->insert($art);
        echo "Insertado: " . $art['titulo'] . "\n";
    } else {
        echo "Ya existe: " . $art['titulo'] . "\n";
    }
}
echo "Proceso terminado.\n";
