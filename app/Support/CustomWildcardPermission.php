<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\WildcardPermission;

class CustomWildcardPermission extends WildcardPermission
{
    /**
     * Construir el índice de permisos del usuario/rol.
     * Añade indexación bidireccional para soportar tanto 'acción módulo' (antiguo) como 'módulo.acción' (moderno).
     */
    public function getIndex(): array
    {
        $index = [];

        foreach ($this->record->getAllPermissions() as $permission) {
            // Indexar nombre original (ej. 'clientes.*', 'view clientes' o '*')
            $index[$permission->guard_name] = $this->buildIndex(
                $index[$permission->guard_name] ?? [],
                explode(static::PART_DELIMITER, $permission->name),
                $permission->name,
            );

            // Si tiene espacio (ej. 'view clientes'), también indexar como 'clientes.view'
            if (str_contains($permission->name, ' ')) {
                $parts = explode(' ', $permission->name, 2);
                if (count($parts) === 2) {
                    $normalized = $parts[1] . '.' . $parts[0];
                    $index[$permission->guard_name] = $this->buildIndex(
                        $index[$permission->guard_name] ?? [],
                        explode(static::PART_DELIMITER, $normalized),
                        $normalized,
                    );
                }
            }
        }

        return $index;
    }

    /**
     * Verificar si el índice implica un permiso solicitado.
     */
    public function implies(string $permission, string $guardName, array $index): bool
    {
        // 1. Probar el permiso tal cual (ej. 'clientes.view', 'clientes.*' o '*')
        if (parent::implies($permission, $guardName, $index)) {
            return true;
        }

        // 2. Si se verifica con formato espaciado (ej. 'view clientes'), normalizar a 'clientes.view'
        if (str_contains($permission, ' ')) {
            $parts = explode(' ', $permission, 2);
            if (count($parts) === 2) {
                $normalized = $parts[1] . '.' . $parts[0];
                if (parent::implies($normalized, $guardName, $index)) {
                    return true;
                }
            }
        }

        return false;
    }
}
