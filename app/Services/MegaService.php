<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Servicio para interactuar con MEGA Cloud usando MEGAcmd
 * 
 * Requisitos del sistema:
 * - Instalar MEGAcmd: sudo apt install megacmd
 */
class MegaService
{
    private ?string $email = null;
    private ?string $password = null;
    private bool $isLoggedIn = false;

    public function __construct(?string $email = null, ?string $password = null)
    {
        $this->email = $email;
        $this->password = $password;
    }

    /**
     * Verificar si MEGAcmd está instalado
     */
    public function isInstalled(): bool
    {
        $output = [];
        $returnCode = 0;
        exec('which mega-login 2>/dev/null', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Iniciar sesión en MEGA
     */
    public function login(?string $email = null, ?string $password = null): array
    {
        $email = $email ?? $this->email;
        $password = $password ?? $this->password;

        if (!$email || !$password) {
            return ['success' => false, 'message' => 'Email y password son requeridos'];
        }

        if (!$this->isInstalled()) {
            return ['success' => false, 'message' => 'MEGAcmd no está instalado. Ejecute: sudo apt install megacmd'];
        }

        // Verificar si ya hay sesión activa
        $sessionOutput = [];
        exec('mega-whoami 2>&1', $sessionOutput, $sessionCode);

        if ($sessionCode === 0 && !empty($sessionOutput)) {
            $currentSession = implode('', $sessionOutput);
            if (str_contains($currentSession, $email)) {
                $this->isLoggedIn = true;
                return ['success' => true, 'message' => 'Ya hay una sesión activa'];
            }
            // Cerrar sesión anterior
            exec('mega-logout 2>&1');
        }

        // Intentar login
        $command = sprintf(
            'mega-login %s %s 2>&1',
            escapeshellarg($email),
            escapeshellarg($password)
        );

        $output = [];
        exec($command, $output, $returnCode);
        $outputStr = implode("\n", $output);

        if ($returnCode === 0) {
            $this->isLoggedIn = true;
            Log::info('MEGA: Login exitoso para ' . $email);
            return ['success' => true, 'message' => 'Login exitoso'];
        }

        Log::warning('MEGA: Login fallido - ' . $outputStr);
        return ['success' => false, 'message' => 'Login fallido: ' . $outputStr];
    }

    /**
     * Cerrar sesión
     */
    public function logout(): array
    {
        exec('mega-logout 2>&1', $output, $returnCode);
        $this->isLoggedIn = false;
        return ['success' => true, 'message' => 'Sesión cerrada'];
    }

    /**
     * Subir archivo a MEGA
     */
    public function upload(string $localPath, string $remotePath = '/'): array
    {
        if (!file_exists($localPath)) {
            return ['success' => false, 'message' => 'El archivo local no existe: ' . $localPath];
        }

        // Asegurar que estamos logueados
        if (!$this->isLoggedIn) {
            $loginResult = $this->login();
            if (!$loginResult['success']) {
                return $loginResult;
            }
        }

        // Crear carpeta remota si no existe
        $remoteDir = dirname($remotePath);
        if ($remoteDir !== '/' && $remoteDir !== '.') {
            exec(sprintf('mega-mkdir -p %s 2>&1', escapeshellarg($remoteDir)));
        }

        // Subir archivo
        $command = sprintf(
            'mega-put %s %s 2>&1',
            escapeshellarg($localPath),
            escapeshellarg($remotePath)
        );

        $output = [];
        exec($command, $output, $returnCode);
        $outputStr = implode("\n", $output);

        if ($returnCode === 0) {
            Log::info('MEGA: Archivo subido exitosamente - ' . basename($localPath) . ' -> ' . $remotePath);
            return ['success' => true, 'message' => 'Archivo subido correctamente'];
        }

        Log::error('MEGA: Error al subir archivo - ' . $outputStr);
        return ['success' => false, 'message' => 'Error al subir: ' . $outputStr];
    }

    /**
     * Descargar archivo de MEGA
     */
    public function download(string $remotePath, string $localPath): array
    {
        if (!$this->isLoggedIn) {
            $loginResult = $this->login();
            if (!$loginResult['success']) {
                return $loginResult;
            }
        }

        // Crear directorio local si no existe
        $localDir = dirname($localPath);
        if (!is_dir($localDir)) {
            mkdir($localDir, 0755, true);
        }

        $command = sprintf(
            'mega-get %s %s 2>&1',
            escapeshellarg($remotePath),
            escapeshellarg($localPath)
        );

        $output = [];
        exec($command, $output, $returnCode);
        $outputStr = implode("\n", $output);

        if ($returnCode === 0 && file_exists($localPath)) {
            Log::info('MEGA: Archivo descargado exitosamente - ' . $remotePath);
            return ['success' => true, 'message' => 'Archivo descargado correctamente', 'path' => $localPath];
        }

        return ['success' => false, 'message' => 'Error al descargar: ' . $outputStr];
    }

    /**
     * Listar archivos en una carpeta de MEGA
     */
    public function list(string $remotePath = '/'): array
    {
        if (!$this->isLoggedIn) {
            $loginResult = $this->login();
            if (!$loginResult['success']) {
                return $loginResult;
            }
        }

        $command = sprintf('mega-ls -l %s 2>&1', escapeshellarg($remotePath));

        $output = [];
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            return ['success' => false, 'message' => 'Error al listar archivos', 'files' => []];
        }

        $files = [];
        foreach ($output as $line) {
            // Parsear línea de mega-ls -l
            // Formato: -rw- 14342 26Dec2025 12:30:45 filename.zip
            if (preg_match('/^([d\-]).*?\s+(\d+)\s+(\d+\w+\d+)\s+(\d+:\d+:\d+)\s+(.+)$/', $line, $matches)) {
                $files[] = [
                    'is_dir' => $matches[1] === 'd',
                    'size' => (int) $matches[2],
                    'date' => $matches[3] . ' ' . $matches[4],
                    'name' => trim($matches[5])
                ];
            }
        }

        return ['success' => true, 'files' => $files];
    }

    /**
     * Eliminar archivo de MEGA
     */
    public function delete(string $remotePath): array
    {
        if (!$this->isLoggedIn) {
            $loginResult = $this->login();
            if (!$loginResult['success']) {
                return $loginResult;
            }
        }

        $command = sprintf('mega-rm %s 2>&1', escapeshellarg($remotePath));

        $output = [];
        exec($command, $output, $returnCode);

        if ($returnCode === 0) {
            Log::info('MEGA: Archivo eliminado - ' . $remotePath);
            return ['success' => true, 'message' => 'Archivo eliminado'];
        }

        return ['success' => false, 'message' => 'Error al eliminar: ' . implode("\n", $output)];
    }

    /**
     * Probar conexión con MEGA
     */
    public function testConnection(?string $email = null, ?string $password = null): array
    {
        if (!$this->isInstalled()) {
            return [
                'success' => false,
                'message' => 'MEGAcmd no está instalado',
                'installed' => false
            ];
        }

        $loginResult = $this->login($email, $password);

        if ($loginResult['success']) {
            // Obtener info de la cuenta
            $output = [];
            exec('mega-df 2>&1', $output, $returnCode);

            $spaceInfo = [];
            if ($returnCode === 0) {
                foreach ($output as $line) {
                    if (preg_match('/Total:\s*([\d.]+)\s*(\w+)/', $line, $m)) {
                        $spaceInfo['total'] = $m[1] . ' ' . $m[2];
                    }
                    if (preg_match('/Used:\s*([\d.]+)\s*(\w+)/', $line, $m)) {
                        $spaceInfo['used'] = $m[1] . ' ' . $m[2];
                    }
                    if (preg_match('/Free:\s*([\d.]+)\s*(\w+)/', $line, $m)) {
                        $spaceInfo['free'] = $m[1] . ' ' . $m[2];
                    }
                }
            }

            return [
                'success' => true,
                'message' => 'Conexión exitosa con MEGA',
                'installed' => true,
                'space' => $spaceInfo
            ];
        }

        return [
            'success' => false,
            'message' => $loginResult['message'],
            'installed' => true
        ];
    }

    /**
     * Obtener uso de espacio en MEGA
     */
    public function getSpaceUsage(): array
    {
        if (!$this->isLoggedIn) {
            $loginResult = $this->login();
            if (!$loginResult['success']) {
                return $loginResult;
            }
        }

        $output = [];
        exec('mega-df 2>&1', $output, $returnCode);

        $space = [
            'total' => 'N/A',
            'used' => 'N/A',
            'free' => 'N/A'
        ];

        foreach ($output as $line) {
            if (preg_match('/Total:\s*([\d.]+\s*\w+)/', $line, $m)) {
                $space['total'] = $m[1];
            }
            if (preg_match('/Used:\s*([\d.]+\s*\w+)/', $line, $m)) {
                $space['used'] = $m[1];
            }
            if (preg_match('/Free:\s*([\d.]+\s*\w+)/', $line, $m)) {
                $space['free'] = $m[1];
            }
        }

        return ['success' => true, 'space' => $space];
    }
}
