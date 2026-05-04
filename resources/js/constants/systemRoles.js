/**
 * Alineado con App\Support\SystemRoles (PHP).
 * Mantener los mismos slugs si se añaden alias en BD.
 */
export const SUPER_ADMIN_ROLES = ['super-admin', 'super_admin']

export const APP_ADMIN_ROLES = ['admin', 'administrador']

export const ELEVATED_ROLES = [...SUPER_ADMIN_ROLES, ...APP_ADMIN_ROLES]

export function roleNameIsSuperAdmin(name) {
  return SUPER_ADMIN_ROLES.includes(name)
}

export function roleNameIsElevated(name) {
  return ELEVATED_ROLES.includes(name)
}
