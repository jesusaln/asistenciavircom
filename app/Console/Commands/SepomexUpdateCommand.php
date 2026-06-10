<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Eclipxe\SepomexPhp\Downloader\SymfonyDownloader;
use Eclipxe\SepomexPhp\Importer\PdoImporter;
use PDO;

/**
 * Comando para descargar y actualizar la base de datos de códigos postales de Sepomex.
 *
 * Descarga el archivo de texto oficial del Servicio Postal Mexicano (Sepomex)
 * y lo importa en un archivo SQLite para consultas rápidas de CP, colonias,
 * municipios y estados.
 *
 * Uso: php artisan sepomex:update
 */
class SepomexUpdateCommand extends Command
{
    protected $signature = 'sepomex:update
                            {--force : Forzar re-descarga aunque el archivo ya exista}';

    protected $description = 'Descarga y actualiza la base de datos de códigos postales de Sepomex (TODOS los CP de México)';

    public function handle(): int
    {
        $this->info('🇲🇽 Actualizando base de datos de Sepomex...');
        $this->newLine();

        $dbFile = storage_path('sepomex.sqlite');
        $rawFile = storage_path('sepomex_raw.txt');
        $forceDownload = $this->option('force');

        // Paso 1: Descargar el archivo de texto
        if (!file_exists($rawFile) || $forceDownload) {
            $this->info('📥 Descargando datos de Sepomex...');
            $this->output->write('   Esto puede tomar unos minutos... ');

            try {
                $downloader = new SymfonyDownloader();
                $downloader->downloadTo($rawFile);
                $fileSize = round(filesize($rawFile) / 1024 / 1024, 2);
                $this->info("✅ Descargado ({$fileSize} MB)");
            } catch (\Throwable $e) {
                $this->error("❌ Error al descargar: " . $e->getMessage());
                $this->newLine();
                $this->warn('Intentando método alternativo...');

                // Fallback: descargar manualmente desde Correos de México
                return $this->downloadFallback($rawFile, $dbFile);
            }
        } else {
            $fileAge = round((time() - filemtime($rawFile)) / 86400);
            $this->info("📄 Archivo raw existente encontrado ({$fileAge} días de antigüedad)");
            $this->info("   Usa --force para re-descargar");
        }

        // Paso 2: Crear/recrear el SQLite
        $this->info('🔨 Creando base de datos SQLite...');

        try {
            // Eliminar DB existente para recrear limpia
            if (file_exists($dbFile)) {
                unlink($dbFile);
            }

            $pdo = new PDO('sqlite:' . $dbFile, options: [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Optimizaciones para importación masiva
            $pdo->exec('PRAGMA journal_mode = MEMORY');
            $pdo->exec('PRAGMA synchronous = OFF');
            $pdo->exec('PRAGMA cache_size = -64000'); // 64MB cache

            $importer = new PdoImporter($pdo);
            $importer->createStruct();

            $this->info('   Importando registros...');
            $importer->import($rawFile);

            // Verificar cuántos registros se importaron
            $count = $pdo->query("SELECT COUNT(*) FROM raw")->fetchColumn();
            $cpCount = $pdo->query("SELECT COUNT(DISTINCT d_codigo) FROM raw")->fetchColumn();

            $dbSize = round(filesize($dbFile) / 1024 / 1024, 2);

            $this->newLine();
            $this->info("✅ Base de datos creada exitosamente:");
            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Registros totales (colonias)', number_format((int) $count)],
                    ['Códigos postales únicos', number_format((int) $cpCount)],
                    ['Tamaño de la DB', "{$dbSize} MB"],
                    ['Ubicación', $dbFile],
                ]
            );

            // Prueba rápida
            $this->testDatabase($dbFile);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("❌ Error al importar: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Método alternativo de descarga si SymfonyDownloader falla
     */
    protected function downloadFallback(string $rawFile, string $dbFile): int
    {
        // Intentar con CURL directo al sitio de Correos de México
        $this->info('📥 Intentando descarga directa...');

        $url = 'https://www.correosdemexico.gob.mx/SSLServicios/ConsultaCP/CodigoPostal_Descarga.aspx';

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_USERAGENT => 'Mozilla/5.0',
            ]);

            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || !$html) {
                $this->error('❌ No se pudo acceder al sitio de Correos de México');
                $this->newLine();
                $this->warn('Puedes descargar manualmente el archivo desde:');
                $this->line("   $url");
                $this->line("   Y guardarlo como: $rawFile");
                $this->line("   Luego ejecuta: php artisan sepomex:update");
                return Command::FAILURE;
            }

            $this->warn('El sitio de Correos de México requiere interacción manual.');
            $this->warn('Descarga el archivo TXT desde:');
            $this->line("   $url");
            $this->line("   Guárdalo como: $rawFile");
            $this->line("   Luego ejecuta: php artisan sepomex:update");
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->error('❌ Fallback también falló: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Prueba rápida de la base de datos creada
     */
    protected function testDatabase(string $dbFile): void
    {
        $this->newLine();
        $this->info('🧪 Prueba rápida:');

        try {
            $sepomex = \Eclipxe\SepomexPhp\SepomexPhp::createForDatabaseFile($dbFile);

            // Probar algunos CPs conocidos
            $testCps = [
                '06600' => 'Ciudad de México (Roma Norte)',
                '83000' => 'Hermosillo, Sonora (Centro)',
                '64000' => 'Monterrey, Nuevo León',
                '44100' => 'Guadalajara, Jalisco',
            ];

            foreach ($testCps as $cp => $desc) {
                try {
                    $data = $sepomex->getZipCodeData($cp);
                    if ($data) {
                        $colonias = count($data->locations);
                        $this->line("   ✅ CP {$cp}: {$data->state->name} / {$data->district->name} ({$colonias} colonias)");
                    } else {
                        $this->line("   ⚠️ CP {$cp}: No encontrado ({$desc})");
                    }
                } catch (\Throwable $e) {
                    $this->line("   ⚠️ CP {$cp}: Error - " . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            $this->warn('   No se pudo probar: ' . $e->getMessage());
        }
    }
}
