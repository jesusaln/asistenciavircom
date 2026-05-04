<?php

namespace App\Support;

use App\Models\User;

/**
 * Nombres de rol alineados en BD (Spatie) y en el front.
 * Incluye alias históricos (p. ej. super_admin, administrador) para instalaciones antiguas.
 */
final class SystemRoles
{
    /** Equivalentes a super administrador: nadie puede eliminar a estos usuarios */
    public const SUPER_ADMIN = ['super-admin', 'super_admin'];

    /** Administradores de aplicación: solo un super-admin puede eliminarlos */
    public const ADMIN = ['admin', 'administrador'];

    /** Roles con bypass amplio en políticas (listar/editar, no borrado especial) */
    public const ELEVATED = [
        'super-admin',
        'super_admin',
        'admin',
        'administrador',
    ];

    public static function userIsSuperAdmin(User $user): bool
    {
        return $user->hasAnyRole(self::SUPER_ADMIN);
    }

    public static function userIsAppAdmin(User $user): bool
    {
        return $user->hasAnyRole(self::ADMIN);
    }

    public static function roleNameIsSuperAdmin(string $name): bool
    {
        return in_array($name, self::SUPER_ADMIN, true);
    }

    public static function roleNameIsAppAdmin(string $name): bool
    {
        return in_array($name, self::ADMIN, true);
    }
}
