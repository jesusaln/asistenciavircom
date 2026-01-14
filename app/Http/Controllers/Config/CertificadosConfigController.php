<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Controller para gestión de certificados fiscales
 * 
 * Maneja: FIEL, CSD, PAC
 */
class CertificadosConfigController extends Controller
{
    use ApiResponse;

    /**
     * Obtener información de certificados (sin contraseñas) para admins
     */
    public function getCertificadosInfo()
    {
        if (!auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            return $this->forbidden();
        }

        $config = EmpresaConfiguracion::getConfig();

        return $this->success([
            'fiel' => [
                'configurado' => !empty($config->fiel_cer_path),
                'rfc' => $config->fiel_rfc,
                'serial' => $config->fiel_serial,
                'valid_from' => $config->fiel_valid_from?->format('d/m/Y'),
                'valid_to' => $config->fiel_valid_to?->format('d/m/Y'),
                'vigente' => $config->fiel_valid_to ? $config->fiel_valid_to->isFuture() : false,
            ],
            'csd' => [
                'configurado' => !empty($config->csd_cer_path),
                'rfc' => $config->csd_rfc,
                'serial' => $config->csd_serial,
                'valid_from' => $config->csd_valid_from?->format('d/m/Y'),
                'valid_to' => $config->csd_valid_to?->format('d/m/Y'),
                'vigente' => $config->csd_valid_to ? $config->csd_valid_to->isFuture() : false,
            ],
            'pac' => [
                'configurado' => !empty($config->pac_apikey),
                'nombre' => $config->pac_nombre,
                'base_url' => $config->pac_base_url,
                'produccion' => $config->pac_produccion,
            ],
        ]);
    }

    /**
     * Subir certificado FIEL (e.firma)
     */
    public function subirCertificadoFiel(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            return back()->withErrors(['error' => 'No tienes permisos para realizar esta acción.']);
        }

