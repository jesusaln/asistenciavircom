<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BlogPost;
use Illuminate\Support\Carbon;

class BlogPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = [
            [
                'titulo' => '5 Razones por las que tu negocio necesita Videovigilancia IP hoy mismo',
                'resumen' => '¿Todavía usas cámaras analógicas? Descubre por qué la tecnología IP es la inversión más inteligente para proteger tus activos y mejorar la operatividad.',
                'contenido' => '
                    <p>En el mundo actual de la seguridad electrónica, la diferencia entre un sistema analógico antiguo y una solución de <strong>Videovigilancia IP</strong> moderna es abismal.</p>
                    
                    <h3>1. Calidad de Imagen Superior</h3>
                    <p>Mientras que las cámaras analógicas tienen límites en su resolución, las cámaras IP ofrecen calidad Full HD, 4K y superior, permitiendo identificar rostros y placas vehiculares con claridad cristalina.</p>

                    <h3>2. Análisis Inteligente de Video (IA)</h3>
                    <p>Las cámaras modernas no solo graban; piensan. Pueden detectar cruces de línea, merodeo, conteo de personas y objetos abandonados, enviando alertas en tiempo real a tu celular.</p>

                    <h3>3. Escalabilidad y Flexibilidad</h3>
                    <p>Agregar una cámara nueva es tan sencillo como conectarla a la red. No necesitas costosos cableados coaxiales dedicados desde el DVR hasta cada punto.</p>

                    <h3>4. Acceso Remoto Seguro</h3>
                    <p>Visualiza tu negocio desde cualquier parte del mundo mediante aplicaciones móviles encriptadas, garantizando que solo tú tengas acceso a la información.</p>

                    <h3>5. Retorno de Inversión (ROI)</h3>
                    <p>Un sistema IP no es un gasto, es una inversión. Reduce pérdidas por robo hormiga, mejora la productividad del personal y puede reducir costos de primas de seguros.</p>

                    <p>En <strong>Asistencia Vircom</strong> somos expertos en migración y diseño de sistemas IP. Contáctanos para una asesoría gratuita.</p>
                ',
                'categoria' => 'Seguridad',
                'status' => 'published',
                'publicado_at' => Carbon::now()->subDays(2),
                'imagen_portada' => 'https://images.unsplash.com/photo-1557597774-9d273605dfa9?q=80&w=2070&auto=format&fit=crop',
            ],
            [
                'titulo' => 'Mantenimiento Preventivo: El secreto para alargar la vida de tus equipos de cómputo',
                'resumen' => 'No esperes a que tu computadora falle. Un plan de mantenimiento regular puede ahorrarte miles de pesos en reparaciones y pérdida de datos.',
                'contenido' => '
                    <p>Las computadoras son el corazón de cualquier oficina moderna. Sin embargo, a menudo las descuidamos hasta que la famosa "pantalla azul" aparece.</p>

                    <h3>Polvo: El enemigo silencioso</h3>
                    <p>La acumulación de polvo obstruye los ventiladores, elevando la temperatura de los componentes críticos como el procesador y el disco duro, reduciendo drásticamente su vida útil.</p>

                    <h3>Pasta Térmica</h3>
                    <p>Con el tiempo, la pasta térmica entre el procesador y el disipador se seca. Reemplazarla cada 12-18 meses mantiene tu equipo funcionando a temperaturas óptimas.</p>

                    <h3>Software y Actualizaciones</h3>
                    <p>El mantenimiento no es solo físico. Limpiar archivos temporales, optimizar el registro y asegurar que el sistema operativo esté actualizado previene lentitud y vulnerabilidades de seguridad.</p>

                    <p>Nuestras <strong>Pólizas de Mantenimiento</strong> aseguran que tus equipos reciban atención profesional periódica, garantizando la continuidad operativa de tu empresa.</p>
                ',
                'categoria' => 'Soporte TI',
                'status' => 'published',
                'publicado_at' => Carbon::now()->subDays(5),
                'imagen_portada' => 'https://images.unsplash.com/photo-1597424882777-62f7903ee94c?q=80&w=2070&auto=format&fit=crop',
            ],
            [
                'titulo' => 'WiFi Empresarial vs Doméstico: ¿Por qué mi internet sigue lento?',
                'resumen' => 'Usar un router casero en tu oficina es una receta para la frustración. Conoce las diferencias clave y por qué necesitas Access Points profesionales.',
                'contenido' => '
                    <p>Es un escenario común: contratas un plan de internet de alta velocidad, pero la conexión se cae constantemente o es lenta cuando todos están trabajando. El culpable suele ser el equipo, no el proveedor.</p>

                    <h3>Capacidad de Usuarios</h3>
                    <p>Un router doméstico está diseñado para manejar 5-10 dispositivos simultáneamente. Un Access Point (AP) empresarial puede manejar 50, 100 o más usuarios sin despeinarse.</p>

                    <h3>Roaming (Itinerancia)</h3>
                    <p>Sistemas como Ubiquiti o Aruba permiten que te muevas por toda la oficina sin que tu dispositivo se desconecte, saltando automáticamente al AP con mejor señal. Con repetidores caseros, esto es imposible.</p>

                    <h3>Seguridad y Gestión (VLANs)</h3>
                    <p>Separa la red de "Invitados" de la red "Administrativa". Protege tus datos sensibles aislando el tráfico crítico de los visitantes.</p>

                    <p>Actualizar tu infraestructura de red es esencial para la productividad. En <strong>Asistencia Vircom</strong> diseñamos redes robustas y seguras a tu medida.</p>
                ',
                'categoria' => 'Redes',
                'status' => 'published',
                'publicado_at' => Carbon::now()->subDays(10),
                'imagen_portada' => 'https://images.unsplash.com/photo-1544197150-b99a580bbcbf?q=80&w=2071&auto=format&fit=crop',
            ],
            [
                'titulo' => 'Reloj Checador: Prepárate para la jornada de 40 horas y el RIT',
                'resumen' => 'Descubre cómo un reloj checador biométrico no solo optimiza tu empresa ante la inminente reforma laboral de 40 horas en México, sino que es clave para aplicar el Reglamento Interior de Trabajo.',
                'contenido' => '
                    <p>Con la inminente aprobación de la reducción de la jornada laboral a <strong>40 horas semanales en México</strong>, el control del tiempo y asistencia dejará de ser opcional para convertirse en una necesidad crítica para cualquier empresa.</p>

                    <h3>El desafío de las 40 horas</h3>
                    <p>La nueva ley exigirá a los empleadores llevar un registro milimétrico de las horas laboradas para evitar demandas por horas extras no pagadas y cuantiosas multas de la STPS. Un <strong>reloj checador biométrico</strong> automatiza este proceso, garantizando cálculos exactos, eliminando el "robo de tiempo" y protegiéndote legalmente con reportes inalterables de entradas y salidas.</p>

                    <h3>El Reglamento Interior de Trabajo (RIT)</h3>
                    <p>No basta con comprar el reloj; su uso y las sanciones deben estar fundamentadas en tu <strong>Reglamento Interior de Trabajo (RIT)</strong>. La comisión mixta (conformada por representantes del patrón y de los trabajadores) debe formular este reglamento, estipulando claramente:</p>
                    <ul>
                        <li>Los horarios de entrada, salida y tiempos de comida o descansos.</li>
                        <li>Los minutos de tolerancia y las sanciones aplicables por retardos, faltas injustificadas o abandono de labores.</li>
                    </ul>

                    <h3>Puntualidad, Productividad y Legalidad</h3>
                    <p>Al contar con un RIT debidamente depositado ante el Centro Federal de Conciliación y Registro Laboral, respaldado por la tecnología precisa de un reloj checador, adquieres certidumbre legal para la aplicación de descuentos por retardos, levantamiento de actas administrativas o incluso rescisiones laborales sin riesgo ante un tribunal.</p>

                    <p>Implementar soluciones biométricas fomenta una cultura de puntualidad y transparencia organizativa. En <strong>Asistencia Vircom</strong>, te instalamos los equipos de control de asistencia más avanzados. ¡Prepara a tu empresa hoy para el futuro legal!</p>
                ',
                'categoria' => 'Consultoría',
                'status' => 'published',
                'publicado_at' => Carbon::now(),
                'imagen_portada' => '/images/blog/reloj-checador.png',
            ],
        ];

        foreach ($posts as $post) {
            BlogPost::updateOrCreate(
                ['titulo' => $post['titulo']], // Evitar duplicados si se corre varias veces
                $post
            );
        }
    }
}
