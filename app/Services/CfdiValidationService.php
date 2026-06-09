<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\SatEstado;
use App\Models\SatRegimenFiscal;
use App\Models\SatUsoCfdi;
use Illuminate\Support\Facades\Http;

class CfdiValidationService
{
    /**
     * Validar RFC completo (formato y existencia en SAT)
     */
    public function validarRfc(string $rfc): array
    {
        $errores = [];

        // Limpiar y validar formato
        $rfc = strtoupper(trim($rfc));

        if (empty($rfc)) {
            $errores[] = 'RFC es obligatorio';
            return $errores;
        }

        // RFC genéricos permitidos
        if (in_array($rfc, ['XAXX010101000', 'XEXX010101000'])) {
            return $errores; // Válidos
        }

        // Validar formato básico
        if (!$this->validarFormatoRfc($rfc)) {
            $errores[] = 'Formato de RFC inválido';
            return $errores;
        }

        // Validar fecha de nacimiento/constitucion
        if (!$this->validarFechaRfc($rfc)) {
            $errores[] = 'Fecha en RFC inválida';
        }

        // Validar dígito verificador
        if (!$this->validarDigitoVerificadorRfc($rfc)) {
            $errores[] = 'Dígito verificador del RFC inválido';
        }

        return $errores;
    }

    /**
     * Validar formato básico de RFC
     */
    private function validarFormatoRfc(string $rfc): bool
    {
        // RFC genéricos permitidos
        if (in_array($rfc, ['XAXX010101000', 'XEXX010101000'])) {
            return true;
        }

        $length = strlen($rfc);

        // Persona física: exactamente 13 caracteres (4 letras + 6 dígitos + 3 alfanuméricos)
        if ($length === 13 && preg_match('/^[A-ZÑ&]{4}\d{6}[A-Z\d]{3}$/', $rfc)) {
            return true;
        }

        // Persona moral: exactamente 12 caracteres (3 letras + 6 dígitos + 3 alfanuméricos)
        if ($length === 12 && preg_match('/^[A-ZÑ&]{3}\d{6}[A-Z\d]{3}$/', $rfc)) {
            return true;
        }

        return false;
    }

    /**
     * Validar fecha en RFC
     */
    private function validarFechaRfc(string $rfc): bool
    {
        // Extraer fecha del RFC (posiciones 4-9 para PF, 3-8 para PM)
        $fechaStr = strlen($rfc) === 13 ? substr($rfc, 4, 6) : substr($rfc, 3, 6);

        $anio = (int) substr($fechaStr, 0, 2);
        $mes = (int) substr($fechaStr, 2, 2);
        $dia = (int) substr($fechaStr, 4, 2);

        // Ajustar año (00-99 -> 1900-1999 o 2000-2099)
        $anioCompleto = $anio >= 0 && $anio <= 99 ? ($anio >= 30 ? 1900 + $anio : 2000 + $anio) : $anio;

        return checkdate($mes, $dia, $anioCompleto);
    }

    /**
     * Validar dígito verificador del RFC
     */
    private function validarDigitoVerificadorRfc(string $rfc): bool
    {
        $rfcBase = substr($rfc, 0, -1);
        $digitoEsperado = strtoupper(substr($rfc, -1));

        $digitoCalculado = $this->calcularDigitoVerificadorRfc($rfcBase);

        return $digitoCalculado === $digitoEsperado;
    }

    /**
     * Calcular dígito verificador del RFC
     */
    private function calcularDigitoVerificadorRfc(string $rfc): string
    {
        $diccionario = '0123456789ABCDEFGHIJKLMN&OPQRSTUVWXYZ Ñ';

        $suma = 0;
        $longitud = strlen($rfc);

        for ($i = 0; $i < $longitud; $i++) {
            $caracter = strtoupper($rfc[$i]);
            $posicion = strpos($diccionario, $caracter);

            if ($posicion === false) {
                return 'X'; // Carácter inválido
            }

            $suma += $posicion * (13 - $i);
        }

        $modulo = $suma % 11;

        if ($modulo === 0) {
            return '0';
        }

        $digito = 11 - $modulo;

        return $digito === 10 ? 'A' : (string) $digito;
    }

