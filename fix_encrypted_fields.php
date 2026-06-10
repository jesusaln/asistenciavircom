<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

$empresas = Empresa::all();

foreach ($empresas as $empresa) {
    echo "Checking Empresa: {$empresa->nombre_razon_social} (ID: {$empresa->id})\n";
    
    $fields = ['whatsapp_access_token', 'whatsapp_app_secret'];
    $updated = false;

    foreach ($fields as $field) {
        $value = $empresa->getRawOriginal($field);
        if (empty($value)) continue;

        try {
            // Attempt to decrypt
            $decrypted = Crypt::decrypt($value);
            echo "  Field $field is OK.\n";
        } catch (DecryptException $e) {
            echo "  CRITICAL: Field $field has INVALID MAC. Clearing it to prevent crashes.\n";
            // We set it to null because we can't recover it anyway without the old key
            $empresa->$field = null;
            $updated = true;
        }
    }

    if ($updated) {
        $empresa->save();
        echo "  Empresa updated (corrupted fields cleared).\n";
    }
}

echo "Done.\n";
