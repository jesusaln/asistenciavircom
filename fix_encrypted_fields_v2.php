<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;
use App\Models\EmpresaConfiguracion;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

echo "--- Checking Empresa ---\n";
$empresas = Empresa::all();
foreach ($empresas as $empresa) {
    echo "Empresa: {$empresa->nombre_razon_social} (ID: {$empresa->id})\n";
    $fields = ['whatsapp_access_token', 'whatsapp_app_secret'];
    $updated = false;
    foreach ($fields as $field) {
        $value = $empresa->getRawOriginal($field);
        if (empty($value)) continue;
        try {
            Crypt::decrypt($value);
        } catch (DecryptException $e) {
            echo "  CRITICAL: $field has INVALID MAC. Clearing.\n";
            $empresa->$field = null;
            $updated = true;
        }
    }
    if ($updated) $empresa->save();
}

echo "\n--- Checking EmpresaConfiguracion ---\n";
$configs = EmpresaConfiguracion::all();
foreach ($configs as $config) {
    echo "Config ID: {$config->id} (Empresa: {$config->empresa_id})\n";
    $fields = ['fiel_password', 'csd_password', 'gemini_api_key', 'groq_api_key', 'smtp_password'];
    $updated = false;
    foreach ($fields as $field) {
        $value = $config->getRawOriginal($field);
        if (empty($value)) continue;
        try {
            Crypt::decrypt($value);
        } catch (DecryptException $e) {
            echo "  CRITICAL: $field has INVALID MAC. Clearing.\n";
            $config->$field = null;
            $updated = true;
        }
    }
    if ($updated) $config->save();
}

echo "\nDone.\n";