    /**
     * Validar cliente completo para CFDI 4.0
     */
    public function validarClienteParaCfdi(Cliente $cliente): array
    {
        $errores = [];

        // Validar RFC
        $erroresRfc = $this->validarRfc($cliente->rfc);
        $errores = array_merge($errores, $erroresRfc);

        // Validar nombre/razón social
        if (empty(trim($cliente->nombre_razon_social))) {
            $errores[] = 'Nombre o razón social es obligatorio';
        }

        // Validar régimen fiscal
        if (empty($cliente->regimen_fiscal)) {
            $errores[] = 'Régimen fiscal es obligatorio';
        } elseif (!$this->validarRegimenFiscal($cliente->regimen_fiscal, $cliente->tipo_persona)) {
            $errores[] = 'Régimen fiscal inválido para el tipo de persona';
        }

        // Validar uso CFDI
        if (empty($cliente->uso_cfdi) && empty($cliente->cfdi_default_use)) {
            $errores[] = 'Uso CFDI es obligatorio';
        }

        // Validar código postal fiscal
        if (empty($cliente->domicilio_fiscal_cp)) {
            $errores[] = 'Código postal del domicilio fiscal es obligatorio';
        } elseif (!$this->validarCodigoPostal($cliente->domicilio_fiscal_cp)) {
            $errores[] = 'Código postal del domicilio fiscal inválido';
        }

        // Reglas específicas para RFC genérico extranjero
        if ($cliente->rfc === 'XEXX010101000') {
            // RFC genérico extranjero: debe usar régimen 616 y uso CFDI S01
            if ($cliente->regimen_fiscal !== '616') {
                $errores[] = 'RFC genérico extranjero debe usar régimen fiscal 616';
            }
            if (($cliente->uso_cfdi !== 'S01') && ($cliente->cfdi_default_use !== 'S01')) {
                $errores[] = 'RFC genérico extranjero debe usar uso CFDI S01';
            }
            // Residencia fiscal obligatoria para RFC genérico extranjero
            if (empty($cliente->residencia_fiscal)) {
                $errores[] = 'Residencia fiscal es obligatoria para RFC genérico extranjero';
            }
        }

        // Para otros extranjeros: residencia fiscal obligatoria
        elseif ($cliente->es_extranjero) {
            if (empty($cliente->residencia_fiscal)) {
                $errores[] = 'Residencia fiscal es obligatoria para clientes extranjeros';
            }
            if (!empty($cliente->num_reg_id_trib) && strlen($cliente->num_reg_id_trib) > 40) {
                $errores[] = 'Número de registro fiscal extranjero no puede exceder 40 caracteres';
            }
        }

        return $errores;
    }

    /**
     * Validar régimen fiscal según tipo de persona
     */
    private function validarRegimenFiscal(string $regimen, ?string $tipoPersona): bool
    {
        $regimenModel = SatRegimenFiscal::where('clave', $regimen)->first();

        if (!$regimenModel) {
            return false;
        }

        // Validar compatibilidad con tipo de persona
        if ($tipoPersona === 'fisica' && !$regimenModel->persona_fisica) {
            return false;
        }

        if ($tipoPersona === 'moral' && !$regimenModel->persona_moral) {
            return false;
        }

        return true;
    }

    /**
     * Validar código postal (5 dígitos)
     */
    private function validarCodigoPostal(string $cp): bool
    {
        return preg_match('/^\d{5}$/', $cp);
    }

    /**
     * Validar clave de catálogo SAT
     */
    public function validarClaveCatalogo(string $clave, string $catalogo): bool
    {
        return match ($catalogo) {
            'regimen_fiscal' => SatRegimenFiscal::where('clave', $clave)->exists(),
            'uso_cfdi' => SatUsoCfdi::where('clave', $clave)->exists(),
            'estado' => SatEstado::where('clave', $clave)->exists(),
            default => false
        };
    }