        $request->validate([
            'fiel_cer' => 'required|file|max:50',
            'fiel_key' => 'required|file|max:50',
            'fiel_password' => 'required|string|min:8',
        ], [
            'fiel_cer.required' => 'El archivo .cer de FIEL es requerido.',
            'fiel_key.required' => 'El archivo .key de FIEL es requerido.',
            'fiel_password.required' => 'La contraseña de FIEL es requerida.',
            'fiel_password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        try {
            $config = EmpresaConfiguracion::getConfig();

            $directory = 'certificados/fiel';
            if (!Storage::disk('local')->exists($directory)) {
                Storage::disk('local')->makeDirectory($directory);
            }

            $timestamp = now()->format('YmdHis');
            $cerFileName = $directory . '/fiel_' . $timestamp . '.cer';
            $keyFileName = $directory . '/fiel_' . $timestamp . '.key';

            Storage::disk('local')->put($cerFileName, file_get_contents($request->file('fiel_cer')->getRealPath()));
            Storage::disk('local')->put($keyFileName, file_get_contents($request->file('fiel_key')->getRealPath()));

            $cerFullPath = Storage::disk('local')->path($cerFileName);
            $keyFullPath = Storage::disk('local')->path($keyFileName);

            Log::info('Archivos FIEL guardados', ['cerPath' => $cerFileName, 'keyPath' => $keyFileName]);

            $certInfo = $this->extraerInfoCertificado($cerFullPath);

            $tipoCert = $this->detectarTipoCertificado($cerFullPath);
            if ($tipoCert === 'csd') {
                Storage::disk('local')->delete([$cerFileName, $keyFileName]);
                return back()->withErrors([
                    'fiel_cer' => '⚠️ Este certificado parece ser un CSD, no una FIEL. Por favor sube tu FIEL aquí y el CSD en la sección correspondiente.'
                ]);
            }

            if (!$this->validarLlavePrivada($keyFullPath, $request->fiel_password)) {
                Storage::disk('local')->delete([$cerFileName, $keyFileName]);
                return back()->withErrors(['fiel_password' => 'La contraseña de la llave privada es incorrecta.']);
            }

            if ($config->fiel_cer_path)
                Storage::disk('local')->delete($config->fiel_cer_path);
            if ($config->fiel_key_path)
                Storage::disk('local')->delete($config->fiel_key_path);

            $config->update([
                'fiel_cer_path' => $cerFileName,
                'fiel_key_path' => $keyFileName,
                'fiel_password' => $request->fiel_password,
                'fiel_valid_from' => $certInfo['valid_from'] ?? null,
                'fiel_valid_to' => $certInfo['valid_to'] ?? null,
                'fiel_serial' => $certInfo['serial'] ?? null,
                'fiel_rfc' => $certInfo['rfc'] ?? null,
            ]);

            Log::info('Certificado FIEL subido correctamente', ['rfc' => $certInfo['rfc'] ?? 'N/A']);

            return back()->with('success', 'Certificado FIEL subido correctamente. Válido hasta: ' . ($certInfo['valid_to'] ?? 'N/A'));

        } catch (\Exception $e) {
            Log::error('Error al subir certificado FIEL', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Error al procesar el certificado: ' . $e->getMessage()]);
        }
    }

    /**
     * Subir certificado CSD (Sello Digital)
     */
    public function subirCertificadoCsd(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            return back()->withErrors(['error' => 'No tienes permisos para realizar esta acción.']);
        }

        $request->validate([
            'csd_cer' => 'required|file|max:50',
            'csd_key' => 'required|file|max:50',
            'csd_password' => 'required|string|min:8',
        ], [
            'csd_cer.required' => 'El archivo .cer de CSD es requerido.',
            'csd_key.required' => 'El archivo .key de CSD es requerido.',
            'csd_password.required' => 'La contraseña de CSD es requerida.',
        ]);

        try {
            $config = EmpresaConfiguracion::getConfig();

            $directory = 'certificados/csd';
            if (!Storage::disk('local')->exists($directory)) {
                Storage::disk('local')->makeDirectory($directory);
            }

            $timestamp = now()->format('YmdHis');
            $cerFileName = $directory . '/csd_' . $timestamp . '.cer';
            $keyFileName = $directory . '/csd_' . $timestamp . '.key';

            Storage::disk('local')->put($cerFileName, file_get_contents($request->file('csd_cer')->getRealPath()));
            Storage::disk('local')->put($keyFileName, file_get_contents($request->file('csd_key')->getRealPath()));

            $cerFullPath = Storage::disk('local')->path($cerFileName);
            $keyFullPath = Storage::disk('local')->path($keyFileName);

            Log::info('Archivos CSD guardados', ['cerPath' => $cerFileName, 'keyPath' => $keyFileName]);

            $certInfo = $this->extraerInfoCertificado($cerFullPath);

            $tipoCert = $this->detectarTipoCertificado($cerFullPath);
            if ($tipoCert === 'fiel') {
                Storage::disk('local')->delete([$cerFileName, $keyFileName]);
                return back()->withErrors([
                    'csd_cer' => '⚠️ Este certificado parece ser una FIEL, no un CSD. Por favor sube tu CSD aquí y la FIEL en la sección correspondiente.'
                ]);
            }

            if (!$this->validarLlavePrivada($keyFullPath, $request->csd_password)) {
                Storage::disk('local')->delete([$cerFileName, $keyFileName]);
                return back()->withErrors(['csd_password' => 'La contraseña de la llave privada es incorrecta.']);
            }

            if ($config->csd_cer_path)
                Storage::disk('local')->delete($config->csd_cer_path);
            if ($config->csd_key_path)
                Storage::disk('local')->delete($config->csd_key_path);

            $config->update([
                'csd_cer_path' => $cerFileName,
                'csd_key_path' => $keyFileName,
                'csd_password' => $request->csd_password,
                'csd_valid_from' => $certInfo['valid_from'] ?? null,
                'csd_valid_to' => $certInfo['valid_to'] ?? null,
                'csd_serial' => $certInfo['serial'] ?? null,
                'csd_rfc' => $certInfo['rfc'] ?? null,
            ]);

            Log::info('Certificado CSD subido correctamente', ['rfc' => $certInfo['rfc'] ?? 'N/A']);

            return back()->with('success', 'Certificado CSD subido correctamente. Válido hasta: ' . ($certInfo['valid_to'] ?? 'N/A'));

        } catch (\Exception $e) {
            Log::error('Error al subir certificado CSD', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Error al procesar el certificado: ' . $e->getMessage()]);
        }
    }

    /**
     * Eliminar certificado FIEL (requiere contraseña del usuario)
     */
    public function eliminarCertificadoFiel(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            return $this->forbidden();
        }

        $request->validate(['password' => 'required|string']);

        if (!password_verify($request->password, auth()->user()->password)) {
            return $this->error('La contraseña de tu cuenta es incorrecta.');
        }

        $config = EmpresaConfiguracion::getConfig();

        if ($config->fiel_cer_path)
            Storage::disk('local')->delete($config->fiel_cer_path);
        if ($config->fiel_key_path)
            Storage::disk('local')->delete($config->fiel_key_path);

        $config->update([
            'fiel_cer_path' => null,
            'fiel_key_path' => null,
            'fiel_password' => null,
            'fiel_valid_from' => null,
            'fiel_valid_to' => null,
            'fiel_serial' => null,
            'fiel_rfc' => null,
        ]);

        EmpresaConfiguracion::clearCache();
        Log::info('Certificado FIEL eliminado por usuario', ['user_id' => auth()->id()]);

        return $this->success(null, 'Certificado FIEL eliminado correctamente.');
    }

