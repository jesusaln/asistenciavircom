<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$user = $_ENV['CVA_USER'];
$pass = $_ENV['CVA_PASS'];

$login = Illuminate\Support\Facades\Http::post('https://apicvaservices.grupocva.com/api/v2/user/login', [
    'user' => $user,
    'password' => $pass
]);

$token = $login->json()['token'];
echo "Token: $token\n";

$params = [
    'desc' => 'VORAGO',
    'sucursales' => 1,
    'exist' => 3
];

$response = Illuminate\Support\Facades\Http::withToken($token)
    ->get('https://apicvaservices.grupocva.com/api/v2/catalogo_clientes/lista_precios', $params);

$data = $response->json();
echo "Total Articulos: " . count($data['articulos'] ?? []) . "\n";

foreach ($data['articulos'] as $idx => $art) {
    $local = 0;
    foreach ($art['disponibilidad_sucursales'] ?? [] as $suc) {
        if (strpos(strtoupper($suc['nombre']), 'HERMOSILLO') !== false) {
            $local = $suc['disponible'];
        }
    }
    echo "$idx: " . substr($art['descripcion'], 0, 40) . " | Local: $local | CEDIS: " . ($art['disponibleCD'] ?? 0) . "\n";
    if ($idx >= 10)
        break;
}
