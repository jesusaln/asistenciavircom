<?php

namespace App\Services\Panel;

use App\Models\CuentasPorPagar;
use App\Models\CuentasPorCobrar;
use App\Models\PagoPrestamo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PanelAlertsService
{
    public function getCuentasPorPagarAlerts(): array
    {
        return Cache::remember("panel_cuentas_por_pagar_alerts", 300, function () {
            $now = Carbon::now();

            try {
                $vencidas = CuentasPorPagar::with("compra.proveedor")
                    ->whereIn("estado", ["pendiente", "parcial"])
                    ->where("fecha_vencimiento", "<", $now)
                    ->orderBy("fecha_vencimiento")
                    ->limit(10)
                    ->get();

                $semana = CuentasPorPagar::with("compra.proveedor")
                    ->whereIn("estado", ["pendiente", "parcial"])
                    ->whereBetween("fecha_vencimiento", [$now, $now->copy()->addDays(7)])
                    ->orderBy("fecha_vencimiento")
                    ->limit(10)
                    ->get();

                $quincena = CuentasPorPagar::with("compra.proveedor")
                    ->whereIn("estado", ["pendiente", "parcial"])
                    ->whereBetween("fecha_vencimiento", [$now->copy()->addDays(8), $now->copy()->addDays(15)])
                    ->orderBy("fecha_vencimiento")
                    ->limit(10)
                    ->get();

                $mes = CuentasPorPagar::with("compra.proveedor")
                    ->whereIn("estado", ["pendiente", "parcial"])
                    ->whereBetween("fecha_vencimiento", [$now->copy()->addDays(16), $now->copy()->addDays(30)])
                    ->orderBy("fecha_vencimiento")
                    ->limit(10)
                    ->get();

                return [
                    "vencidas" => $this->formatCuentasPagar($vencidas, $now),
                    "vencidas_count" => $vencidas->count(),
                    "semana" => $this->formatCuentasPagar($semana, $now),
                    "semana_count" => $semana->count(),
                    "quincena" => $this->formatCuentasPagar($quincena, $now),
                    "quincena_count" => $quincena->count(),
                    "mes" => $this->formatCuentasPagar($mes, $now),
                    "mes_count" => $mes->count(),
                ];
            } catch (\Exception $e) {
                \Log::error("Error loading accounts payable alerts: " . $e->getMessage());
                return [
                    "vencidas" => [],
                    "vencidas_count" => 0,
                    "semana" => [],
                    "semana_count" => 0,
                    "quincena" => [],
                    "quincena_count" => 0,
                    "mes" => [],
                    "mes_count" => 0,
                ];
            }
        });
    }

    private function formatCuentasPagar($cuentas, $now): array
    {
        return $cuentas->map(function ($cuenta) use ($now) {
            $diasVencimiento = Carbon::parse($cuenta->fecha_vencimiento)->diffInDays($now, false);
            return [
                "id" => $cuenta->id,
                "compra_id" => $cuenta->compra_id,
                "numero" => $cuenta->compra->numero_compra ?? "N/A",
                "proveedor" => $cuenta->compra->proveedor->nombre_razon_social ?? "Sin proveedor",
                "monto_pendiente" => $cuenta->monto_pendiente,
                "fecha_vencimiento" => Carbon::parse($cuenta->fecha_vencimiento)->format("d/m/Y"),
                "dias_vencimiento" => $diasVencimiento,
                "vencida" => $diasVencimiento > 0,
            ];
        })->toArray();
    }

    public function getCuentasPorCobrarAlerts(): array
    {
        return Cache::remember("panel_cuentas_por_cobrar_alerts", 300, function () {
            $now = Carbon::now();

            try {
                // Usamos cobrable.cliente para manejar Venta y Renta polimórficamente
                $vencidas = CuentasPorCobrar::with(["cobrable.cliente", "cliente"])
                    ->where("estado", "pendiente")
                    ->where("fecha_vencimiento", "<", $now)
                    ->orderBy("fecha_vencimiento")
                    ->limit(10)
                    ->get();

                $semana = CuentasPorCobrar::with(["cobrable.cliente", "cliente"])
                    ->where("estado", "pendiente")
                    ->whereBetween("fecha_vencimiento", [$now, $now->copy()->addDays(7)])
                    ->orderBy("fecha_vencimiento")
                    ->limit(10)
                    ->get();

                $quincena = CuentasPorCobrar::with(["cobrable.cliente", "cliente"])
                    ->where("estado", "pendiente")
                    ->whereBetween("fecha_vencimiento", [$now->copy()->addDays(8), $now->copy()->addDays(15)])
                    ->orderBy("fecha_vencimiento")
                    ->limit(10)
                    ->get();

                $mes = CuentasPorCobrar::with(["cobrable.cliente", "cliente"])
                    ->where("estado", "pendiente")
                    ->whereBetween("fecha_vencimiento", [$now->copy()->addDays(16), $now->copy()->addDays(30)])
                    ->orderBy("fecha_vencimiento")
                    ->limit(10)
                    ->get();

                return [
                    "vencidas" => $this->formatCuentasCobrar($vencidas, $now),
                    "vencidas_count" => $vencidas->count(),
                    "semana" => $this->formatCuentasCobrar($semana, $now),
                    "semana_count" => $semana->count(),
                    "quincena" => $this->formatCuentasCobrar($quincena, $now),
                    "quincena_count" => $quincena->count(),
                    "mes" => $this->formatCuentasCobrar($mes, $now),
                    "mes_count" => $mes->count(),
                ];
            } catch (\Exception $e) {
                \Log::error("Error loading accounts receivable alerts: " . $e->getMessage());

                return [
                    "vencidas" => [],
                    "vencidas_count" => 0,
                    "semana" => [],
                    "semana_count" => 0,
                    "quincena" => [],
                    "quincena_count" => 0,
                    "mes" => [],
                    "mes_count" => 0,
                ];
            }
        });
    }

    private function formatCuentasCobrar($cuentas, $now): array
    {
        return $cuentas->map(function ($cuenta) use ($now) {
            $diasVencimiento = Carbon::parse($cuenta->fecha_vencimiento)->diffInDays($now, false);

            // Determinar origen y detalles
            $origen = $cuenta->cobrable;
            $numero = "N/A";
            $cliente = "Sin cliente";
            $link_id = null;

            if ($origen) {
                // Si ya tenemos el cliente directamente cargado, lo usamos
                if ($cuenta->relationLoaded('cliente') && $cuenta->cliente) {
                    $cliente = $cuenta->cliente->nombre_razon_social;
                } elseif (isset($origen->cliente)) {
                    $cliente = $origen->cliente->nombre_razon_social ?? "Sin cliente";
                }

                // Determinar número/folio y link
                if (str_contains($cuenta->cobrable_type, 'Venta') || $cuenta->cobrable_type === 'venta') {
                    $numero = $origen->numero_venta ?? $origen->folio ?? $origen->id;
                    $link_id = $origen->id;
                } elseif (str_contains($cuenta->cobrable_type, 'Renta') || $cuenta->cobrable_type === 'renta') {
                    $numero = $origen->numero_contrato ?? $origen->id;
                    $link_id = $origen->id;
                } elseif (str_contains($cuenta->cobrable_type, 'Poliza') || $cuenta->cobrable_type === 'poliza_servicio') {
                    $numero = $origen->folio ?? $origen->id;
                    $link_id = $origen->id;
                }
            } else if ($cuenta->cliente) {
                // Fallback si no hay cobrable pero hay cliente
                $cliente = $cuenta->cliente->nombre_razon_social;
            }

            return [
                "id" => $cuenta->id,
                "venta_id" => (isset($cuenta->cobrable_type) && $cuenta->cobrable_type === 'App\\Models\\Venta') ? $link_id : null,
                "cobrable_type" => $cuenta->cobrable_type,
                "cobrable_id" => $cuenta->cobrable_id,
                "numero" => $numero,
                "cliente" => $cliente,
                "monto_pendiente" => $cuenta->monto_pendiente,
                "fecha_vencimiento" => Carbon::parse($cuenta->fecha_vencimiento)->format("d/m/Y"),
                "dias_vencimiento" => $diasVencimiento,
                "vencida" => $diasVencimiento > 0,
            ];
        })->toArray();
    }

    public function getPrestamosAlerts(): array
    {
        return Cache::remember("panel_prestamos_alerts", 300, function () {
            $now = Carbon::now();

            try {
                // Pagos vencidos (fecha programada < hoy y no pagados completamente)
                $vencidas = PagoPrestamo::with("prestamo.cliente")
                    ->where("estado", "pendiente")
                    ->where("fecha_programada", "<", $now)
                    ->orderBy("fecha_programada")
                    ->limit(10)
                    ->get();

                // Pagos próxima semana
                $semana = PagoPrestamo::with("prestamo.cliente")
                    ->where("estado", "pendiente")
                    ->whereBetween("fecha_programada", [$now, $now->copy()->addDays(7)])
                    ->orderBy("fecha_programada")
                    ->limit(10)
                    ->get();

                // Pagos próxima quincena (8-15 días)
                $quincena = PagoPrestamo::with("prestamo.cliente")
                    ->where("estado", "pendiente")
                    ->whereBetween("fecha_programada", [$now->copy()->addDays(8), $now->copy()->addDays(15)])
                    ->orderBy("fecha_programada")
                    ->limit(10)
                    ->get();

                // Pagos próximo mes (16-30 días)
                $mes = PagoPrestamo::with("prestamo.cliente")
                    ->where("estado", "pendiente")
                    ->whereBetween("fecha_programada", [$now->copy()->addDays(16), $now->copy()->addDays(30)])
                    ->orderBy("fecha_programada")
                    ->limit(10)
                    ->get();

                return [
                    "vencidas" => $this->formatPrestamos($vencidas, $now),
                    "vencidas_count" => $vencidas->count(),
                    "semana" => $this->formatPrestamos($semana, $now),
                    "semana_count" => $semana->count(),
                    "quincena" => $this->formatPrestamos($quincena, $now),
                    "quincena_count" => $quincena->count(),
                    "mes" => $this->formatPrestamos($mes, $now),
                    "mes_count" => $mes->count(),
                ];
            } catch (\Exception $e) {
                \Log::error("Error loading loan alerts: " . $e->getMessage());
                return [
                    "vencidas" => [],
                    "vencidas_count" => 0,
                    "semana" => [],
                    "semana_count" => 0,
                    "quincena" => [],
                    "quincena_count" => 0,
                    "mes" => [],
                    "mes_count" => 0,
                ];
            }
        });
    }

    private function formatPrestamos($pagos, $now): array
    {
        return $pagos->map(function ($pago) use ($now) {
            $diasVencimiento = Carbon::parse($pago->fecha_programada)->diffInDays($now, false);
            return [
                "id" => $pago->id,
                "prestamo_id" => $pago->prestamo_id,
                "numero_pago" => $pago->numero_pago,
                "cliente" => $pago->prestamo->cliente->nombre_razon_social ?? "Sin cliente",
                "monto_pendiente" => $pago->monto_programado - $pago->monto_pagado,
                "fecha_vencimiento" => Carbon::parse($pago->fecha_programada)->format("d/m/Y"),
                "dias_vencimiento" => $diasVencimiento,
                "vencida" => $diasVencimiento > 0,
            ];
        })->toArray();
    }
}
