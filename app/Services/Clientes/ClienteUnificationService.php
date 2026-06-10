<?php

namespace App\Services\Clientes;

use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ClienteUnificationService
{
    /**
     * Unifica dos clientes: transfiere todas las relaciones vinculadas del duplicado al maestro
     * y elimina el registro duplicado.
     *
     * @param int|Cliente $master Cliente que prevalecerá en el sistema.
     * @param int|Cliente $duplicate Cliente que será fusionado y eliminado.
     * @return array Resumen de la unificación con los conteos de registros transferidos.
     * @throws Exception Si los clientes no existen o pertenecen a distintas empresas.
     */
    public function unify($master, $duplicate): array
    {
        $masterCliente = $master instanceof Cliente ? $master : Cliente::withoutGlobalScopes()->findOrFail($master);
        $dupCliente = $duplicate instanceof Cliente ? $duplicate : Cliente::withoutGlobalScopes()->findOrFail($duplicate);

        if ($masterCliente->id === $dupCliente->id) {
            throw new Exception("No se puede unificar un cliente consigo mismo (ID: {$masterCliente->id}).");
        }

        if ($masterCliente->empresa_id !== $dupCliente->empresa_id) {
            throw new Exception("No se pueden unificar clientes de distintas empresas (Master Empresa: {$masterCliente->empresa_id}, Duplicado Empresa: {$dupCliente->empresa_id}).");
        }

        Log::info("Iniciando unificación de clientes", [
            'master_id' => $masterCliente->id,
            'master_nombre' => $masterCliente->nombre_razon_social,
            'duplicate_id' => $dupCliente->id,
            'duplicate_nombre' => $dupCliente->nombre_razon_social,
        ]);

        $summary = [
            'master_id' => $masterCliente->id,
            'duplicate_id' => $dupCliente->id,
            'tablas_actualizadas' => [],
            'atributos_fusionados' => [],
        ];

        DB::beginTransaction();

        try {
            // 1. Tablas directas con 'cliente_id'
            $tablas = [
                'ventas',
                'cotizaciones',
                'facturas',
                'pedidos',
                'rentas',
                'polizas_servicio',
                'prestamos',
                'tickets',
                'citas',
                'taller_ordenes',
                'cliente_documentos',
                'marketing_mensajes_entrantes',
                'contratos',
                'crm_prospectos',
                'repse_contracts',
                'cuentas_por_cobrar',
                'marketing_destinatarios',
                'bitacora_actividades',
                'proyectos',
                'cfdis',
            ];

            foreach ($tablas as $tabla) {
                // Verificar si la tabla existe por seguridad
                if (DB::getSchemaBuilder()->hasTable($tabla)) {
                    $count = DB::table($tabla)
                        ->where('cliente_id', $dupCliente->id)
                        ->update(['cliente_id' => $masterCliente->id]);

                    if ($count > 0) {
                        $summary['tablas_actualizadas'][$tabla] = $count;
                    }
                }
            }

            // 2. Relaciones polimórficas: Credenciales
            if (DB::getSchemaBuilder()->hasTable('credenciales')) {
                $countCred = DB::table('credenciales')
                    ->where('credentialable_type', Cliente::class)
                    ->where('credentialable_id', $dupCliente->id)
                    ->update(['credentialable_id' => $masterCliente->id]);

                if ($countCred > 0) {
                    $summary['tablas_actualizadas']['credenciales'] = $countCred;
                }
            }

            // 3. Tablas pivote: Marketing Audiencias (muchos a muchos)
            if (DB::getSchemaBuilder()->hasTable('marketing_audiencia_clientes')) {
                $audienciasMaster = DB::table('marketing_audiencia_clientes')
                    ->where('cliente_id', $masterCliente->id)
                    ->pluck('audiencia_id')
                    ->toArray();

                // Eliminar del duplicado las audiencias que ya tiene el maestro para evitar violación de llave primaria/única
                if (!empty($audienciasMaster)) {
                    DB::table('marketing_audiencia_clientes')
                        ->where('cliente_id', $dupCliente->id)
                        ->whereIn('audiencia_id', $audienciasMaster)
                        ->delete();
                }

                // Transferir las audiencias restantes
                $countAud = DB::table('marketing_audiencia_clientes')
                    ->where('cliente_id', $dupCliente->id)
                    ->update(['cliente_id' => $masterCliente->id]);

                if ($countAud > 0) {
                    $summary['tablas_actualizadas']['marketing_audiencia_clientes'] = $countAud;
                }
            }

            // 4. Fusionar atributos faltantes en el maestro
            $atributosParaCompletar = [
                'email',
                'telefono',
                'rfc',
                'calle',
                'numero_exterior',
                'numero_interior',
                'colonia',
                'codigo_postal',
                'municipio',
                'estado',
                'pais',
                'curp',
                'notas',
                'domicilio_fiscal_cp',
                'forma_pago_default',
                'cfdi_default_use',
            ];

            $cambiosMaster = [];
            foreach ($atributosParaCompletar as $attr) {
                if (empty($masterCliente->{$attr}) && !empty($dupCliente->{$attr})) {
                    if ($attr === 'rfc' && ($dupCliente->{$attr} === 'XAXX010101000' || $dupCliente->{$attr} === 'XEXX010101000')) {
                        continue; // No transferir RFC genérico si el maestro está en blanco
                    }
                    $cambiosMaster[$attr] = $dupCliente->{$attr};
                    $summary['atributos_fusionados'][$attr] = $dupCliente->{$attr};
                }
            }

            // Conservar el límite de crédito mayor si aplica
            if ($masterCliente->limite_credito == 0 && $dupCliente->limite_credito > 0) {
                $cambiosMaster['limite_credito'] = $dupCliente->limite_credito;
                $cambiosMaster['credito_activo'] = $dupCliente->credito_activo;
                $cambiosMaster['dias_credito'] = $dupCliente->dias_credito;
                $summary['atributos_fusionados']['limite_credito'] = $dupCliente->limite_credito;
            }

            if (!empty($cambiosMaster)) {
                $masterCliente->update($cambiosMaster);
            }

            // 5. Eliminar el registro duplicado (soft delete para rastro de auditoría)
            $dupCliente->delete();

            // 6. Si queremos dejar una nota en las notas del maestro sobre la unificación
            $notaUnificacion = "\n[Unificado el " . now()->format('Y-m-d H:i') . " con ID anterior {$dupCliente->id}]";
            $masterCliente->notas = (($masterCliente->notas ?? '') . $notaUnificacion);
            $masterCliente->save();

            DB::commit();

            Log::info("Unificación de clientes completada exitosamente", [
                'master_id' => $masterCliente->id,
                'summary' => $summary,
            ]);

            return $summary;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error durante unificación de clientes: " . $e->getMessage(), [
                'master_id' => $masterCliente->id ?? null,
                'duplicate_id' => $dupCliente->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
