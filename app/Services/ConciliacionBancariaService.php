<?php

namespace App\Services;

use App\Models\CuentasPorCobrar;
use App\Models\CuentasPorPagar;
use App\Models\MovimientoBancario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de conciliación bancaria automática
 */
class ConciliacionBancariaService
{
    /**
     * Tolerancia de monto para matches (porcentaje)
     */
    protected float $toleranciaMonto = 0.01; // 1%

    /**
     * Tolerancia de días para matches por fecha
     */
    protected int $toleranciaDias = 5;

    /**
     * Buscar sugerencias de conciliación para un movimiento
     */
    public function buscarSugerencias(MovimientoBancario $movimiento): array
    {
        $sugerencias = [];

        if ($movimiento->esDeposito()) {
            // Depósito = Cobro de cliente (CXC)
            $sugerencias = $this->buscarCuentasPorCobrar($movimiento);
        } else {
            // Retiro = Pago a proveedor (CXP)
            $sugerencias = $this->buscarCuentasPorPagar($movimiento);
        }

        // Ordenar por score de coincidencia
        usort($sugerencias, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($sugerencias, 0, 5); // Top 5 sugerencias
    }

    /**
     * Buscar sugerencias en Cuentas por Cobrar
     */
    protected function buscarCuentasPorCobrar(MovimientoBancario $movimiento): array
    {
        $montoAbsoluto = abs($movimiento->monto);
        $sugerencias = [];

        // Buscar cuentas pendientes
        $cuentas = CuentasPorCobrar::with('venta.cliente')
            ->where('estado', 'pendiente')
            ->where('monto_pendiente', '>', 0)
            ->get();

        foreach ($cuentas as $cuenta) {
            $score = $this->calcularScore($movimiento, $cuenta, 'CXC');
            
            if ($score > 0) {
                $sugerencias[] = [
                    'tipo' => 'CXC',
                    'cuenta_id' => $cuenta->id,
                    'numero' => $cuenta->venta->numero_venta ?? "CXC-{$cuenta->id}",
                    'entidad' => $cuenta->venta->cliente->nombre_razon_social ?? 'Sin cliente',
                    'monto_pendiente' => $cuenta->monto_pendiente,
                    'fecha_vencimiento' => $cuenta->fecha_vencimiento?->format('d/m/Y'),
                    'score' => $score,
                    'razon' => $this->getRazonMatch($score),
                    'diferencia_monto' => abs($montoAbsoluto - $cuenta->monto_pendiente),
                ];
            }
        }

        return $sugerencias;
    }

    /**
     * Buscar sugerencias en Cuentas por Pagar
     */
    protected function buscarCuentasPorPagar(MovimientoBancario $movimiento): array
    {
        $montoAbsoluto = abs($movimiento->monto);
        $sugerencias = [];

        // Buscar cuentas pendientes
        $cuentas = CuentasPorPagar::with('compra.proveedor')
            ->where('estado', 'pendiente')
            ->where('monto_pendiente', '>', 0)
            ->get();

        foreach ($cuentas as $cuenta) {
            $score = $this->calcularScore($movimiento, $cuenta, 'CXP');
            
            if ($score > 0) {
                $sugerencias[] = [
                    'tipo' => 'CXP',
                    'cuenta_id' => $cuenta->id,
                    'numero' => $cuenta->compra->numero_compra ?? "CXP-{$cuenta->id}",
                    'entidad' => $cuenta->compra->proveedor->nombre_razon_social ?? 'Sin proveedor',
                    'monto_pendiente' => $cuenta->monto_pendiente,
                    'fecha_vencimiento' => $cuenta->fecha_vencimiento?->format('d/m/Y'),
                    'score' => $score,
                    'razon' => $this->getRazonMatch($score),
                    'diferencia_monto' => abs($montoAbsoluto - $cuenta->monto_pendiente),
                ];
            }
        }

        return $sugerencias;
    }

    /**
     * Calcular score de coincidencia (0-100)
     */
    protected function calcularScore(MovimientoBancario $movimiento, $cuenta, string $tipo): int
    {
        $score = 0;
        $montoMovimiento = abs($movimiento->monto);
        $montoCuenta = $cuenta->monto_pendiente;

        // 1. Match exacto de monto (50 puntos)
        if (abs($montoMovimiento - $montoCuenta) < 0.01) {
            $score += 50;
        }
        // Match con tolerancia (30 puntos)
        elseif ($this->dentroDeTolerancia($montoMovimiento, $montoCuenta)) {
            $score += 30;
        }
        // Montos muy diferentes = no match
        else {
            return 0;
        }

        // 2. Buscar referencia en concepto (25 puntos)
        $concepto = strtolower($movimiento->concepto ?? '');
        
        // Buscar número de venta/compra en el concepto
        if ($tipo === 'CXC' && $cuenta->venta) {
            $numeroVenta = strtolower($cuenta->venta->numero_venta ?? '');
            if ($numeroVenta && str_contains($concepto, $numeroVenta)) {
                $score += 25;
            }
            // Buscar nombre del cliente
            $cliente = strtolower($cuenta->venta->cliente->nombre_razon_social ?? '');
            if ($cliente && str_contains($concepto, substr($cliente, 0, 10))) {
                $score += 15;
            }
        } elseif ($tipo === 'CXP' && $cuenta->compra) {
            $numeroCompra = strtolower($cuenta->compra->numero_compra ?? '');
            if ($numeroCompra && str_contains($concepto, $numeroCompra)) {
                $score += 25;
            }
            // Buscar nombre del proveedor
            $proveedor = strtolower($cuenta->compra->proveedor->nombre_razon_social ?? '');
            if ($proveedor && str_contains($concepto, substr($proveedor, 0, 10))) {
                $score += 15;
            }
        }

        // 3. Proximidad de fecha (15 puntos)
        if ($cuenta->fecha_vencimiento) {
            $diasDiferencia = abs($movimiento->fecha->diffInDays($cuenta->fecha_vencimiento));
            if ($diasDiferencia <= $this->toleranciaDias) {
                $score += max(0, 15 - ($diasDiferencia * 2));
            }
        }

        // 4. Fecha de creación cercana (10 puntos)
        $diasDesdeCreacion = abs($movimiento->fecha->diffInDays($cuenta->created_at));
        if ($diasDesdeCreacion <= 30) {
            $score += max(0, 10 - ($diasDesdeCreacion / 3));
        }

        return min(100, $score);
    }

    /**
     * Verificar si dos montos están dentro de la tolerancia
     */
    protected function dentroDeTolerancia(float $monto1, float $monto2): bool
    {
        if ($monto2 == 0) return $monto1 == 0;
        
        $diferenciaPorcentual = abs($monto1 - $monto2) / $monto2;
        return $diferenciaPorcentual <= $this->toleranciaMonto;
    }

    /**
     * Obtener razón del match basado en el score
     */
    protected function getRazonMatch(int $score): string
    {
        if ($score >= 75) return 'Match exacto de monto y referencia';
        if ($score >= 50) return 'Match exacto de monto';
        if ($score >= 30) return 'Monto similar';
        return 'Posible coincidencia';
    }

    /**
     * Ejecutar conciliación
     */
    public function conciliar(MovimientoBancario $movimiento, string $tipoCuenta, int $cuentaId): bool
    {
        $cuenta = match($tipoCuenta) {
            'CXC' => CuentasPorCobrar::find($cuentaId),
            'CXP' => CuentasPorPagar::find($cuentaId),
            default => null,
        };

        if (!$cuenta) {
            throw new \Exception("Cuenta no encontrada");
        }

        return $movimiento->conciliar($cuenta);
    }

    /**
     * Conciliación automática masiva
     */
    public function conciliacionAutomatica(Collection $movimientos, int $scoreMinimo = 75): array
    {
        $conciliados = 0;
        $noEncontrados = 0;
        $errores = [];

        foreach ($movimientos as $movimiento) {
            if ($movimiento->estado !== 'pendiente') continue;

            $sugerencias = $this->buscarSugerencias($movimiento);
            
            if (!empty($sugerencias) && $sugerencias[0]['score'] >= $scoreMinimo) {
                try {
                    $mejor = $sugerencias[0];
                    $this->conciliar($movimiento, $mejor['tipo'], $mejor['cuenta_id']);
                    $conciliados++;
                } catch (\Exception $e) {
                    $errores[] = "Movimiento {$movimiento->id}: " . $e->getMessage();
                }
            } else {
                $noEncontrados++;
            }
        }

        return [
            'conciliados' => $conciliados,
            'sin_match' => $noEncontrados,
            'errores' => $errores,
        ];
    }

    /**
     * Obtener resumen de movimientos pendientes
     */
    public function getResumenPendientes(): array
    {
        $pendientes = MovimientoBancario::pendientes()->get();

        return [
            'total' => $pendientes->count(),
            'depositos' => $pendientes->where('tipo', 'deposito')->count(),
            'retiros' => $pendientes->where('tipo', 'retiro')->count(),
            'monto_depositos' => $pendientes->where('tipo', 'deposito')->sum('monto'),
            'monto_retiros' => abs($pendientes->where('tipo', 'retiro')->sum('monto')),
        ];
    }
}
