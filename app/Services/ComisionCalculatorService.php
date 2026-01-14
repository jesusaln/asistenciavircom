<?php

namespace App\Services;

use App\Models\Venta;
use App\Models\User;
use App\Models\Tecnico;
use App\Models\PagoComision;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ComisionCalculatorService
{
    /**
     * Obtener todos los vendedores (Users + Tecnicos) que tienen ventas
     */
    public function obtenerVendedoresConVentas(): Collection
    {
        // Obtener IDs de usuarios con ventas
        // Ahora todas las ventas deberían apuntar a User::class tras la migración
        $userIds = Venta::where('vendedor_type', User::class)
            ->whereNotNull('vendedor_id')
            ->distinct()
            ->pluck('vendedor_id');

        $vendedores = collect();

        // Agregar usuarios
        User::whereIn('id', $userIds)->get()->each(function ($user) use ($vendedores) {
            $vendedores->push([
                'id' => $user->id,
                'type' => User::class,
                'type_label' => $user->es_tecnico ? 'Técnico' : 'Vendedor',
                'nombre' => $user->name,
                'email' => $user->email,
            ]);
        });

        return $vendedores;
    }

    /**
     * Calcular comisiones de un vendedor para un periodo
     */
    public function calcularComisionesVendedor(string $vendedorType, int $vendedorId, Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $ventas = Venta::with(['cliente', 'productos', 'servicios'])
            ->where('vendedor_type', $vendedorType)
            ->where('vendedor_id', $vendedorId)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->whereIn('estado', ['completada', 'pagada'])
            ->orderBy('fecha', 'asc')
            ->get();

        $totalComision = 0;
        $detalles = [];

        foreach ($ventas as $venta) {
            $comisionVenta = $this->calcularComisionVenta($venta);
            $totalComision += $comisionVenta['total'];

            $detalles[] = [
                'venta_id' => $venta->id,
                'numero_venta' => $venta->numero_venta,
                'fecha' => $venta->fecha->format('Y-m-d'),
                'cliente' => $venta->cliente?->nombre ?? 'Sin cliente',
                'total_venta' => (float) $venta->total,
                'comision_productos' => $comisionVenta['productos'],
                'comision_servicios' => $comisionVenta['servicios'],
                'comision_total' => $comisionVenta['total'],
            ];
        }

        return [
            'vendedor_type' => $vendedorType,
            'vendedor_id' => $vendedorId,
            'periodo_inicio' => $fechaInicio->format('Y-m-d'),
            'periodo_fin' => $fechaFin->format('Y-m-d'),
            'total_comision' => round($totalComision, 2),
            'num_ventas' => count($ventas),
            'total_ventas' => $ventas->sum('total'),
            'detalles' => $detalles,
        ];
    }

    /**
     * Calcular comisión de una venta específica
     */
    public function calcularComisionVenta(Venta $venta): array
    {
        $comisionProductos = 0;
        $comisionServicios = 0;
        $vendedor = $venta->vendedor;

        // Comisión de productos
        foreach ($venta->productos as $producto) {
            $pivot = $producto->pivot;
            $precioVenta = $pivot->precio * (1 - ($pivot->descuento ?? 0) / 100);
            $costo = $pivot->costo_unitario ?? $producto->precio_compra ?? 0;
            $gananciaBase = ($precioVenta - $costo) * $pivot->cantidad;

            // Comisión configurada en el producto (% de la ganancia)
            $comisionProductos += $gananciaBase * (($producto->comision_vendedor ?? 0) / 100);

            // Si es técnico, agregar margen adicional
            // El vendedor siempre es User ahora, verificar flag es_tecnico
            if ($vendedor && $vendedor->es_tecnico) {
                $comisionProductos += $gananciaBase * (($vendedor->margen_venta_productos ?? 0) / 100);
            }
        }

        // Comisión de servicios
        foreach ($venta->servicios as $servicio) {
            $pivot = $servicio->pivot;

            // Comisión configurada en el servicio (monto fijo por unidad)
            $comisionServicios += ($servicio->comision_vendedor ?? 0) * $pivot->cantidad;

            // Si es técnico, agregar márgenes adicionales
            if ($vendedor && $vendedor->es_tecnico) {
                $precioVenta = $pivot->precio * (1 - ($pivot->descuento ?? 0) / 100);
                $comisionServicios += $precioVenta * (($vendedor->margen_venta_servicios ?? 0) / 100) * $pivot->cantidad;

                // Comisión extra por instalación
                if ($servicio->es_instalacion ?? false) {
                    $comisionServicios += ($vendedor->comision_instalacion ?? 0) * $pivot->cantidad;
                }
            }
        }

        return [
            'productos' => round($comisionProductos, 2),
            'servicios' => round($comisionServicios, 2),
            'total' => round($comisionProductos + $comisionServicios, 2),
        ];
    }

    /**
     * Obtener resumen de comisiones de la semana actual
     */
    public function obtenerResumenSemanal(): array
    {
        $inicioSemana = Carbon::now()->startOfWeek();
        $finSemana = Carbon::now()->endOfWeek();

        return $this->obtenerResumenPeriodo($inicioSemana, $finSemana);
    }

    /**
     * Obtener resumen de comisiones del mes actual
     */
    public function obtenerResumenMensual(): array
    {
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();

        return $this->obtenerResumenPeriodo($inicioMes, $finMes);
    }

    /**
     * Obtener resumen de comisiones para un periodo
     */
    public function obtenerResumenPeriodo(Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $vendedores = $this->obtenerVendedoresConVentas();
        $resumenVendedores = [];
        $totalComisiones = 0;
        $totalPagado = 0;
        $totalPendiente = 0;

        foreach ($vendedores as $vendedor) {
            $calculo = $this->calcularComisionesVendedor(
                $vendedor['type'],
                $vendedor['id'],
                $fechaInicio,
                $fechaFin
            );

            // Verificar si ya hay un pago registrado para este periodo
            $pagoExistente = PagoComision::where('vendedor_type', $vendedor['type'])
                ->where('vendedor_id', $vendedor['id'])
                ->where('periodo_inicio', $fechaInicio->format('Y-m-d'))
                ->where('periodo_fin', $fechaFin->format('Y-m-d'))
                ->first();

            $estado = 'pendiente';
            $montoPagado = 0;

            if ($pagoExistente) {
                $estado = $pagoExistente->estado;
                $montoPagado = (float) $pagoExistente->monto_pagado;
            }

            if ($calculo['total_comision'] > 0) {
                $resumenVendedores[] = [
                    'id' => $vendedor['id'],
                    'type' => $vendedor['type'],
                    'type_label' => $vendedor['type_label'],
                    'nombre' => $vendedor['nombre'],
                    'num_ventas' => $calculo['num_ventas'],
                    'total_ventas' => $calculo['total_ventas'],
                    'comision' => $calculo['total_comision'],
                    'pagado' => $montoPagado,
                    'pendiente' => $calculo['total_comision'] - $montoPagado,
                    'estado' => $estado,
                    'pago_id' => $pagoExistente?->id,
                ];

                $totalComisiones += $calculo['total_comision'];
                $totalPagado += $montoPagado;
                $totalPendiente += ($calculo['total_comision'] - $montoPagado);
            }
        }

        // Ordenar por comisión descendente
        usort($resumenVendedores, fn($a, $b) => $b['comision'] <=> $a['comision']);

        return [
            'periodo_inicio' => $fechaInicio->format('Y-m-d'),
            'periodo_fin' => $fechaFin->format('Y-m-d'),
            'periodo_label' => $fechaInicio->format('d M') . ' - ' . $fechaFin->format('d M Y'),
            'total_comisiones' => round($totalComisiones, 2),
            'total_pagado' => round($totalPagado, 2),
            'total_pendiente' => round($totalPendiente, 2),
            'vendedores' => $resumenVendedores,
        ];
    }

    /**
     * Crear registro de pago de comisión
     */
    public function crearPagoComision(array $data): PagoComision
    {
        $calculo = $this->calcularComisionesVendedor(
            $data['vendedor_type'],
            $data['vendedor_id'],
            Carbon::parse($data['periodo_inicio']),
            Carbon::parse($data['periodo_fin'])
        );

        return PagoComision::create([
            'vendedor_type' => $data['vendedor_type'],
            'vendedor_id' => $data['vendedor_id'],
            'periodo_inicio' => $data['periodo_inicio'],
            'periodo_fin' => $data['periodo_fin'],
            'monto_comision' => $calculo['total_comision'],
            'monto_pagado' => $data['monto_pagado'] ?? $calculo['total_comision'],
            'estado' => ($data['monto_pagado'] ?? $calculo['total_comision']) >= $calculo['total_comision'] ? 'pagado' : 'parcial',
            'fecha_pago' => $data['fecha_pago'] ?? now(),
            'metodo_pago' => $data['metodo_pago'] ?? null,
            'referencia_pago' => $data['referencia_pago'] ?? null,
            'cuenta_bancaria_id' => $data['cuenta_bancaria_id'] ?? null,
            'detalles_ventas' => $calculo['detalles'],
            'num_ventas' => $calculo['num_ventas'],
            'total_ventas' => $calculo['total_ventas'],
            'notas' => $data['notas'] ?? null,
            'pagado_por' => auth()->id(),
            'created_by' => auth()->id(),
        ]);
    }
}
