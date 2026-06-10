<?php

namespace App\Services;

use App\Models\Venta;
use App\Models\User;
use App\Models\PagoComision;
use App\Support\EmpresaResolver;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ComisionCalculatorService
{
    const SPLIT_TECNICO = 0.70;
    const SPLIT_AYUDANTE = 0.30;

    private function getSplitTecnico(): float
    {
        return config('app.comision_split_tecnico', 70) / 100;
    }

    private function getSplitAyudante(): float
    {
        return config('app.comision_split_ayudante', 30) / 100;
    }

    private function getDefaultComisionProducto(): float
    {
        return config('app.comision_default_productos', 10);
    }

    private function motivoComisionCero(array $context): ?string
    {
        $tipo = $context['tipo'] ?? '';
        $item = $context['item'] ?? null;

        if ($tipo === 'Producto' && $item) {
            if (empty($item->comision_vendedor)) return 'Producto sin % de comisión';
            if (!empty($context['gananciaBase']) && $context['gananciaBase'] <= 0) return 'Sin margen de ganancia (precio ≤ costo)';
        }
        if ($tipo === 'Servicio' && $item) {
            if (!empty($context['esTecnico']) && empty($item->tipo_comision_tecnica)) return 'Servicio sin tipo de comisión técnica';
            if (empty($context['esTecnico']) && empty($item->comision_vendedor)) return 'Servicio sin comisión fija configurada';
        }
        if ($tipo === 'Instalación' && !empty($context['esAyudanteSinSplit'])) return 'Ayudante sin split asignado';
        return 'Configuración de comisión incompleta';
    }
    /**
     * Obtener todos los vendedores (Users + Tecnicos) que tienen ventas
     */
    public function obtenerVendedoresConVentas(): Collection
    {
        // Obtener IDs de usuarios con ventas directas
        $vendedorIds = Venta::where('vendedor_type', User::class)
            ->whereNotNull('vendedor_id')
            ->distinct()
            ->pluck('vendedor_id')
            ->toArray();

        // Obtener IDs de técnicos asignados a citas de ventas (como técnicos líderes o ayudantes)
        $tecnicoIds = Venta::whereNotNull('cita_id')
            ->join('citas', 'ventas.cita_id', '=', 'citas.id')
            ->where(function ($q) {
                $q->whereNotNull('citas.tecnico_id')
                  ->orWhereNotNull('citas.ayudante_id');
            })
            ->distinct()
            ->get(['citas.tecnico_id', 'citas.ayudante_id'])
            ->flatMap(function ($item) {
                return [$item->tecnico_id, $item->ayudante_id];
            })
            ->filter()
            ->unique()
            ->toArray();

        // Combinar IDs únicos
        $userIds = array_unique(array_merge($vendedorIds, $tecnicoIds));

        $vendedores = collect();
        $usuarios = User::whereIn('id', $userIds)->get();

        foreach ($usuarios as $user) {
            $vendedores->push([
                'id' => $user->id,
                'type' => User::class,
                'type_label' => $user->es_tecnico ? 'Técnico' : 'Vendedor',
                'nombre' => $user->name,
                'email' => $user->email,
            ]);
        }

        return $vendedores;
    }

    /**
     * Calcular comisiones detalladas de un vendedor en un periodo
     */
    public function calcularComisionesVendedor(string $vendedorType, int $vendedorId, Carbon $fechaInicio, Carbon $fechaFin): array
    {
        // Convert boundaries to UTC because Venta's fecha is cast via TimezoneCast (which stores in UTC)
        $fechaInicioUtc = $fechaInicio->copy()->startOfDay()->timezone('UTC');
        $fechaFinUtc = $fechaFin->copy()->endOfDay()->timezone('UTC');

        // Ventas donde es el vendedor directo
        $ventasAsVendedor = Venta::with(['cliente', 'productos.kitItems.item', 'servicios', 'cita'])
            ->where('vendedor_type', $vendedorType)
            ->where('vendedor_id', $vendedorId)
            ->whereIn('estado', ['aprobada', 'enviada', 'facturada', 'pagado'])
            ->whereBetween('fecha', [$fechaInicioUtc, $fechaFinUtc])
            ->get();

        // Ventas donde es el técnico de la cita
        $ventasAsTecnico = Venta::with(['cliente', 'productos.kitItems.item', 'servicios', 'cita'])
            ->whereHas('cita', function ($q) use ($vendedorId) {
                $q->where('tecnico_id', $vendedorId);
            })
            ->whereIn('estado', ['aprobada', 'enviada', 'facturada', 'pagado'])
            ->whereBetween('fecha', [$fechaInicioUtc, $fechaFinUtc])
            ->get();

        // Ventas donde es el ayudante de la cita
        $ventasAsAyudante = Venta::with(['cliente', 'productos.kitItems.item', 'servicios', 'cita'])
            ->whereHas('cita', function ($q) use ($vendedorId) {
                $q->where('ayudante_id', $vendedorId);
            })
            ->whereIn('estado', ['aprobada', 'enviada', 'facturada', 'pagado'])
            ->whereBetween('fecha', [$fechaInicioUtc, $fechaFinUtc])
            ->get();

        // Combinar y ordenar por fecha
        $ventas = $ventasAsVendedor->concat($ventasAsTecnico)->concat($ventasAsAyudante)->unique('id')->sortBy('fecha');

        $totalComisionBruto = 0;
        $totalComisionPendiente = 0;
        $detalles = [];
        $usuario = User::find($vendedorId);

        foreach ($ventas as $venta) {
            // Calcular comisión específica para este usuario en esta venta
            $comisionVenta = $this->calcularComisionVentaParaUsuario($venta, $vendedorId);
            
            $totalComisionBruto += $comisionVenta['total'];
            
            if (!$venta->comision_pagada) {
                $totalComisionPendiente += $comisionVenta['total'];
            }

            // Obtener desglose de items para análisis detallado
            $itemsBreakdown = [];
            
            // Productos (incluyendo Kits)
            $esVendedorDirecto = ($venta->vendedor_id == $vendedorId);
            $esTecnicoCita = ($venta->cita && $venta->cita->tecnico_id == $vendedorId);
            $esAyudanteCita = ($venta->cita && $venta->cita->ayudante_id == $vendedorId);
            $tieneAyudante = ($venta->cita && $venta->cita->ayudante_id);

            if ($esVendedorDirecto || $esTecnicoCita || $esAyudanteCita) {
                foreach ($venta->productos as $p) {
                    $pivot = $p->pivot;
                    
                    // Si el usuario es el vendedor, gana por el margen del producto/kit
                    if ($esVendedorDirecto) {
                        $precioVenta = $pivot->precio * (1 - ($pivot->descuento ?? 0) / 100);
                        $costo = $pivot->costo_unitario ?? $p->precio_compra ?? 0;
                        $gananciaBase = ($precioVenta - $costo) * $pivot->cantidad;
                        
                        $pctComision = ($p->comision_vendedor ?? 0) > 0
                            ? $p->comision_vendedor
                            : $this->getDefaultComisionProducto();
                        $comisionItem = $gananciaBase * ($pctComision / 100);
                        if ($usuario && $usuario->es_tecnico) {
                            $comisionItem += $gananciaBase * (($usuario->margen_venta_productos ?? 0) / 100);
                        }

                        $itemsBreakdown[] = [
                            'nombre' => $p->nombre,
                            'tipo' => 'Producto',
                            'cantidad' => $pivot->cantidad,
                            'precio' => (float)$pivot->precio,
                            'descuento' => (float)$pivot->descuento,
                            'comision' => round($comisionItem, 2),
                            'motivo_cero' => round($comisionItem, 2) <= 0 ? $this->motivoComisionCero([
                                'tipo' => 'Producto', 'item' => $p, 'gananciaBase' => $gananciaBase
                            ]) : null,
                        ];
                    }

                    // Si el usuario es el técnico, ayudante o vendedor-técnico Y el producto es un kit, buscar instalaciones dentro del kit
                    if (($esTecnicoCita || $esAyudanteCita || ($esVendedorDirecto && $usuario && $usuario->es_tecnico)) && $p->tipo_producto === 'kit') {
                        foreach ($p->kitItems as $ki) {
                            if ($ki->item_type === 'servicio' && $ki->item && $ki->item->es_instalacion) {
                                $comisionInstalacion = ($usuario->comision_instalacion ?? 0) > 0 
                                    ? $usuario->comision_instalacion 
                                    : 300; // Default de 300 si no está configurado

                                $totalInstalacion = $comisionInstalacion * $ki->cantidad * $pivot->cantidad;
                                
                                $splitTecnico = $this->getSplitTecnico();
                                $splitAyudante = $this->getSplitAyudante();
                                if ($tieneAyudante) {
                                    $totalInstalacion = $totalInstalacion * ($esTecnicoCita ? $splitTecnico : $splitAyudante);
                                } elseif ($esAyudanteCita) {
                                    $totalInstalacion = 0;
                                }

                                $itemsBreakdown[] = [
                                    'nombre' => "Instalación (Kit: {$p->nombre})" . ($tieneAyudante ? ($esTecnicoCita ? " (Líder " . ($splitTecnico*100) . "%)" : " (Ayudante " . ($splitAyudante*100) . "%)") : ''),
                                    'tipo' => 'Servicio',
                                    'cantidad' => $ki->cantidad * $pivot->cantidad,
                                    'precio' => 0,
                                    'descuento' => 0,
                                    'comision' => round($totalInstalacion, 2),
                                    'motivo_cero' => round($totalInstalacion, 2) <= 0 ? $this->motivoComisionCero([
                                        'tipo' => 'Instalación', 'esAyudanteSinSplit' => true
                                    ]) : null,
                                ];
                            }
                        }
                    }
                }
            }

            // Servicios (si es el técnico, ayudante o el vendedor sin técnico asignado)
            if ($esTecnicoCita || $esAyudanteCita || ($esVendedorDirecto && (!$venta->cita || !$venta->cita->tecnico_id))) {
                foreach ($venta->servicios as $s) {
                    $pivot = $s->pivot;
                    $precioVenta = $pivot->precio * (1 - ($pivot->descuento ?? 0) / 100);
                    $precioConIva = $venta->iva_incluido ? $precioVenta : ($precioVenta * 1.16);
                    
                    if ($usuario && $usuario->es_tecnico) {
                        $tipo = $s->tipo_comision_tecnica;
                        
                        if ($tipo === 'refrigeracion') {
                            // Servicios de refrigeración en 350
                            $comisionItem = 350 * $pivot->cantidad;
                        } elseif ($tipo === 'limpieza_refrigeracion') {
                            $comisionItem = 300 * $pivot->cantidad;
                        } elseif ($tipo === 'preventivo_refrigerador') {
                            $comisionItem = 200 * $pivot->cantidad;
                        } elseif ($tipo === 'desinstalacion') {
                            // Desinstalaciones en 100
                            $comisionItem = 100 * $pivot->cantidad;
                        } elseif ($tipo === 'tierra') {
                            // Instalación de tierra en 100 cada una
                            $comisionItem = 100 * $pivot->cantidad;
                        } elseif ($tipo === 'instalacion' || $s->es_instalacion) {
                            // Instalaciones generales en 300
                            $comisionInstalacion = ($usuario->comision_instalacion ?? 0) > 0 
                                ? $usuario->comision_instalacion 
                                : 300;
                            $comisionItem = $comisionInstalacion * $pivot->cantidad;
                        } else {
                            // Otros servicios (diagnósticos, preventivos, etc.) al 30% del total
                            $comisionItem = $precioConIva * (($usuario->margen_venta_servicios ?? 0) / 100) * $pivot->cantidad;
                        }

                        // Aplicar división Líder / Ayudante
                        $splitTecnico = $this->getSplitTecnico();
                        $splitAyudante = $this->getSplitAyudante();
                        if ($tieneAyudante) {
                            $comisionItem = $comisionItem * ($esTecnicoCita ? $splitTecnico : $splitAyudante);
                        } elseif ($esAyudanteCita) {
                            $comisionItem = 0;
                        }
                    } else {
                        // Para vendedores normales, usamos la comisión fija del catálogo
                        $comisionItem = ($s->comision_vendedor ?? 0) * $pivot->cantidad;
                    }

                    $esTecnico = $usuario && $usuario->es_tecnico;
                    $itemsBreakdown[] = [
                        'nombre' => $s->nombre . ($tieneAyudante && ($esTecnicoCita || $esAyudanteCita) ? ($esTecnicoCita ? ' (Líder ' . ($splitTecnico*100) . '%)' : ' (Ayudante ' . ($splitAyudante*100) . '%)') : ''),
                        'tipo' => 'Servicio',
                        'cantidad' => $pivot->cantidad,
                        'precio' => (float)$precioConIva,
                        'descuento' => (float)$pivot->descuento,
                        'comision' => round($comisionItem, 2),
                        'motivo_cero' => round($comisionItem, 2) <= 0 ? $this->motivoComisionCero([
                            'tipo' => 'Servicio', 'item' => $s, 'esTecnico' => $esTecnico
                        ]) : null,
                    ];
                }
            }

            $detalles[] = [
                'venta_id' => $venta->id,
                'numero_venta' => $venta->numero_venta,
                'fecha' => $venta->fecha->format('Y-m-d'),
                'fecha_display' => $venta->fecha->locale('es')->isoFormat('D MMM YYYY'),
                'cliente' => $venta->cliente ? $venta->cliente->nombre_razon_social : 'Sin cliente',
                'total_venta' => (float) $venta->total,
                'comision_productos' => $comisionVenta['productos'],
                'comision_servicios' => $comisionVenta['servicios'],
                'comision_total' => $comisionVenta['total'],
                'rol' => $venta->vendedor_id == $vendedorId ? 'Vendedor' : ($esTecnicoCita ? 'Técnico Líder' : 'Ayudante'),
                'comision_pagada' => (bool) $venta->comision_pagada,
                'items' => $itemsBreakdown,
            ];
        }

        return [
            'vendedor_type' => $vendedorType,
            'vendedor_id' => $vendedorId,
            'periodo_inicio' => $fechaInicio->format('Y-m-d'),
            'periodo_fin' => $fechaFin->format('Y-m-d'),
            'total_comision_bruto' => round($totalComisionBruto, 2),
            'total_comision' => round($totalComisionPendiente, 2),
            'num_ventas' => count($ventas),
            'total_ventas' => $ventas->sum('total'),
            'detalles' => $detalles,
        ];
    }

    /**
     * Calcular resumen general del periodo para todos los vendedores (o uno específico)
     */
    public function obtenerResumenPeriodo(Carbon $fechaInicio, Carbon $fechaFin, ?int $userId = null): array
    {
        if ($userId) {
            $user = User::find($userId);
            $vendedores = collect();
            if ($user) {
                $vendedores->push([
                    'id' => $user->id,
                    'type' => User::class,
                    'type_label' => $user->es_tecnico ? 'Técnico' : 'Vendedor',
                    'nombre' => $user->name,
                ]);
            }
        } else {
            $vendedores = $this->obtenerVendedoresConVentas();
        }
        
        $resumenVendedores = [];

        foreach ($vendedores as $v) {
            $detalle = $this->calcularComisionesVendedor($v['type'], $v['id'], $fechaInicio, $fechaFin);

            // Determinar estado basado en las ventas reales
            $detallesCollection = collect($detalle['detalles']);
            $hasPaid = $detallesCollection->contains('comision_pagada', true);
            $hasPending = $detallesCollection->contains('comision_pagada', false);

            if ($hasPaid && $hasPending) {
                $estado = 'parcial';
            } elseif ($hasPaid && !$hasPending) {
                $estado = 'pagado';
            } else {
                $estado = 'pendiente';
            }

            // Calcular monto pagado real: bruto - pendiente
            $montoPagado = $detalle['total_comision_bruto'] - $detalle['total_comision'];

            // Buscar el ID del último pago para el recibo en el dashboard
            $ultimoPago = PagoComision::where('vendedor_id', $v['id'])
                ->where('periodo_inicio', $fechaInicio->format('Y-m-d'))
                ->where('periodo_fin', $fechaFin->format('Y-m-d'))
                ->orderBy('created_at', 'desc')
                ->first();

            if ($detalle['num_ventas'] > 0) {
                $resumenVendedores[] = [
                    'id' => $v['id'],
                    'type' => $v['type'],
                    'type_label' => $v['type_label'],
                    'nombre' => $v['nombre'],
                    'num_ventas' => $detalle['num_ventas'],
                    'total_ventas' => $detalle['total_ventas'],
                    'comision_bruto' => $detalle['total_comision_bruto'],
                    'comision' => $detalle['total_comision'],
                    'pagado' => round($montoPagado, 2),
                    'pendiente' => $detalle['total_comision'],
                    'estado' => $estado,
                    'pago_id' => $ultimoPago ? $ultimoPago->id : null,
                ];
            }
        }

        return [
            'periodo_inicio' => $fechaInicio->format('Y-m-d'),
            'periodo_fin' => $fechaFin->format('Y-m-d'),
            'periodo_label' => $fechaInicio->format('d M') . ' - ' . $fechaFin->format('d M Y'),
            'total_comisiones' => collect($resumenVendedores)->sum('comision_bruto'),
            'total_pagado' => collect($resumenVendedores)->sum('pagado'),
            'total_pendiente' => collect($resumenVendedores)->sum('pendiente'),
            'vendedores' => $resumenVendedores,
        ];
    }

    /**
     * Calcular comisión de una venta específica para un usuario concreto
     */
    public function calcularComisionVentaParaUsuario(Venta $venta, int $userId): array
    {
        $comisionProductos = 0;
        $comisionServicios = 0;
        $usuario = User::find($userId);
        
        if (!$usuario) return ['productos' => 0, 'servicios' => 0, 'total' => 0];

        // Determinar si es vendedor directo, técnico de la cita, o ayudante de la cita
        $esVendedorDirecto = ($venta->vendedor_type === User::class && $venta->vendedor_id == $userId);
        $esTecnicoCita = ($venta->cita && $venta->cita->tecnico_id == $userId);
        $esAyudanteCita = ($venta->cita && $venta->cita->ayudante_id == $userId);
        $tieneAyudante = ($venta->cita && $venta->cita->ayudante_id);

        // 1. Comisión de productos
        if ($esVendedorDirecto) {
            foreach ($venta->productos as $producto) {
                $pivot = $producto->pivot;
                $precioVenta = $pivot->precio * (1 - ($pivot->descuento ?? 0) / 100);
                $costo = $pivot->costo_unitario ?? $producto->precio_compra ?? 0;
                $gananciaBase = ($precioVenta - $costo) * $pivot->cantidad;

                $pctComision = ($producto->comision_vendedor ?? 0) > 0
                    ? $producto->comision_vendedor
                    : $this->getDefaultComisionProducto();
                $comisionProductos += $gananciaBase * ($pctComision / 100);

                if ($usuario->es_tecnico) {
                    $comisionProductos += $gananciaBase * (($usuario->margen_venta_productos ?? 0) / 100);
                }
            }
        }

        // 2. Comisión de servicios
        if ($esTecnicoCita || $esAyudanteCita || ($esVendedorDirecto && (!$venta->cita || !$venta->cita->tecnico_id))) {
            foreach ($venta->servicios as $servicio) {
                $pivot = $servicio->pivot;
                $precioVenta = $pivot->precio * (1 - ($pivot->descuento ?? 0) / 100);
                $precioConIva = $venta->iva_incluido ? $precioVenta : ($precioVenta * 1.16);

                if ($usuario && $usuario->es_tecnico) {
                    $tipo = $servicio->tipo_comision_tecnica;
                    if ($tipo === 'refrigeracion') {
                        $comisionItem = 350 * $pivot->cantidad;
                    } elseif ($tipo === 'limpieza_refrigeracion') {
                        $comisionItem = 300 * $pivot->cantidad;
                    } elseif ($tipo === 'preventivo_refrigerador') {
                        $comisionItem = 200 * $pivot->cantidad;
                    } elseif ($tipo === 'desinstalacion') {
                        $comisionItem = 100 * $pivot->cantidad;
                    } elseif ($tipo === 'tierra') {
                        $comisionItem = 100 * $pivot->cantidad;
                    } elseif ($tipo === 'instalacion' || $servicio->es_instalacion) {
                        $comisionInstalacion = ($usuario->comision_instalacion ?? 0) > 0 
                            ? $usuario->comision_instalacion 
                            : 300;
                        $comisionItem = $comisionInstalacion * $pivot->cantidad;
                    } else {
                        $comisionItem = $precioConIva * (($usuario->margen_venta_servicios ?? 0) / 100) * $pivot->cantidad;
                    }

                    $splitTecnico = $this->getSplitTecnico();
                    $splitAyudante = $this->getSplitAyudante();
                    // Aplicar división si hay ayudante asignado
                    if ($tieneAyudante) {
                        $comisionServicios += $comisionItem * ($esTecnicoCita ? $splitTecnico : $splitAyudante);
                    } elseif ($esTecnicoCita) {
                        $comisionServicios += $comisionItem;
                    } elseif ($esVendedorDirecto) {
                        $comisionServicios += $comisionItem;
                    }
                } else {
                    $comisionServicios += ($servicio->comision_vendedor ?? 0) * $pivot->cantidad;
                }
            }
        }

        // 3. Comisión por instalaciones dentro de KITS (para técnicos/ayudantes/vendedor-técnico)
        if ($esTecnicoCita || $esAyudanteCita || ($esVendedorDirecto && $usuario && $usuario->es_tecnico)) {
            $splitTecnico = $this->getSplitTecnico();
            $splitAyudante = $this->getSplitAyudante();
            foreach ($venta->productos as $producto) {
                if ($producto->tipo_producto === 'kit') {
                    if (!$producto->relationLoaded('kitItems')) {
                        $producto->load(['kitItems.item']);
                    }
                    foreach ($producto->kitItems as $ki) {
                        if ($ki->item_type === 'servicio' && $ki->item && $ki->item->es_instalacion) {
                            $comisionInstalacion = ($usuario->comision_instalacion ?? 0) > 0 
                                ? $usuario->comision_instalacion 
                                : 300;
                            
                            $comisionItem = $comisionInstalacion * $ki->cantidad * $producto->pivot->cantidad;
                            
                            if ($tieneAyudante) {
                                $comisionServicios += $comisionItem * ($esTecnicoCita ? $splitTecnico : $splitAyudante);
                            } elseif ($esTecnicoCita) {
                                $comisionServicios += $comisionItem;
                            } elseif ($esVendedorDirecto) {
                                $comisionServicios += $comisionItem;
                            }
                        }
                    }
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
     * Crear registro de pago de comisión
     */
    public function crearPagoComision(array $data): PagoComision
    {
        $ventaIds = $data['venta_ids'] ?? [];
        
        $calculo = $this->calcularComisionesVendedor(
            $data['vendedor_type'],
            $data['vendedor_id'],
            Carbon::parse($data['periodo_inicio']),
            Carbon::parse($data['periodo_fin'])
        );

        // Si se especificaron ventas, filtrar el cálculo y el total
        if (!empty($ventaIds)) {
            $detallesFiltrados = collect($calculo['detalles'])->whereIn('venta_id', $ventaIds);
            $calculo['total_comision'] = $detallesFiltrados->sum('comision_total');
            $calculo['num_ventas'] = $detallesFiltrados->count();
            $calculo['total_ventas'] = $detallesFiltrados->sum('total_venta');
            $calculo['detalles'] = $detallesFiltrados->values()->all();
        }

        $montoPagado = (float) ($data['monto_pagado'] ?? $calculo['total_comision']);
        $montoComision = (float) $calculo['total_comision'];

        // Validar que no se pague más de lo debido
        if ($montoPagado > $montoComision && $montoComision > 0) {
            throw new \InvalidArgumentException(
                "El monto pagado (\${$montoPagado}) excede la comisión calculada (\${$montoComision})."
            );
        }

        $pagadoCompleto = $montoPagado >= $montoComision;

        $pago = PagoComision::create([
            'empresa_id' => $data['empresa_id'] ?? EmpresaResolver::resolveId(),
            'vendedor_type' => User::class,
            'vendedor_id' => $data['vendedor_id'],
            'periodo_inicio' => $data['periodo_inicio'],
            'periodo_fin' => $data['periodo_fin'],
            'monto_comision' => $montoComision,
            'monto_pagado' => $montoPagado,
            'estado' => $pagadoCompleto ? 'pagado' : 'parcial',
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

        // Marcar ventas como pagadas (completas o parciales inteligente)
        $ventasCollection = collect($calculo['detalles']);

        if ($pagadoCompleto) {
            // Pago completo: marcar todas como pagadas
            Venta::whereIn('id', $ventasCollection->pluck('venta_id'))
                ->update([
                    'comision_pagada' => true,
                    'comision_pagada_at' => now(),
                    'pago_comision_id' => $pago->id
                ]);
        } elseif ($montoPagado > 0 && !empty($ventaIds)) {
            // Pago parcial con IDs específicos: marcar exactamente esas ventas
            Venta::whereIn('id', $ventaIds)
                ->update([
                    'comision_pagada' => true,
                    'comision_pagada_at' => now(),
                    'pago_comision_id' => $pago->id
                ]);
        } elseif ($montoPagado > 0) {
            // Pago parcial inteligente: marcar las ventas más antiguas primero
            // hasta cubrir el monto pagado (de más antigua a más reciente)
            $acumulado = 0;
            $ventasAMarcar = [];
            $ventasOrdenadas = $ventasCollection->sortBy('fecha');

            foreach ($ventasOrdenadas as $vd) {
                if ($acumulado >= $montoPagado) break;
                $ventasAMarcar[] = $vd['venta_id'];
                $acumulado += $vd['comision_total'];
            }

            if (!empty($ventasAMarcar)) {
                Venta::whereIn('id', $ventasAMarcar)
                    ->update([
                        'comision_pagada' => true,
                        'comision_pagada_at' => now(),
                        'pago_comision_id' => $pago->id
                    ]);
            }
        }

        return $pago;
    }
}