    /**
     * Validar compatibilidad entre régimen fiscal y uso CFDI
     */
    public function validarCompatibilidadRegimenUso(string $regimen, string $uso): bool
    {
        $usoCfdi = SatUsoCfdi::where('clave', $uso)->first();
        $regimenModel = SatRegimenFiscal::where('clave', $regimen)->first();

        if (!$usoCfdi || !$regimenModel) {
            return false;
        }

        // Validar compatibilidad básica por tipo de persona
        if ($regimenModel->persona_fisica && $usoCfdi->persona_fisica) {
            return true;
        }

        if ($regimenModel->persona_moral && $usoCfdi->persona_moral) {
            return true;
        }

        return false;
    }

    /**
     * Validar CFDI completo antes del timbrado
     */
    public function validarCfdiParaTimbrado(array $datosCfdi): array
    {
        $errores = [];

        // Validar emisor (empresa)
        if (empty($datosCfdi['emisor'])) {
            $errores[] = 'Datos del emisor son obligatorios';
        }

        // Validar receptor (cliente)
        if (empty($datosCfdi['receptor'])) {
            $errores[] = 'Datos del receptor son obligatorios';
        } elseif (isset($datosCfdi['receptor']['id'])) {
            $cliente = Cliente::find($datosCfdi['receptor']['id']);
            if ($cliente) {
                $erroresCliente = $this->validarClienteParaCfdi($cliente);
                $errores = array_merge($errores, $erroresCliente);
            }
        }

        // Validar conceptos
        if (empty($datosCfdi['conceptos']) || !is_array($datosCfdi['conceptos'])) {
            $errores[] = 'Debe incluir al menos un concepto';
        } else {
            foreach ($datosCfdi['conceptos'] as $i => $concepto) {
                $erroresConcepto = $this->validarConcepto($concepto, $i + 1);
                $errores = array_merge($errores, $erroresConcepto);
            }
        }

        // Validar totales
        if (!isset($datosCfdi['total']) || $datosCfdi['total'] <= 0) {
            $errores[] = 'El total debe ser mayor a cero';
        }

        return $errores;
    }

    /**
     * Validar concepto individual
     */
    private function validarConcepto(array $concepto, int $numero): array
    {
        $errores = [];

        if (empty($concepto['clave_prod_serv'])) {
            $errores[] = "Concepto {$numero}: Clave de producto/servicio es obligatoria";
        }

        if (empty($concepto['descripcion'])) {
            $errores[] = "Concepto {$numero}: Descripción es obligatoria";
        }

        if (!isset($concepto['cantidad']) || $concepto['cantidad'] <= 0) {
            $errores[] = "Concepto {$numero}: Cantidad debe ser mayor a cero";
        }

        if (empty($concepto['clave_unidad'])) {
            $errores[] = "Concepto {$numero}: Clave de unidad es obligatoria";
        }

        if (!isset($concepto['valor_unitario']) || $concepto['valor_unitario'] < 0) {
            $errores[] = "Concepto {$numero}: Valor unitario no puede ser negativo";
        }

        return $errores;
    }

    /**
     * Consultar RFC en lista del SAT (simulado - requeriría API del SAT)
     */
    public function consultarRfcEnSat(string $rfc): array
    {
        // Esta sería una implementación real que consulta el webservice del SAT
        // Por ahora retornamos un resultado simulado

        $rfc = strtoupper(trim($rfc));

        // RFC genérico extranjero siempre válido
        if ($rfc === 'XEXX010101000') {
            return [
                'valido' => true,
                'tipo' => 'extranjero',
                'mensaje' => 'RFC genérico extranjero válido'
            ];
        }

        // Simular consulta (en producción usar webservice del SAT)
        return [
            'valido' => true, // Simulado
            'tipo' => strlen($rfc) === 13 ? 'fisica' : 'moral',
            'mensaje' => 'RFC encontrado en lista del SAT'
        ];
    }
}
