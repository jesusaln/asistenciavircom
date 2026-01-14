<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use PDO;
use GuzzleHttp\Client;

class DownloadSepomexData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sepomex:download {--force : Forzar descarga incluso si el archivo existe}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Descarga datos de SEPOmex y genera base de datos SQLite para autocompletado de direcciones';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando descarga de datos SEPOmex...');

        $databasePath = storage_path('sepomex.sqlite');
        $rawFilePath = storage_path('sepomex.txt');

        // Verificar si ya existe
        if (file_exists($databasePath) && !$this->option('force')) {
            $this->warn('⚠️  La base de datos SEPOmex ya existe. Usa --force para sobrescribir.');
            $this->info('📍 Ubicación: ' . $databasePath);
            $this->info('💡 Para probar: GET /api/cp/01000');
            return;
        }

        try {
            $this->info('📥 Descargando archivo TXT de SEPOmex...');
            $this->info('Esto puede tomar varios minutos...');

            // Descargar el archivo usando Guzzle
            $client = new Client(['timeout' => 300]);
            $response = $client->get('https://www.correosdemexico.gob.mx/datosabiertos/cp/cpdescarga.txt');
            file_put_contents($rawFilePath, $response->getBody());

            $this->info('✅ Archivo TXT descargado correctamente');

            $this->info('🔄 Creando base de datos SQLite...');

            // Crear PDO para SQLite
            $pdo = new PDO('sqlite:' . $databasePath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Crear el importer y procesar
            $importer = new \Eclipxe\SepomexPhp\Importer\PdoImporter($pdo);
            $importer->createStruct();
            $importer->import($rawFilePath);

            // Limpiar archivo temporal
            if (file_exists($rawFilePath)) {
                unlink($rawFilePath);
            }

            $this->info('✅ Base de datos SEPOmex creada exitosamente');
            $this->info('📍 Ubicación: ' . $databasePath);
            $this->info('🎉 ¡Listo! Ahora puedes usar el endpoint /api/cp/{codigo_postal}');

            // Verificar que funciona
            $this->info('🔍 Verificando funcionamiento...');
            $sepomex = \Eclipxe\SepomexPhp\SepomexPhp::createForDatabaseFile($databasePath);
            $testData = $sepomex->getZipCodeData('01000'); // Código postal de prueba

            if ($testData) {
                $this->info('✅ Verificación exitosa - Base de datos funcional');
                $this->info('💡 Ejemplo: GET /api/cp/01000');
            } else {
                $this->warn('⚠️  Verificación fallida - Puede que la base de datos esté vacía');
            }

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error('Asegúrate de tener conexión a internet.');

            // Limpiar archivos temporales en caso de error
            if (file_exists($rawFilePath)) {
                unlink($rawFilePath);
            }

            return 1;
        }
    }
}
