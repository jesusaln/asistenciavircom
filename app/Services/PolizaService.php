<?php

namespace App\Services;

use App\Models\PolizaServicio;
use App\Models\PolizaAuditLog;
use Illuminate\Support\Facades\DB;

class PolizaService
{
    /**
     * Crear una nueva póliza.
     */
    public function createPoliza(array $data): PolizaServicio
    {
        return DB::transaction(function () use ($data) {
            $condiciones = $data['condiciones_especiales'] ?? [];
            $condiciones['equipos_cliente'] = $data['equipos_cliente'] ?? [];

            $poliza = PolizaServicio::create([
                'empresa_id' => auth()->user()->empresa_id ?? 1,
                'cliente_id' => $data['cliente_id'],
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin' => $data['fecha_fin'] ?? null,
                'monto_mensual' => $data['monto_mensual'],
                'dia_cobro' => $data['dia_cobro'],
                'estado' => $data['estado'] ?? 'activa',
                'limite_mensual_tickets' => $data['limite_mensual_tickets'] ?? null,
                'notificar_exceso_limite' => $data['notificar_exceso_limite'] ?? true,
                'renovacion_automatica' => $data['renovacion_automatica'] ?? true,
                'notas' => $data['notas'] ?? null,
                'sla_horas_respuesta' => $data['sla_horas_respuesta'] ?? null,
                'condiciones_especiales' => $condiciones,
                'horas_incluidas_mensual' => $data['horas_incluidas_mensual'] ?? null,
                'costo_hora_excedente' => $data['costo_hora_excedente'] ?? null,
                'dias_alerta_vencimiento' => $data['dias_alerta_vencimiento'] ?? 30,
                'mantenimiento_frecuencia_meses' => $data['mantenimiento_frecuencia_meses'] ?? null,
                'proximo_mantenimiento_at' => $data['proximo_mantenimiento_at'] ?? null,
                'generar_cita_automatica' => $data['generar_cita_automatica'] ?? false,
                'visitas_sitio_mensuales' => $data['visitas_sitio_mensuales'] ?? null,
                'costo_visita_sitio_extra' => $data['costo_visita_sitio_extra'] ?? null,
                'costo_ticket_extra' => $data['costo_ticket_extra'] ?? null,
            ]);

            if (isset($data['servicios']) && is_array($data['servicios'])) {
                foreach ($data['servicios'] as $item) {
                    $poliza->servicios()->attach($item['id'], [
                        'cantidad' => $item['cantidad'] ?? 1,
                        'precio_especial' => $item['precio_especial'] ?? null,
                    ]);
                }
            }

            PolizaAuditLog::log($poliza, 'created', null, $poliza->toArray());

            return $poliza;
        });
    }

    /**
     * Actualizar una póliza existente.
     */
    public function updatePoliza(PolizaServicio $poliza, array $data): PolizaServicio
    {
        return DB::transaction(function () use ($poliza, $data) {
            $oldValues = $poliza->toArray();

            $condiciones = $data['condiciones_especiales'] ?? [];
            $condiciones['equipos_cliente'] = $data['equipos_cliente'] ?? [];

            $poliza->update(array_merge($data, [
                'condiciones_especiales' => $condiciones,
            ]));

            if (isset($data['servicios'])) {
                $syncData = [];
                foreach ($data['servicios'] as $item) {
                    $syncData[$item['id']] = [
                        'cantidad' => $item['cantidad'] ?? 1,
                        'precio_especial' => $item['precio_especial'] ?? null,
                    ];
                }
                $poliza->servicios()->sync($syncData);
            }

            PolizaAuditLog::log($poliza, 'updated', $oldValues, $poliza->fresh()->toArray());

            return $poliza;
        });
    }

    /**
     * Obtener estadísticas para el dashboard.
     */
    public function getDashboardStats(): array
    {
        // KPIs Financieros Premium
        $ingresosMensuales = PolizaServicio::activa()->sum('monto_mensual');

        // Cobros pendientes de pólizas (deuda activa)
        $cobrosPendientes = \App\Models\CuentasPorCobrar::where('cobrable_type', PolizaServicio::class)
            ->whereIn('estado', ['pendiente', 'vencido'])
            ->sum('monto_total');

        // Polizas con cobros vencidos (bloquear soporte)
        $polizasConDeuda = PolizaServicio::activa()
            ->whereHas('cuentasPorCobrar', function ($q) {
                $q->where('estado', 'vencido');
            })
            ->count();

        // Ingresos potenciales por excedentes de horas
        $ingresosExcedentes = PolizaServicio::activa()
            ->whereNotNull('horas_incluidas_mensual')
            ->whereNotNull('costo_hora_excedente')
            ->where('horas_consumidas_mes', '>', DB::raw('horas_incluidas_mensual'))
            ->get()
            ->sum(function ($p) {
                $exceso = $p->horas_consumidas_mes - $p->horas_incluidas_mensual;
                return $exceso * $p->costo_hora_excedente;
            });

        // Pre-cargar el conteo de tickets del mes actual
        $polizasActivas = PolizaServicio::activa()
            ->withCount([
                'tickets as tickets_count' => function ($query) {
                    $query->where('tipo_servicio', '!=', 'costo')
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->whereHas('categoria', function ($q) {
                            $q->where('consume_poliza', true);
                        });
                }
            ])
            ->get();

        return [
            'total_activas' => $polizasActivas->count(),
            'total_inactivas' => PolizaServicio::where('estado', '!=', 'activa')->count(),
            'ingresos_mensuales' => $ingresosMensuales,
            'ingresos_anuales_proyectados' => $ingresosMensuales * 12,
            'cobros_pendientes' => $cobrosPendientes,
            'polizas_con_deuda' => $polizasConDeuda,
            'tickets_este_mes' => $polizasActivas->sum('tickets_count'),
            'ingresos_excedentes_potenciales' => $ingresosExcedentes,
        ];
    }
}
