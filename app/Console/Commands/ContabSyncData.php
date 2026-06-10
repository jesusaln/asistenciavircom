<?php

namespace App\Console\Commands;

use App\Models\Contab\AsientoContable;
use App\Models\Contab\CuentaContable;
use App\Models\Contab\PolizaContable;
use App\Models\Contab\RfcMapping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ContabSyncData extends Command
{
    protected $signature = 'contab:sync-data {action : export or import} {file=contab_data.json}';
    protected $description = 'Exporta o importa datos contables (cuentas, mappings, pólizas, asientos)';

    public function handle()
    {
        $action = $this->argument('action');
        $file = $this->argument('file');

        if ($action === 'export') {
            $this->exportData($file);
        } elseif ($action === 'import') {
            $this->importData($file);
        } else {
            $this->error("Acción no reconocida. Usa 'export' o 'import'.");
        }
    }

    protected function exportData($file)
    {
        $this->info("Exportando datos contables...");

        $data = [
            'cuentas' => CuentaContable::withTrashed()->get()->toArray(),
            'mappings' => RfcMapping::all()->toArray(), // Doesn't seem to use soft deletes
            'polizas' => PolizaContable::withTrashed()->get()->toArray(),
            'asientos' => AsientoContable::all()->toArray(), // Check if it uses soft deletes
        ];

        File::put($file, json_encode($data, JSON_PRETTY_PRINT));
        $this->info("Datos exportados a {$file}");
    }

    protected function importData($file)
    {
        if (!File::exists($file)) {
            $this->error("El archivo {$file} no existe.");
            return;
        }

        $this->info("Importando datos contables desde {$file}...");
        $data = json_decode(File::get($file), true);

        DB::transaction(function () use ($data) {
            // Desactivar constraints de FK temporalmente
            DB::statement('SET CONSTRAINTS ALL DEFERRED');
            
            $this->info("  - Limpiando tablas actuales...");
            DB::table('contab_asientos')->delete();
            DB::table('contab_polizas')->delete();
            DB::table('contab_rfc_mappings')->delete();
            DB::table('contab_cuentas')->delete();

            $this->info("  - Procesando Cuentas...");
            // Ordenar por nivel para evitar violaciones de padre_id (aunque estén deferred)
            $cuentas = collect($data['cuentas'])->sortBy('nivel');
            foreach ($cuentas as $c) {
                DB::table('contab_cuentas')->insert($c);
            }

            $this->info("  - Procesando RFC Mappings...");
            foreach ($data['mappings'] as $m) {
                DB::table('contab_rfc_mappings')->insert($m);
            }

            $this->info("  - Procesando Pólizas...");
            foreach ($data['polizas'] as $p) {
                if (isset($p['cfdi_uuids']) && is_array($p['cfdi_uuids'])) $p['cfdi_uuids'] = json_encode($p['cfdi_uuids']);
                if (isset($p['soportes']) && is_array($p['soportes'])) $p['soportes'] = json_encode($p['soportes']);
                DB::table('contab_polizas')->insert($p);
            }

            $this->info("  - Procesando Asientos...");
            foreach ($data['asientos'] as $a) {
                DB::table('contab_asientos')->insert($a);
            }

            // Resetear secuencias en Postgres para evitar errores de duplicado en el futuro
            $tables = ['contab_cuentas', 'contab_rfc_mappings', 'contab_polizas', 'contab_asientos'];
            foreach ($tables as $table) {
                $maxId = DB::table($table)->max('id') ?: 0;
                $seq = $table . '_id_seq';
                DB::statement("SELECT setval('$seq', $maxId + 1, false)");
            }
        });

        $this->info("Importación completada exitosamente.");
    }
}
