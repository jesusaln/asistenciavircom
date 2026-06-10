<?php

namespace App\Services;

use App\Mail\AlertaMantenimientoMail;
use App\Models\Mantenimiento;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AlertaMantenimientoService
{
    const ESCALAMIENTO_TIER = [
        1 => 'recordatorio',     // Días 1-3 vencido → notificación estándar
        2 => 'urgente',          // Días 4-7 vencido → urgencia
        3 => 'critico',          // Más de 7 días vencido → crítico (dueño)
    ];

    public function verificarYEnviarAlertas(int $dias = 30, int $km = 500)
    {
        $porFecha = Mantenimiento::conAlertasPendientes($dias)
            ->whereNotIn('estado', [Mantenimiento::ESTADO_COMPLETADO, Mantenimiento::ESTADO_CANCELADO])
            ->with('carro')
            ->get();

        $porKm = Mantenimiento::whereNotIn('estado', [Mantenimiento::ESTADO_COMPLETADO, Mantenimiento::ESTADO_CANCELADO])
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
        $escaladas = 0;
        $errores = [];

        foreach ($pendientes as $mantenimiento) {
            try {
                $resultado = $this->procesarConEscalamiento($mantenimiento);
                if ($resultado['enviada']) {
                    $enviadas++;
                }
                if ($resultado['escalada']) {
                    $escaladas++;
                }
            } catch (\Throwable $e) {
                $errores[] = [
                    'mantenimiento_id' => $mantenimiento->id,
                    'error' => $e->getMessage(),
                ];
                Log::error('Error en alerta mantenimiento', [
                    'mantenimiento_id' => $mantenimiento->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'alertas_enviadas' => $enviadas,
            'escaladas' => $escaladas,
            'errores' => $errores,
            'total_procesados' => $pendientes->count(),
        ];
    }

    private function procesarConEscalamiento(Mantenimiento $mantenimiento): array
    {
        $enviada = false;
        $escalada = false;

        $diasVencido = 0;
        if ($mantenimiento->dias_restantes !== null && $mantenimiento->dias_restantes < 0) {
            $diasVencido = abs($mantenimiento->dias_restantes);
        }

        $nivel = $this->determinarNivelEscalamiento($diasVencido, $mantenimiento->prioridad);
        $ultimoNivel = $this->getUltimoNivelEnviado($mantenimiento);

        if ($nivel > $ultimoNivel) {
            $tipoDestinatario = self::ESCALAMIENTO_TIER[$nivel] ?? 'recordatorio';
            $this->enviarAlertaMantenimiento($mantenimiento, $tipoDestinatario);
            $mantenimiento->agregarRecordatorioEnviado('escalamiento_' . $nivel);
            $enviada = true;

            if ($nivel >= 3) {
                $mantenimiento->marcarAlertaEnviada();
                $escalada = true;
            }
        }

        return ['enviada' => $enviada, 'escalada' => $escalada];
    }

    private function determinarNivelEscalamiento(int $diasVencido, ?string $prioridad): int
    {
        if (in_array($prioridad, [Mantenimiento::PRIORIDAD_CRITICA, Mantenimiento::PRIORIDAD_ALTA])) {
            if ($diasVencido >= 3) return 3;
            if ($diasVencido >= 1) return 2;
            return 1;
        }

        if ($diasVencido >= 7) return 3;
        if ($diasVencido >= 4) return 2;
        if ($diasVencido >= 1) return 1;

        return 0;
    }

    private function getUltimoNivelEnviado(Mantenimiento $mantenimiento): int
    {
        $recordatorios = $mantenimiento->recordatorios_enviados ?? [];
        if (!is_array($recordatorios) || empty($recordatorios)) {
            return 0;
        }

        $maxNivel = 0;
        foreach ($recordatorios as $r) {
            if (isset($r['tipo']) && str_starts_with($r['tipo'], 'escalamiento_')) {
                $nivel = (int) str_replace('escalamiento_', '', $r['tipo']);
                if ($nivel > $maxNivel) {
                    $maxNivel = $nivel;
                }
            }
        }
        return $maxNivel;
    }

    private function enviarAlertaMantenimiento(Mantenimiento $mantenimiento, string $tipoDestinatario): void
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
            'tipo_destinatario' => $tipoDestinatario,
            'fecha_proximo' => $mantenimiento->proximo_mantenimiento,
            'prioridad' => $mantenimiento->prioridad,
            'nivel_escalamiento' => $tipoDestinatario,
        ];

        $to = config('mail.alertas_mantenimiento_to', config('mail.from.address'));
        Mail::to($to)->queue(new AlertaMantenimientoMail($datosAlerta));

        Log::info('Alerta mantenimiento ' . $tipoDestinatario, [
            'mantenimiento_id' => $mantenimiento->id,
            'carro' => optional($carro)->marca . ' ' . optional($carro)->modelo,
            'tipo' => $mantenimiento->tipo,
            'dias_restantes' => $diasRestantes,
            'nivel' => $tipoDestinatario,
        ]);
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
        $mantenimientos = Mantenimiento::whereNotIn('estado', [
                Mantenimiento::ESTADO_COMPLETADO,
                Mantenimiento::ESTADO_CANCELADO,
            ])
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
        Log::info('Recordatorio mantenimiento', [
            'mantenimiento_id' => $mantenimiento->id,
            'carro' => optional($mantenimiento->carro)->marca . ' ' . optional($mantenimiento->carro)->modelo,
            'tipo' => $mantenimiento->tipo,
            'dias_restantes' => $mantenimiento->dias_restantes,
        ]);
    }

    public function getMantenimientosCriticos()
    {
        return [
            'criticos' => Mantenimiento::where('prioridad', Mantenimiento::PRIORIDAD_CRITICA)
                ->whereNotIn('estado', [Mantenimiento::ESTADO_COMPLETADO, Mantenimiento::ESTADO_CANCELADO])
                ->get(),
            'vencidos' => Mantenimiento::whereNotIn('estado', [Mantenimiento::ESTADO_COMPLETADO, Mantenimiento::ESTADO_CANCELADO])
                ->where('proximo_mantenimiento', '<', now())
                ->get(),
            'proximos_3_dias' => Mantenimiento::whereNotIn('estado', [Mantenimiento::ESTADO_COMPLETADO, Mantenimiento::ESTADO_CANCELADO])
                ->whereBetween('proximo_mantenimiento', [now(), now()->addDays(3)])
                ->get(),
        ];
    }
}