    /**
     * Eliminar certificado CSD (requiere contraseña del usuario)
     */
    public function eliminarCertificadoCsd(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            return $this->forbidden();
        }

        $request->validate(['password' => 'required|string']);

        if (!password_verify($request->password, auth()->user()->password)) {
            return $this->error('La contraseña de tu cuenta es incorrecta.');
        }

        $config = EmpresaConfiguracion::getConfig();

        if ($config->csd_cer_path)
            Storage::disk('local')->delete($config->csd_cer_path);
        if ($config->csd_key_path)
            Storage::disk('local')->delete($config->csd_key_path);

        $config->update([
            'csd_cer_path' => null,
            'csd_key_path' => null,
            'csd_password' => null,
            'csd_valid_from' => null,
            'csd_valid_to' => null,
            'csd_serial' => null,
            'csd_rfc' => null,
        ]);

        EmpresaConfiguracion::clearCache();
        Log::info('Certificado CSD eliminado por usuario', ['user_id' => auth()->id()]);

        return $this->success(null, 'Certificado CSD eliminado correctamente.');
    }

    /**
     * Guardar configuración del PAC
     */
    public function guardarPac(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            return redirect()->back()->withErrors(['error' => 'No autorizado']);
        }

        $validated = $request->validate([
            'pac_nombre' => 'nullable|string|max:100',
            'pac_base_url' => 'nullable|url|max:255',
            'pac_apikey' => 'nullable|string|max:500',
            'pac_produccion' => 'nullable|boolean',
        ]);

        $config = EmpresaConfiguracion::getConfig();

        $updateData = [
            'pac_nombre' => $validated['pac_nombre'],
            'pac_base_url' => $validated['pac_base_url'],
            'pac_produccion' => $request->boolean('pac_produccion'),
        ];

        if (!empty($validated['pac_apikey'])) {
            $updateData['pac_apikey'] = $validated['pac_apikey'];
        }

        $config->update($updateData);
        EmpresaConfiguracion::clearCache();

        return redirect()->back()->with('success', 'Configuración del PAC guardada correctamente');
    }

    /**
     * Probar conexión con el PAC
     */
    public function testPac(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            return $this->forbidden();
        }

        $validated = $request->validate([
            'pac_base_url' => 'required|url',
            'pac_apikey' => 'required|string',
        ]);

        try {
            $http = new \GuzzleHttp\Client([
                'base_uri' => rtrim($validated['pac_base_url'], '/') . '/',
                'timeout' => 10,
                'verify' => false,
            ]);

            $response = $http->post('consultarCreditosDisponibles', [
                'form_params' => ['apikey' => $validated['pac_apikey']],
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode((string) $response->getBody(), true);

            if ($statusCode === 200) {
                $saldo = $body['saldo'] ?? $body['creditos'] ?? $body['timbres'] ?? null;
                $message = 'Conexión exitosa con el PAC.';
                if ($saldo !== null) {
                    $message .= " Saldo disponible: {$saldo} timbres.";
                }
                return $this->success(['saldo' => $saldo], $message);
            }

            return $this->error('El PAC respondió con código ' . $statusCode . ': ' . ($body['message'] ?? 'Error desconocido'));

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return $this->error('No se pudo conectar al PAC. Verifica la URL.');
        } catch (\Exception $e) {
            Log::error('Error probando PAC', ['error' => $e->getMessage()]);
            return $this->error('Error: ' . $e->getMessage());
        }
    }

    /**
     * Extraer información de un certificado .cer del SAT
     */
    private function extraerInfoCertificado(string $cerPath): array
    {
        try {
            $certContent = file_get_contents($cerPath);
            $certResource = @openssl_x509_read($certContent);

            if (!$certResource) {
                $pemContent = "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode($certContent), 64, "\n") . "-----END CERTIFICATE-----";
                $certResource = @openssl_x509_read($pemContent);
            }

            if (!$certResource) {
                Log::warning('No se pudo leer el certificado', ['path' => $cerPath]);
                return [];
            }

            $certInfo = openssl_x509_parse($certResource);
            $subject = $certInfo['subject'] ?? [];

            $rfc = null;
            if (isset($subject['x500UniqueIdentifier'])) {
                $uid = $subject['x500UniqueIdentifier'];
                if (is_array($uid)) {
                    foreach ($uid as $val) {
                        if (preg_match('/^[A-Z&Ñ]{3,4}\d{6}[A-Z0-9]{3}$/', $val)) {
                            $rfc = $val;
                            break;
                        }
                    }
                } else {
                    $rfc = $uid;
                }
            }

            if (!$rfc && isset($subject['UID'])) {
                $rfc = is_array($subject['UID']) ? $subject['UID'][0] : $subject['UID'];
            }

            $serial = $certInfo['serialNumberHex'] ?? null;
            if (!$serial && isset($certInfo['serialNumber'])) {
                $serial = dechex((int) $certInfo['serialNumber']);
            }

            return [
                'valid_from' => isset($certInfo['validFrom_time_t']) ? date('Y-m-d H:i:s', $certInfo['validFrom_time_t']) : null,
                'valid_to' => isset($certInfo['validTo_time_t']) ? date('Y-m-d H:i:s', $certInfo['validTo_time_t']) : null,
                'serial' => $serial,
                'rfc' => $rfc,
                'name' => $subject['CN'] ?? $subject['name'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Error extrayendo info de certificado', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Detectar si un certificado es FIEL o CSD
     */
    private function detectarTipoCertificado(string $cerPath): string
    {
        try {
            $certContent = file_get_contents($cerPath);
            $certResource = @openssl_x509_read($certContent);
            if (!$certResource) {
                $pemContent = "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode($certContent), 64, "\n") . "-----END CERTIFICATE-----";
                $certResource = @openssl_x509_read($pemContent);
            }

            if (!$certResource)
                return 'desconocido';

            $certInfo = openssl_x509_parse($certResource);
            $subject = $certInfo['subject'] ?? [];
            $ou = $subject['OU'] ?? '';
            if (is_array($ou))
                $ou = implode(' ', $ou);
            $ou = strtoupper($ou);

            if (str_contains($ou, 'SELLO') || str_contains($ou, 'CSD') || str_contains($ou, 'FACTURACION')) {
                return 'csd';
            }

            if (str_contains($ou, 'FIRMA') || str_contains($ou, 'FIEL') || str_contains($ou, 'EFIRMA')) {
                return 'fiel';
            }

            return 'desconocido';

        } catch (\Exception $e) {
            Log::error('Error detectando tipo de certificado', ['error' => $e->getMessage()]);
            return 'desconocido';
        }
    }

    /**
     * Validar que la contraseña de la llave privada sea correcta
     */
    private function validarLlavePrivada(string $keyPath, string $password): bool
    {
        try {
            $escapedPassword = escapeshellarg($password);
            $escapedKeyPath = escapeshellarg($keyPath);

            $command = "openssl pkcs8 -inform DER -in {$escapedKeyPath} -passin pass:{$password} -nocrypt 2>&1";
            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                Log::info('Llave privada validada correctamente con OpenSSL CLI (PKCS#8 DER)');
                return true;
            }

            $keyContent = file_get_contents($keyPath);
            $privateKey = @openssl_pkey_get_private($keyContent, $password);

            if (!$privateKey) {
                $pemKey = "-----BEGIN ENCRYPTED PRIVATE KEY-----\n" . chunk_split(base64_encode($keyContent), 64, "\n") . "-----END ENCRYPTED PRIVATE KEY-----";
                $privateKey = @openssl_pkey_get_private($pemKey, $password);
            }

            if ($privateKey) {
                Log::info('Llave privada validada correctamente con OpenSSL PHP');
                return true;
            }

            Log::warning('No se pudo validar la llave privada - contraseña incorrecta o formato no soportado');
            return false;

        } catch (\Exception $e) {
            Log::error('Error validando llave privada', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
