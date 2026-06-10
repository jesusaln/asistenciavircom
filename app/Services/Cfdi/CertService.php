<?php

namespace App\Services\Cfdi;

use App\Models\EmpresaConfiguracion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CertService
{
    /**
     * Extrae el número de serie del certificado CSD.
     */
    public function getNoCertificado(): ?string
    {
        $pem = $this->getCsdCerPem();
        if (!$pem) return null;

        $data = openssl_x509_parse($pem);
        if (!$data || !isset($data['serialNumber'])) return null;

        $serial = $data['serialNumber'];
        
        // El SAT a veces devuelve el serial en formato decimal o hex con prefijo.
        // El formato requerido son 20 dígitos.
        // Si empieza con 0x es hex, si no es decimal.
        if (str_starts_with($serial, '0x')) {
            $hex = substr($serial, 2);
        } else {
            // Convertir decimal grande a hex si es necesario (openssl_x509_parse suele darlo ya procesado)
            // Pero para el SAT lo ideal es la cadena de caracteres legible del serial.
            // Los certificados del SAT tienen un serial de 20 caracteres que son 10 bytes hex
            // 303030... en ASCII representa "0000...".
            $hex = $serial; 
        }

        // Si el serial viene como "3030303031..." es ASCII representado en HEX.
        // Debemos convertirlo a su representación de texto.
        if (strlen($hex) == 40) {
            $readable = '';
            for ($i = 0; $i < strlen($hex); $i += 2) {
                $readable .= chr(hexdec(substr($hex, $i, 2)));
            }
            return $readable;
        }

        return $hex;
    }
    public function getCsdCerPem(): ?string
    {
        $config = EmpresaConfiguracion::getConfig();
        if (!$config->csd_cer_path) return null;

        $content = Storage::disk('local')->get($config->csd_cer_path);
        
        // Si ya es PEM, limpiar posibles espacios y devolverlo
        if (str_contains($content, '-----BEGIN CERTIFICATE-----')) {
            return trim($content);
        }

        // Convertir de DER a PEM cubriendo el estándar de 64 caracteres
        return "-----BEGIN CERTIFICATE-----\n" . 
               chunk_split(base64_encode($content), 64, "\n") . 
               "-----END CERTIFICATE-----";
    }

    /**
     * Obtiene el contenido de la llave privada CSD en formato PEM.
     */
    public function getCsdKeyPem(): ?string
    {
        $config = EmpresaConfiguracion::getConfig();
        if (!$config->csd_key_path) return null;

        $content = Storage::disk('local')->get($config->csd_key_path);

        // Si ya es PEM, limpiar y devolver
        if (str_contains($content, '-----BEGIN ENCRYPTED PRIVATE KEY-----') || 
            str_contains($content, '-----BEGIN PRIVATE KEY-----') ||
            str_contains($content, '-----BEGIN RSA PRIVATE KEY-----')) {
            return trim($content);
        }

        // El formato del SAT suele ser DER PKCS#8. 
        // Intentaremos el bloque estándar de PKCS#8
        return "-----BEGIN ENCRYPTED PRIVATE KEY-----\n" . 
               chunk_split(base64_encode($content), 64, "\n") . 
               "-----END ENCRYPTED PRIVATE KEY-----";
    }

    /**
     * Obtiene el contenido crudo (binario) del certificado CSD.
     */
    public function getCsdCerRaw(): ?string
    {
        $config = EmpresaConfiguracion::getConfig();
        if (!$config->csd_cer_path) return null;
        return Storage::disk('local')->get($config->csd_cer_path);
    }

    public function getCsdCerB64(): ?string
    {
        $raw = $this->getCsdCerRaw();
        return $raw ? base64_encode($raw) : null;
    }

    public function getCsdKeyRaw(): ?string
    {
        $config = EmpresaConfiguracion::getConfig();
        if (!$config->csd_key_path) return null;
        return Storage::disk('local')->get($config->csd_key_path);
    }

    public function getCsdKeyB64(): ?string
    {
        $raw = $this->getCsdKeyRaw();
        return $raw ? base64_encode($raw) : null;
    }

    /**
     * ✅ SECURITY: Verifica si el certificado CSD está expirado
     */
    public function isCertificateExpired(): bool
    {
        $pem = $this->getCsdCerPem();
        if (!$pem) return true; // Sin certificado = considerado expirado
        
        try {
            $data = openssl_x509_parse($pem);
            if (!$data || !isset($data['validTo_time_t'])) {
                return true;
            }
            
            return time() > $data['validTo_time_t'];
        } catch (\Exception $e) {
            Log::error('Error validando expiración de certificado: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * ✅ SECURITY: Obtiene la fecha de expiración del certificado
     */
    public function getCertificateExpirationDate(): ?\DateTime
    {
        $pem = $this->getCsdCerPem();
        if (!$pem) return null;
        
        try {
            $data = openssl_x509_parse($pem);
            if (!$data || !isset($data['validTo_time_t'])) {
                return null;
            }
            
            return (new \DateTime())->setTimestamp($data['validTo_time_t']);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * ✅ SECURITY: Obtiene los días restantes de validez del certificado
     */
    public function getCertificateDaysRemaining(): int
    {
        $expiration = $this->getCertificateExpirationDate();
        if (!$expiration) return 0;
        
        $now = new \DateTime();
        $diff = $now->diff($expiration);
        
        // Si ya expiró, retornar negativo
        if ($expiration < $now) {
            return -$diff->days;
        }
        
        return $diff->days;
    }

    /**
     * ✅ SECURITY: Valida que el certificado esté listo para usar
     * Retorna array con success y message
     */
    public function validateCertificate(): array
    {
        // Verificar que existan los archivos
        $config = EmpresaConfiguracion::getConfig();
        
        if (!$config->csd_cer_path || !Storage::disk('local')->exists($config->csd_cer_path)) {
            return ['success' => false, 'message' => 'No se encuentra el archivo del certificado CSD (.cer)'];
        }
        
        if (!$config->csd_key_path || !Storage::disk('local')->exists($config->csd_key_path)) {
            return ['success' => false, 'message' => 'No se encuentra el archivo de la llave privada CSD (.key)'];
        }
        
        if (!$config->csd_password) {
            return ['success' => false, 'message' => 'No se ha configurado la contraseña del CSD'];
        }
        
        // Verificar expiración
        if ($this->isCertificateExpired()) {
            $expDate = $this->getCertificateExpirationDate();
            $expStr = $expDate ? $expDate->format('Y-m-d') : 'desconocida';
            return ['success' => false, 'message' => "El certificado CSD ha expirado (fecha: {$expStr}). Por favor renuévelo en el portal del SAT."];
        }
        
        // Advertir si está por expirar (menos de 30 días)
        $daysRemaining = $this->getCertificateDaysRemaining();
        if ($daysRemaining > 0 && $daysRemaining <= 30) {
            Log::warning("Certificado CSD expira en {$daysRemaining} días. Considere renovarlo.");
        }
        
        return ['success' => true, 'message' => 'Certificado válido', 'days_remaining' => $daysRemaining];
    }
}
