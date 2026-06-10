<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;
use App\Models\EmpresaConfiguracion;
use App\Models\Credencial;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

function checkModel($modelClass, $fields) {
    echo "--- Checking $modelClass ---\n";
    $models = $modelClass::all();
    foreach ($models as $model) {
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
            }
        }
        if ($updated) $model->save();
    }
}

checkModel(Empresa::class, ['whatsapp_access_token', 'whatsapp_app_secret']);
checkModel(EmpresaConfiguracion::class, ['fiel_password', 'csd_password', 'gemini_api_key', 'groq_api_key', 'smtp_password']);
checkModel(Credencial::class, ['password']);

echo "\nDone.\n";
