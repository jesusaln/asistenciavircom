<?php

namespace App\Services;

use App\Mail\AlertaMantenimientoMail;
use App\Models\Mantenimiento;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AlertaMantenimientoService
{
    // Verifica y envía alertas por fecha y por km
    public function verificarYEnviarAlertas(int $dias = 30, int $km = 500)
    {
        $porFecha = Mantenimiento::conAlertasPendientes($dias)
            ->where('estado', '!=', Mantenimiento::ESTADO_COMPLETADO)
            ->with('carro')
            ->get();

        $porKm = Mantenimiento::where('alerta_enviada', false)
            ->where('estado', '!=', Mantenimiento::ESTADO_COMPLETADO)
            ->whereNotNull('proximo_kilometraje')
            ->with('carro')
            ->get()
            ->filter(function ($mto) use ($km) {
                $umbral = $mto->km_anticipacion_alerta ?? $km;
                $kmRestantes = $mto->km_restantes;
                return $kmRestantes !== null && $kmRestantes <= $umbral;
            });

        $pendientes = $porFecha->concat($porKm)->unique('id');

        $enviadas = 0;
        $errores = [];

        foreach ($pendientes as $mantenimiento) {
            try {
                if (!$mantenimiento->requiere_alerta) {
                    continue;
                }

                $this->enviarAlertaMantenimiento($mantenimiento);
                $mantenimiento->marcarAlertaEnviada();
                $enviadas++;

                Log::info('Alerta de mantenimiento enviada', [
                    'mantenimiento_id' => $mantenimiento->id,
                    'carro' => optional($mantenimiento->carro)->marca . ' ' . optional($mantenimiento->carro)->modelo,
                    'tipo' => $mantenimiento->tipo,
                    'dias_restantes' => $mantenimiento->dias_restantes,
                    'km_restantes' => $mantenimiento->km_restantes,
                ]);
            } catch (\Throwable $e) {
                $errores[] = [
                    'mantenimiento_id' => $mantenimiento->id,
                    'error' => $e->getMessage(),
                ];
                Log::error('Error al enviar alerta de mantenimiento', [
                    'mantenimiento_id' => $mantenimiento->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'alertas_enviadas' => $enviadas,
            'errores' => $errores,
            'total_procesados' => $pendientes->count(),
        ];
    }

    // Arma datos y envía correo, registra recordatorio
    private function enviarAlertaMantenimiento(Mantenimiento $mantenimiento): void
    {
        $carro = $mantenimiento->carro;
        $diasRestantes = $mantenimiento->dias_restantes;
        $tipoAlerta = $this->determinarTipoAlerta($diasRestantes, $mantenimiento->prioridad);

        $datosAlerta = [
            'carro' => $carro,
            'mantenimiento' => $mantenimiento,
            'dias_restantes' => $diasRestantes,
            'km_restantes' => $mantenimiento->km_restantes,
            'tipo_alerta' => $tipoAlerta,
            'fecha_proximo' => $mantenimiento->proximo_mantenimiento,
            'prioridad' => $mantenimiento->prioridad,
        ];

        $to = config('mail.alertas_mantenimiento_to', env('ALERTAS_MANTENIMIENTO_TO', 'jesuslopeznoriega@hotmail.com'));
        Mail::to($to)->queue(new AlertaMantenimientoMail($datosAlerta));

        $mantenimiento->agregarRecordatorioEnviado('email');
    }

    private function determinarTipoAlerta($diasRestantes, $prioridad)
    {
        if ($diasRestantes === null) {
            return 'informativa';
        }
        if ($diasRestantes < 0) return 'vencido';
        if ($diasRestantes <= 3 || $prioridad === Mantenimiento::PRIORIDAD_CRITICA) return 'critica';
        if ($diasRestantes <= 7 || $prioridad === Mantenimiento::PRIORIDAD_ALTA) return 'urgente';
        if ($diasRestantes <= 15) return 'moderada';
        return 'informativa';
    }

    public function generarReporteAlertas()
    {
        $estadisticas = Mantenimiento::getEstadisticasAlertas();
        return [
            'estadisticas' => $estadisticas,
            'alertas_por_prioridad' => Mantenimiento::select('prioridad')
                ->selectRaw('COUNT(*) as total')
                ->where('alerta_enviada', false)
                ->where('proximo_mantenimiento', '<=', now()->addDays(30))
                ->groupBy('prioridad')
                ->get(),
            'alertas_por_tipo' => Mantenimiento::select('tipo')
                ->selectRaw('COUNT(*) as total')
                ->where('alerta_enviada', false)
                ->where('proximo_mantenimiento', '<=', now()->addDays(30))
                ->groupBy('tipo')
                ->get(),
            'proximos_mantenimientos' => Mantenimiento::with('carro')
                ->where('proximo_mantenimiento', '>=', now())
                ->where('proximo_mantenimiento', '<=', now()->addDays(30))
                ->orderBy('proximo_mantenimiento', 'asc')
                ->limit(10)
                ->get(),
        ];
    }

    public function programarRecordatoriosAutomaticos()
    {
        $mantenimientos = Mantenimiento::where('alerta_enviada', true)
            ->where('estado', '!=', Mantenimiento::ESTADO_COMPLETADO)
            ->where('proximo_mantenimiento', '>', now())
            ->get();

        $enviados = 0;
        foreach ($mantenimientos as $mantenimiento) {
            $count = $this->contarRecordatoriosEnviados($mantenimiento);
            if ($count >= 3) continue;

            $diasDesdeUltimo = $this->getDiasDesdeUltimoRecordatorio($mantenimiento);
            if ($diasDesdeUltimo >= $mantenimiento->frecuencia_recordatorio_dias) {
                $this->enviarRecordatorio($mantenimiento);
                $mantenimiento->agregarRecordatorioEnviado('recordatorio_automatico');
                $enviados++;
            }
        }
        return $enviados;
    }

    private function contarRecordatoriosEnviados(Mantenimiento $mantenimiento)
    {
        $recordatorios = $mantenimiento->recordatorios_enviados ?? [];
        return is_array($recordatorios) ? count($recordatorios) : 0;
    }

    private function getDiasDesdeUltimoRecordatorio(Mantenimiento $mantenimiento)
    {
        $recordatorios = $mantenimiento->recordatorios_enviados ?? [];
        if (empty($recordatorios)) return PHP_INT_MAX;
        $ultimo = collect($recordatorios)->sortByDesc('timestamp')->first();
        $fechaUltimo = Carbon::parse($ultimo['fecha']);
        return $fechaUltimo->diffInDays(now());
    }

    private function enviarRecordatorio(Mantenimiento $mantenimiento)
    {
        Log::info('Enviando recordatorio de mantenimiento', [
            'mantenimiento_id' => $mantenimiento->id,
            'carro' => optional($mantenimiento->carro)->marca . ' ' . optional($mantenimiento->carro)->modelo,
            'tipo' => $mantenimiento->tipo,
            'dias_restantes' => $mantenimiento->dias_restantes,
        ]);
        // Aquí podrías enviar un correo/notification real
    }
}

