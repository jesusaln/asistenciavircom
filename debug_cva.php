<?php
$cva = app(App\Services\CVAService::class);
$res = $cva->getCatalogo(['desc' => 'VORAGO', 'sucursales' => 1]);
if (isset($res['articulos'])) {
    foreach (array_slice($res['articulos'], 0, 15) as $idx => $art) {
        $stock = $cva->getHermosilloStock($art);
        echo $idx . ": " . substr($art['descripcion'], 0, 45) . " | Hmo: " . $stock . PHP_EOL;
    }
} else {
    print_r($res);
}
exit();