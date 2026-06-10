<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

$modelsToFix = [
    'App\Models\Empresa' => ['whatsapp_access_token', 'whatsapp_app_secret'],
    'App\Models\EmpresaConfiguracion' => ['fiel_password', 'csd_password', 'gemini_api_key', 'groq_api_key', 'smtp_password'],
    'App\Models\Credencial' => ['password'],
    'App\Models\Renta' => ['condiciones_especiales', 'observaciones'],
];

foreach ($modelsToFix as $modelClass => $fields) {
    echo "--- Checking $modelClass ---\n";
    if (!class_exists($modelClass)) {
        echo "  Class NOT FOUND: $modelClass\n";
        continue;
    }
    
    // Use an instance to get all records if static call fails in some versions
    $all = $modelClass::all();
    
    foreach ($all as $model) {
        $updated = false;
        foreach ($fields as $field) {
            $value = $model->getRawOriginal($field);
            if (empty($value)) continue;
            try {
                Crypt::decrypt($value);
            } catch (DecryptException $e) {
                echo "  ID {$model->id}: $field INVALID. Clearing.\n";
                $model->$field = null;
                $updated = true;
            } catch (\Exception $e) {
                 echo "  ID {$model->id}: $field error: " . $e->getMessage() . "\n";
            }
        }
        if ($updated) $model->save();
    }
}

echo "\nDone.\n";
