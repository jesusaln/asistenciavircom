<?php

namespace App\Services;

use App\Models\EmpresaConfiguracion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class EmpresaConfiguracionService
{
    /**
     * Obtener configuración de la empresa
     */
    public static function getConfiguracion()
    {
        return EmpresaConfiguracion::getConfig();
    }

    /**
     * Obtener información básica de la empresa
     */
    public static function getInfoEmpresa()
    {
        return EmpresaConfiguracion::getInfoEmpresa();
    }

    /**
     * Obtener colores del sistema
     */
    public static function getColores()
    {
        return EmpresaConfiguracion::getColores();
    }

    /**
     * Obtener configuración financiera
     */
    public static function getConfiguracionFinanciera()
    {
        return EmpresaConfiguracion::getConfiguracionFinanciera();
    }

    /**
     * Obtener pie de página para documentos
     */
    public static function getPiePagina($tipo = 'facturas')
    {
        return EmpresaConfiguracion::getPiePagina($tipo);
    }

    /**
     * Obtener URL del logo
     */
    public static function getLogoUrl()
    {
        $config = self::getConfiguracion();
        return $config->logo_url;
    }

    /**
     * Obtener URL del logo para reportes
     */
    public static function getLogoReportesUrl()
    {
        $config = self::getConfiguracion();
        return $config->logo_reportes_url;
    }

    /**
     * Obtener nombre de la empresa
     */
    public static function getNombreEmpresa()
    {
        $config = self::getConfiguracion();
        return $config->nombre_empresa;
    }

    /**
     * Obtener RFC de la empresa
     */
    public static function getRfc()
    {
        $config = self::getConfiguracion();
        return $config->rfc;
    }

    /**
     * Obtener razón social de la empresa
     */
    public static function getRazonSocial()
    {
        $config = self::getConfiguracion();
        return $config->razon_social;
    }

    /**
     * Obtener dirección completa de la empresa
     */
    public static function getDireccionCompleta()
    {
        $config = self::getConfiguracion();
        return $config->direccion_completa;
    }

    /**
     * Obtener teléfono de la empresa
     */
    public static function getTelefono()
    {
        $config = self::getConfiguracion();
        return $config->telefono;
    }

    /**
     * Obtener email de la empresa
     */
    public static function getEmail()
    {
        $config = self::getConfiguracion();
        return $config->email;
    }

    /**
     * Obtener sitio web de la empresa
     */
    public static function getSitioWeb()
    {
        $config = self::getConfiguracion();
        return $config->sitio_web;
    }

    /**
     * Obtener porcentaje de IVA
     */
    public static function getIvaPorcentaje()
    {
        $config = self::getConfiguracion();
        return $config->iva_porcentaje;
    }

    /**
     * Obtener porcentaje de ISR (retención para personas morales)
     */
    public static function getIsrPorcentaje()
    {
        $config = self::getConfiguracion();
        return $config->isr_porcentaje ?? 1.25;
    }

    public static function isIsrEnabled()
    {
        $config = self::getConfiguracion();
        return $config->enable_isr ?? false;
    }

    public static function isRetencionIvaEnabled()
    {
        $config = self::getConfiguracion();
        return $config->enable_retencion_iva ?? false;
    }

    public static function isRetencionIsrEnabled()
    {
        $config = self::getConfiguracion();
        return $config->enable_retencion_isr ?? false;
    }

    public static function getRetencionIvaDefault()
    {
        $config = self::getConfiguracion();
        return $config->retencion_iva ?? 0;
    }

    public static function getRetencionIsrDefault()
    {
        $config = self::getConfiguracion();
        return $config->retencion_isr ?? 0;
    }

    /**
     * Obtener moneda del sistema
     */
    public static function getMoneda()
    {
        $config = self::getConfiguracion();
        return $config->moneda;
    }

    /**
     * Verificar si el sistema está en mantenimiento
     */
    public static function enMantenimiento()
    {
        return EmpresaConfiguracion::enMantenimiento();
    }

    /**
     * Obtener mensaje de mantenimiento
     */
    public static function getMensajeMantenimiento()
    {
        return EmpresaConfiguracion::mensajeMantenimiento();
    }

    /**
     * Compartir configuración con todas las vistas
     */
    public static function compartirConVistas()
    {
        $config = self::getConfiguracion();

        View::share([
            'empresa_config' => $config,
            'empresa_nombre' => $config->nombre_empresa,
            'empresa_logo' => $config->logo_url,
            'empresa_colores' => [
                'principal' => $config->color_principal,
                'secundario' => $config->color_secundario,
            ],
        ]);
    }

    /**
     * Obtener configuración para documentos PDF
     */
    public static function getConfiguracionParaPDF()
    {
        $config = self::getConfiguracion();

        return [
            'empresa' => [
                'nombre' => $config->nombre_empresa,
                'rfc' => $config->rfc,
                'razon_social' => $config->razon_social,
                'direccion' => $config->direccion_completa,
                'telefono' => $config->telefono,
                'email' => $config->email,
                'sitio_web' => $config->sitio_web,
            ],
            'logo_path' => $config->logo_reportes ?: $config->logo_path,
            'colores' => [
                'principal' => $config->color_principal,
                'secundario' => $config->color_secundario,
            ],
            'financiera' => [
                'iva_porcentaje' => $config->iva_porcentaje,
                'moneda' => $config->moneda,
            ],
        ];
    }

    /**
     * Formatear moneda según configuración
     */
    public static function formatearMoneda($monto, $incluirSimbolo = true)
    {
        $config = self::getConfiguracion();
        $formato = $config->formato_numeros ?: 'es-ES';
        $moneda = $config->moneda ?: 'MXN';

        $formatter = new \NumberFormatter($formato, \NumberFormatter::CURRENCY);

        if (!$incluirSimbolo) {
            $formatter = new \NumberFormatter($formato, \NumberFormatter::DECIMAL);
            return $formatter->format($monto);
        }

        return $formatter->formatCurrency($monto, $moneda);
    }

    /**
     * Convertir un número a su representación escrita en español (Ideal para facturas)
     */
    public static function convertirALetras($numero, $moneda = 'MXN')
    {
        $numero = number_format($numero, 2, '.', '');
        $partes = explode('.', $numero);
        $entero = (int) $partes[0];
        $decimal = $partes[1];

        $fmt = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
        $textoEntero = strtoupper($fmt->format($entero));

        $sufijo = ($moneda === 'MXN') ? 'PESOS' : 'DÓLARES';
        if ($entero == 1) {
            $sufijo = ($moneda === 'MXN') ? 'PESO' : 'DÓLAR';
        }

        return "({$textoEntero} {$sufijo} {$decimal}/100 " . ($moneda === 'MXN' ? 'M.N.' : 'USD') . ")";
    }

    /**
     * Formatear fecha según configuración
     */
    public static function formatearFecha($fecha, $formato = null)
    {
        if (!$fecha)
            return '';

        $config = self::getConfiguracion();
        $formato = $formato ?: $config->formato_fecha ?: 'd/m/Y';

        try {
            $date = $fecha instanceof \Carbon\Carbon ? $fecha : \Carbon\Carbon::parse($fecha);
            return $date->format($formato);
        } catch (\Exception $e) {
            return $fecha;
        }
    }

    /**
     * Formatear fecha y hora según configuración
     */
    public static function formatearFechaHora($fecha, $formatoFecha = null, $formatoHora = null)
    {
        if (!$fecha)
            return '';

        $config = self::getConfiguracion();
        $formatoFecha = $formatoFecha ?: $config->formato_fecha ?: 'd/m/Y';
        $formatoHora = $formatoHora ?: $config->formato_hora ?: 'H:i:s';

        try {
            $date = $fecha instanceof \Carbon\Carbon ? $fecha : \Carbon\Carbon::parse($fecha);
            return $date->format($formatoFecha . ' ' . $formatoHora);
        } catch (\Exception $e) {
            return $fecha;
        }
    }

    /**
     * Obtener estilos CSS para colores del sistema
     */
    public static function getEstilosCSS()
    {
        $colores = self::getColores();

        return [
            'color_principal' => $colores['principal'],
            'color_secundario' => $colores['secundario'],
        ];
    }

    /**
     * Verificar permisos de administrador para configuración
     */
    public static function puedeEditarConfiguracion()
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * Crear configuración por defecto si no existe
     */
    public static function inicializarConfiguracionPorDefecto()
    {
        if (!EmpresaConfiguracion::exists()) {
            EmpresaConfiguracion::create([
                'nombre_empresa' => 'CDD - Sistema de Gestión',
                'rfc' => 'XAXX010101000',
                'razon_social' => 'Empresa de Ejemplo S.A. de C.V.',
                'calle' => 'Calle de Ejemplo',
                'numero_exterior' => '123',
                'numero_interior' => 'A',
                'telefono' => '555-000-0000',
                'email' => 'contacto@empresa.com',
                'sitio_web' => 'https://empresa.com',
                'codigo_postal' => '00000',
                'colonia' => 'Colonia de Ejemplo',
                'ciudad' => 'Ciudad de México',
                'estado' => 'CDMX',
                'pais' => 'México',
                'color_principal' => '#3B82F6',
                'color_secundario' => '#1E40AF',
                'iva_porcentaje' => 16.00,
                'moneda' => 'MXN',
                'formato_numeros' => 'es-ES',
                'formato_fecha' => 'd/m/Y',
                'formato_hora' => 'H:i:s',
                'registro_usuarios' => true,
                'notificaciones_email' => true,
                'backup_automatico' => true,
                'frecuencia_backup' => 7,
                'retencion_backups' => 30,
                'intentos_login' => 5,
                'tiempo_bloqueo' => 15,
            ]);
        }
    }
}
