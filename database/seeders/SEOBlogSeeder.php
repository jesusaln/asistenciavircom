<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BlogPost;
use App\Models\EmpresaConfiguracion;

class SEOBlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $empresa = EmpresaConfiguracion::first();
        if (!$empresa)
            return;

        // Limpiar para evitar duplicados en cada despliegue (usamos forceDelete por SoftDeletes)
        BlogPost::withTrashed()->whereIn('slug', [
            'mejor-minisplit-calor-sonora',
            'mantenimiento-preventivo-mirage',
            'preparar-aire-verano-sonora-45-grados'
        ])->forceDelete();

        BlogPost::create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Cómo elegir el mejor minisplit para el calor extremo de Sonora',
            'slug' => 'mejor-minisplit-calor-sonora',
            'resumen' => '¿Buscas mantenerte fresco este verano? Descubre qué capacidad de minisplit necesitas según el clima desértico de Sonora y ahorra energía.',
            'contenido' => '
                <p>En Sonora, el verano no es solo una estación, es un desafío. Con temperaturas que superan los 45°C, elegir el aire acondicionado correcto no es un lujo, es una necesidad de supervivencia y confort.</p>
                
                <h2>1. La importancia del Tonelaje (Capacidad)</h2>
                <p>El error más común es comprar un equipo pequeño para ahorrar. En Sonora, un equipo subdimensionado trabajará sin descanso, gastará más luz y se dañará rápido. Aquí la regla general:</p>
                <ul>
                    <li><strong>1 Tonelada:</strong> Para habitaciones de hasta 12-16 m².</li>
                    <li><strong>1.5 Toneladas:</strong> Para espacios de 16-24 m².</li>
                    <li><strong>2 Toneladas:</strong> Para salas o áreas de hasta 32 m².</li>
                </ul>

                <h2>2. Tecnología Inverter: ¿Vale la pena?</h2>
                <p>La respuesta corta es <strong>SÍ</strong>. Mientras que un minisplit tradicional se apaga y enciende gastando picos de energía, el Inverter regula su velocidad. En climas como Hermosillo u Obregón, donde el aire está prendido 20 horas al día, el ahorro en el recibo de CFE puede ser de hasta un 60%.</p>

                <h2>3. El mantenimiento: El secreto del rendimiento</h2>
                <p>El polvo del desierto es el enemigo #1 de tu minisplit. Un filtro sucio hace que el equipo se fuerce. Recomendamos una limpieza de filtros cada quincena y un mantenimiento profesional preventivo antes de que inicie la temporada de calor.</p>

                <h2>Conclusión</h2>
                <p>En Climas del Desierto somos expertos en equipos Mirage y sistemas de alta eficiencia diseñados para el desierto. No dejes tu confort al azar este verano.</p>
            ',
            'imagen_portada' => '/images/blog/mejor-minisplit.png',
            'categoria' => 'Guías de Compra',
            'status' => 'published',
            'publicado_at' => now()->subDays(2),
            'visitas' => 0,
            'meta_titulo' => 'Mejor Minisplit para Sonora 2026 | Guía de Climas del Desierto',
            'meta_descripcion' => 'Descubre cómo elegir el minisplit ideal para el calor de Sonora. Analizamos Mirage, tecnología Inverter y ahorro de energía en CFE.',
        ]);

        BlogPost::create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Mantenimiento preventivo: Alarga la vida de tu equipo Mirage',
            'slug' => 'mantenimiento-preventivo-mirage',
            'resumen' => 'No esperes a que tu aire deje de enfriar. Aprende por qué el mantenimiento preventivo es la mejor inversión para tu bolsillo.',
            'contenido' => '
                <p>Un equipo de aire acondicionado es como un auto: necesita servicios regulares para no fallar en el momento menos oportuno.</p>
                
                <h2>Ahorro de Energía</h2>
                <p>Un minisplit con serpentines sucios consume hasta un 30% más de energía eléctrica para enfriar lo mismo. Al mantenerlo limpio, aseguras que el intercambio de calor sea eficiente.</p>

                <h2>Calidad del Aire</h2>
                <p>Los filtros atrapan polvo, polen y bacterias. Si no se limpian, estarás respirando micropartículas que pueden causar alergias o enfermedades respiratorias.</p>

                <h2>¿Cuándo llamar a un técnico?</h2>
                <p>Si notas ruidos extraños, goteos de agua en la pared o que el aire ya no sale tan frío como antes, es momento de un servicio profesional.</p>
            ',
            'imagen_portada' => '/images/blog/mantenimiento-mirage.png',
            'categoria' => 'Mantenimiento',
            'status' => 'published',
            'publicado_at' => now()->subDays(1),
            'visitas' => 0,
            'meta_titulo' => 'Mantenimiento de Aires Acondicionados en Sonora | Climas del Desierto',
            'meta_descripcion' => 'Aprende por qué el mantenimiento preventivo de tu minisplit Mirage es clave para ahorrar energía y evitar reparaciones costosas.',
        ]);

        BlogPost::create([
            'empresa_id' => $empresa->id,
            'titulo' => '¿Cómo preparar tu aire para el verano de 45° en Sonora?',
            'slug' => 'preparar-aire-verano-sonora-45-grados',
            'resumen' => 'El verano en Sonora no perdona. Te decimos cómo poner a punto tu equipo para sobrevivir al calor extremo y ahorrar en tu recibo de CFE.',
            'contenido' => '
                <p>Si vives en Hermosillo, Ciudad Obregón o San Luis Río Colorado, sabes que el termómetro superando los 45°C no es noticia, es el pan de cada día durante mayo y junio. En Climas del Desierto, queremos que tu única preocupación sea disfrutar del frío dentro de casa.</p>
                
                <h2>1. La Limpieza de Serpentines es Vital</h2>
                <p>Con las tormentas de arena y el polvo constante del desierto, el serpentín exterior se "tapa". Si esto ocurre, el equipo no puede liberar el calor hacia afuera y terminará por quemar el compresor. Un mantenimiento profesional antes de mayo es tu mejor seguro.</p>

                <h2>2. Comparativa: Inverter vs Convencional (El bolsillo manda)</h2>
                <p>Muchos clientes nos preguntan si realmente vale la pena cambiar su equipo viejo. Aquí los datos reales para Sonora:</p>
                <ul>
                    <li><strong>Minisplit Convencional (On/Off):</strong> Consume picos de hasta 15 Amperes cada vez que enciende. En un ciclo de 8 horas, tu recibo de CFE puede llegar hasta los $4,500 MXN mensuales en tarifa 1F.</li>
                    <li><strong>Minisplit Inverter Mirage:</strong> Una vez que alcanza la temperatura, consume apenas 2 o 3 Amperes. El ahorro real es del 50% al 65%. ¡El equipo se paga solo en dos veranos!</li>
                </ul>

                <h2>3. El Secreto de los 24 Grados</h2>
                <p>Poner el aire en 16°C no hará que enfríe más rápido, solo hará que el compresor nunca descanse. Mantenerlo en <strong>22-24°C</strong> con la función "Sleep" durante la noche es el punto óptimo de confort y ahorro de energía.</p>

                <h2>4. Aislamiento Térmico</h2>
                <p>De poco sirve el mejor equipo Mirage si el aire frío se escapa por debajo de las puertas o si tienes ventanales sin cortinas térmicas. Bloquear el sol directo puede reducir la carga térmica de tu habitación en un 20%.</p>

                <p>En <strong>Climas del Desierto</strong>, somos expertos en proteger tu hogar contra el calor extremo. Si tu equipo ya tiene más de 5 años y utiliza gas R22, es momento de actualizarte a un sistema con gas ecológico R410A y tecnología Inverter.</p>
            ',
            'imagen_portada' => '/images/blog/verano-sonora.png',
            'categoria' => 'Tips de Ahorro',
            'status' => 'published',
            'publicado_at' => now(),
            'visitas' => 0,
            'meta_titulo' => 'Sobrevivir al Verano de 45° en Sonora | Consejos de Climatización',
            'meta_descripcion' => 'Guía completa para preparar tu minisplit ante el calor extremo de Sonora. Comparativa Inverter vs Convencional y tips para ahorrar en CFE.',
        ]);
    }
}
