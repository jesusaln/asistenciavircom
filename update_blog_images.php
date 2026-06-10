<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$updates = [
    'recibo-de-luz-muy-alto-5-errores-que-disparan-tu-consumo-en-sonora' => 'images/blog/recibo-luz-alto.png',
    'modo-dry-vs-modo-cool-cual-usar-durante-el-monzon-sonorense' => 'images/blog/modo-dry-vs-cool.png',
    'no-dejes-que-falle-5-senales-de-que-tu-ac-necesita-reparacion-urgente' => 'images/blog/senales-reparacion.png',
    'alergias-y-polvo-aire-puro-en-el-interior-a-pesar-del-desierto' => 'images/blog/alergias-polvo.png',
    'inverter-vs-onoff-realmente-vale-la-pena-la-inversion-en-sonora' => 'images/blog/inverter-vs-onoff.png',
];

foreach ($updates as $slug => $image) {
    DB::table('blog_posts')
        ->where('slug', $slug)
        ->update(['imagen_portada' => $image]);
    echo "Actualizado: $slug -> $image\n";
}
