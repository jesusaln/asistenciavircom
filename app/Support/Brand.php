<?php

namespace App\Support;

use App\Models\Empresa;
use App\Models\EmpresaConfiguracion;

/**
 * Resolver central de marca/branding para evitar contaminacion cruzada
 * entre proyectos multi-tenant (Climas del Desierto vs Asistencia Vircom).
 *
 * Prioridad de resolucion del nombre:
 *  1. Empresa explicita pasada por parametro
 *  2. EmpresaConfiguracion activa del tenant actual
 *  3. config('app.name') (seteado por SwitchDatabaseConnection o .env)
 *  4. config('app.fallback_brand') (env BRAND_FALLBACK, default "la empresa")
 *
 * NUNCA hardcodear "Climas del Desierto" ni "Asistencia Vircom" en codigo.
 * Usar siempre Brand::name() o Brand::botName().
 */
class Brand
{
    public static function name(?Empresa $empresa = null): string
    {
        if ($empresa && !empty($empresa->nombre_razon_social)) {
            return $empresa->nombre_razon_social;
        }

        try {
            $empresaId = EmpresaResolver::resolveId();
            if ($empresaId) {
                $config = EmpresaConfiguracion::getConfig($empresaId);
                if ($config && !empty($config->nombre_empresa)) {
                    return $config->nombre_empresa;
                }
            }
        } catch (\Throwable $e) {
            // Silenciar: caer a config('app.name')
        }

        $appName = (string) config('app.name', '');
        if ($appName !== '' && $appName !== 'Sistema') {
            return $appName;
        }

        return (string) config('app.fallback_brand', 'la empresa');
    }

    public static function shortName(): string
    {
        $full = self::name();
        $parts = preg_split('/\s+(S\.?A\.?\s*(de\s*C\.?V\.?)?|S\.? de\s*R\.?L\.?)/i', $full);

        return trim($parts[0] ?: $full);
    }

    public static function tagline(): string
    {
        $appTagline = (string) config('app.brand_tagline', '');

        if ($appTagline !== '') {
            return $appTagline;
        }

        try {
            $empresaId = EmpresaResolver::resolveId();
            if ($empresaId) {
                $config = EmpresaConfiguracion::getConfig($empresaId);
                if ($config && !empty($config->slogan)) {
                    return $config->slogan;
                }
            }
        } catch (\Throwable $e) {
        }

        return '';
    }

    public static function botName(): string
    {
        $explicit = (string) config('app.brand_bot_name', '');
        if ($explicit !== '') {
            return $explicit;
        }

        $appName = (string) config('app.name', '');
        if ($appName !== '' && $appName !== 'Sistema') {
            $short = self::shortName();

            return $short . ' Bot';
        }

        return 'Asistente Virtual';
    }

    public static function emoji(): string
    {
        return (string) config('app.brand_emoji', '🔧');
    }

    public static function domain(): string
    {
        try {
            $empresaId = EmpresaResolver::resolveId();
            if ($empresaId) {
                $config = EmpresaConfiguracion::getConfig($empresaId);
                if ($config && !empty($config->dominio_principal)) {
                    return $config->dominio_principal;
                }
            }
        } catch (\Throwable $e) {
        }

        $appUrl = (string) config('app.url', '');
        if ($appUrl !== '') {
            $host = parse_url($appUrl, PHP_URL_HOST);

            return $host ?: $appUrl;
        }

        return '';
    }
}
