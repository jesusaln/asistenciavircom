<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Intentar resolver el ID de la empresa
$empresaId = DB::table('empresas')->value('id');

if (!$empresaId) {
    die("Error: No se encontró ninguna empresa en la base de datos.\n");
}

echo "Empresa ID detectada: $empresaId\n";

// Actualizar todos los posts que no tengan empresa_id
$affected = DB::table('blog_posts')
    ->whereNull('empresa_id')
    ->update(['empresa_id' => $empresaId]);

echo "Posts vinculados a la empresa: $affected\n";

// Asegurar que los estados sean 'published' y las fechas sean correctas
DB::table('blog_posts')
    ->whereIn('slug', [
        'recibo-de-luz-muy-alto-5-errores-que-disparan-tu-consumo-en-sonora',
        'modo-dry-vs-modo-cool-cual-usar-durante-el-monzon-sonorense',
        'no-dejes-que-falle-5-senales-de-que-tu-ac-necesita-reparacion-urgente',
        'alergias-y-polvo-aire-puro-en-el-interior-a-pesar-del-desierto',
        'inverter-vs-onoff-realmente-vale-la-pena-la-inversion-en-sonora'
    ])
    ->update([
        'status' => 'published',
        'publicado_at' => now(),
    ]);

echo "Estados y fechas sincronizados.\n";
