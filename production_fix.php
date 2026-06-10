<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$u = \App\Models\User::where('email', 'alejandro.jimenez@climasdeldesierto.com')->first();
if ($u) {
    $u->assignRole('super-admin');
    $u->assignRole('admin');
    $u->givePermissionTo('manage-all-citas');
    echo "Usuario ID: " . $u->id . " elevado exitosamente en produccion\n";
} else {
    echo "Usuario no encontrado\n";
}
