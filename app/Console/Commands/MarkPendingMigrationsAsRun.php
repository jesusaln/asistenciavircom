<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MarkPendingMigrationsAsRun extends Command
{
    protected $signature = 'migrations:mark-all-run';
    protected $description = 'Mark all pending migrations as run';

    public function handle()
    {
        $batch = DB::table('migrations')->max('batch') + 1;

        $migrations = [
            '2025_10_04_201849_create_traspasos_table',
            '2025_10_04_202254_add_almacen_id_to_compras_table',
            '2025_10_04_210232_create_ajustes_inventario_table',
            '2025_10_04_210736_create_movimientos_manuales_table',
            '2025_10_04_211313_create_alertas_stock_table',
            '2025_10_04_213837_improve_traspasos_table',
            '2025_10_05_092029_create_producto_precio_historial_table',
            '2025_10_05_170705_add_almacen_id_to_inventario_movimientos_table',
            '2025_10_05_170853_drop_inventario_logs_table',
            '2025_10_05_172119_create_lotes_table',
            '2025_10_05_172211_add_lote_id_to_inventario_movimientos_table',
            '2025_10_05_173001_create_cuentas_por_pagar_table',
            '2025_10_05_181331_assign_almacen_id_to_existing_products',
            '2025_10_05_181353_create_initial_inventario_records_for_existing_products',
            '2025_10_05_200000_create_cuentas_por_cobrar_table',
            '2025_10_06_093906_add_names_to_inventario_movimientos_table',
            '2025_10_06_105517_add_almacen_id_to_orden_compras_table',
            '2025_10_06_172537_add_entregado_responsable_to_entregas_dinero_table',
            '2025_10_06_174401_add_payment_info_to_cuentas_por_pagar_table',
            '2025_10_06_200001_add_indexes_to_mantenimientos_table',
            '2025_10_06_200100_add_km_alert_to_mantenimientos_table',
            '2025_10_07_111020_add_security_fields_to_backup_logs_table',
            '2025_10_07_150226_create_empresa_configuracion_table',
            '2025_10_07_154035_modify_empresa_configuracion_direccion_fields',
            '2025_10_07_155438_clean_empresa_configuracion_old_fields',
            '2025_10_07_160000_add_colonia_to_empresa_configuracion_table',
            '2025_10_08_133747_add_datos_bancarios_to_empresa_configuracion_table',
            '2025_10_08_135608_add_banking_fields_to_empresa_configuracion_table',
            '2025_10_08_135638_add_email_fields_to_empresa_configuracion_table',
            '2025_10_08_151243_add_dkim_fields_to_empresa_configuracion_table',
            '2025_10_08_221114_create_recordatorios_cobranza_correcta_table',
            '2025_10_09_132334_add_email_tracking_to_orden_compras_table',
            '2025_10_09_233344_add_email_tracking_to_pedidos_table',
            '2025_10_10_171141_create_prestamos_table',
            '2025_10_10_175020_add_tasa_interes_mensual_to_prestamos_table',
            '2025_10_10_175948_make_tasa_interes_nullable_in_prestamos_table',
            '2025_10_10_180254_create_pagos_prestamos_table',
            '2025_10_10_183554_create_historial_pagos_prestamos_table',
            '2025_10_10_211635_create_vacacions_table',
            '2025_10_10_215154_create_registro_vacaciones_table',
            '2025_10_11_152927_add_whatsapp_to_empresas_table',
            '2025_10_11_152959_create_whatsapp_messages_table',
            '2025_10_13_174014_add_dark_mode_fields_to_empresa_configuracion_table',
            '2025_10_14_181122_create_clientes_final_table',
            '2025_10_14_182356_create_productos_final_table',
            '2025_10_14_182803_create_users_final_table',
            '2025_10_14_183104_create_cotizaciones_final_tables',
            '2025_10_14_185544_create_categorias_final_tables',
            '2025_10_14_191039_create_almacenes_final_table',
            '2025_10_15_195032_create_cita_productos_utilizados_table',
            '2025_10_15_195059_create_cita_productos_vendidos_table',
            '2025_10_16_000000_create_cita_servicios_table',
            '2025_10_16_000001_add_requiere_serie_to_productos_table',
            '2025_10_16_000002_create_producto_series_table',
            '2025_10_16_114628_fix_tecnicos_margenes_constraints',
            '2025_10_20_124108_add_activo_to_carros_table',
            '2025_10_21_141708_create_unidades_medida_table',
            '2025_10_24_083900_normalize_polymorphic_types',
            '2025_11_04_075815_add_almacen_venta_id_to_users_table',
            '2025_11_05_000001_make_clientes_address_nullable',
        ];

        foreach ($migrations as $migration) {
            if (DB::table('migrations')->where('migration', $migration)->doesntExist()) {
                DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => $batch
                ]);
                $this->info("Marked $migration as run.");
            } else {
                $this->line("$migration already exists in migrations table.");
            }
        }

        $this->info('All pending migrations have been marked as run.');
        return Command::SUCCESS;
    }
}
