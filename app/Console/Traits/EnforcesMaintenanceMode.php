<?php

namespace App\Console\Traits;

use Illuminate\Support\Facades\App;

trait EnforcesMaintenanceMode
{
    /**
     * Verifica si la aplicación está en modo mantenimiento.
     * Si no lo está y no se fuerza, retorna false y muestra error.
     *
     * @param bool $force Si es true, ignora la verificación (ej. opción --force)
     * @return bool True si se puede continuar, False si se debe detener
     */
    protected function checkMaintenanceMode(bool $force = false): bool
    {
        // En ambiente local, permitimos correr sin mantenimiento a menos que sea crítico
        $isLocal = App::environment('local', 'testing');

        if (App::isDownForMaintenance()) {
            return true;
        }

        if ($force) {
            $this->warn('⚠️  Ejecutando en sistema VIVO (No Maintenance Mode) debido a opción --force.');
            return true;
        }

        if ($isLocal) {
            // En local solo advertimos
            // $this->warn('ℹ️  Sistema activo (No Maintenance Mode). Procediendo por ser ambiente local.');
            return true;
        }

        $this->error('🛑 Error: Este comando requiere que el sistema esté en MODO MANTENIMIENTO.');
        $this->line('   Ejecuta "php artisan down" primero para asegurar la integridad de datos.');
        $this->line('   O usa la opción --force si está disponible y estÃ¡s seguro.');

        return false;
    }
}
