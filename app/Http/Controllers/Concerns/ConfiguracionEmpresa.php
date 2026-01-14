<?php

namespace App\Http\Controllers\Concerns;

use App\Services\EmpresaConfiguracionService;

trait ConfiguracionEmpresa
{
    /**
     * Obtener configuración de la empresa para documentos
     */
    protected function getConfiguracionEmpresa()
    {
        return EmpresaConfiguracionService::getConfiguracionParaPDF();
    }

    /**
     * Obtener información básica de la empresa
     */
    protected function getInfoEmpresa()
    {
        return EmpresaConfiguracionService::getInfoEmpresa();
    }

    /**
     * Obtener colores del sistema
     */
    protected function getColoresEmpresa()
    {
        return EmpresaConfiguracionService::getColores();
    }

    /**
     * Obtener configuración financiera
     */
    protected function getConfiguracionFinanciera()
    {
        return EmpresaConfiguracionService::getConfiguracionFinanciera();
    }

    /**
     * Obtener pie de página para documentos
     */
    protected function getPiePagina($tipo = 'facturas')
    {
        return EmpresaConfiguracionService::getPiePagina($tipo);
    }

    /**
     * Formatear moneda según configuración de empresa
     */
    protected function formatearMoneda($monto, $incluirSimbolo = true)
    {
        return EmpresaConfiguracionService::formatearMoneda($monto, $incluirSimbolo);
    }

    /**
     * Formatear fecha según configuración de empresa
     */
    protected function formatearFecha($fecha, $formato = null)
    {
        return EmpresaConfiguracionService::formatearFecha($fecha, $formato);
    }

    /**
     * Formatear fecha y hora según configuración de empresa
     */
    protected function formatearFechaHora($fecha, $formatoFecha = null, $formatoHora = null)
    {
        return EmpresaConfiguracionService::formatearFechaHora($fecha, $formatoFecha, $formatoHora);
    }

    /**
     * Compartir configuración con vistas
     */
    protected function compartirConfiguracionConVistas()
    {
        EmpresaConfiguracionService::compartirConVistas();
    }

    /**
     * Verificar si el sistema está en mantenimiento
     */
    protected function sistemaEnMantenimiento()
    {
        return EmpresaConfiguracionService::enMantenimiento();
    }

    /**
     * Obtener mensaje de mantenimiento
     */
    protected function getMensajeMantenimiento()
    {
        return EmpresaConfiguracionService::getMensajeMantenimiento();
    }
}
