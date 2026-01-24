<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class PedidoOnline extends Model
{
    use BelongsToEmpresa, Notifiable;

    protected $table = 'pedidos_online';

    // Disparadores automáticos para registrar creación
    protected static function boot()
    {
        parent::boot();
        static::created(function ($pedido) {
            // Intentar registrar el evento solo si ya tiene ID (aunque after create siempre tiene)
            // Usamos una conexión directa o try-catch por si acaso
            try {
                \App\Models\PedidoBitacora::create([
                    'pedido_online_id' => $pedido->id,
                    'accion' => 'CREACION',
                    'descripcion' => 'Pedido creado por el cliente',
                    'metadata' => ['ip' => request()->ip(), 'user_agent' => request()->userAgent()],
                    'created_at' => now()
                ]);
            } catch (\Exception $e) {
            }
        });
    }

    protected $fillable = [
        'empresa_id',
        'numero_pedido',
        'cliente_tienda_id',
        'cliente_id',
        'email',
        'nombre',
        'telefono',
        'direccion_envio',
        'items',
        'subtotal',
        'costo_envio',
        'total',
        'metodo_pago',
        'estado',
        'payment_id',
        'payment_status',
        'payment_status',
        'payment_details',
        'cva_pedido_id',
        'guia_envio',
        'paqueteria',
        'notas',
        'pagado_at',
        'enviado_at',
        'entregado_at',
    ];

    protected $casts = [
        'direccion_envio' => 'array',
        'items' => 'array',
        'payment_details' => 'array',
        'subtotal' => 'decimal:2',
        'costo_envio' => 'decimal:2',
        'total' => 'decimal:2',
        'pagado_at' => 'datetime',
        'enviado_at' => 'datetime',
        'entregado_at' => 'datetime',
    ];

    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_PAGADO = 'pagado';
    const ESTADO_ENVIADO = 'enviado';
    const ESTADO_ENTREGADO = 'entregado';
    const ESTADO_CANCELADO = 'cancelado';

    /**
     * Cliente que realizó el pedido
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(ClienteTienda::class, 'cliente_tienda_id');
    }

    /**
     * Historial de eventos del pedido
     */
    public function bitacora(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PedidoBitacora::class)->orderBy('created_at', 'desc');
    }

    /**
     * Registrar un evento en la bitácora
     */
    public function registrarEvento(string $accion, string $descripcion, array $metadata = [], ?int $usuarioId = null): void
    {
        $this->bitacora()->create([
            'accion' => $accion,
            'descripcion' => $descripcion,
            'usuario_id' => $usuarioId ?? auth()->id(), // Si hay usuario autenticado lo toma
            'metadata' => $metadata
        ]);
    }

    /**
     * Generar número de pedido único
     */
    public static function generarNumeroPedido(): string
    {
        $fecha = now()->format('Ymd');
        $ultimo = self::whereDate('created_at', today())->count() + 1;
        return "PO-{$fecha}-" . str_pad($ultimo, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Scope para pedidos pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    /**
     * Scope para pedidos pagados
     */
    public function scopePagados($query)
    {
        return $query->where('estado', self::ESTADO_PAGADO);
    }

    /**
     * Marcar como pagado
     */
    public function marcarComoPagado(string $paymentId, string $paymentStatus, array $paymentDetails = []): void
    {
        $this->update([
            'estado' => self::ESTADO_PAGADO,
            'payment_id' => $paymentId,
            'payment_status' => $paymentStatus,
            'payment_details' => $paymentDetails,
            'pagado_at' => now(),
        ]);

        $this->registrarEvento('PAGO_CONFIRMADO', "Pago confirmado mediante {$this->metodo_pago}. Ref: {$paymentId}");

        // AUTOMATIZACIÓN DE BANCOS:
        // Registrar movimiento en el banco automáticamente dependiendo del método
        try {
            $config = \App\Models\EmpresaConfiguracion::getConfig($this->empresa_id);
            $cuentaId = null;

            if ($this->metodo_pago === 'paypal') {
                $cuentaId = $config->cuenta_id_paypal;
            } elseif ($this->metodo_pago === 'mercadopago') {
                $cuentaId = $config->cuenta_id_mercadopago;
            } elseif ($this->metodo_pago === 'tarjeta' || $this->metodo_pago === 'stripe') {
                $cuentaId = $config->cuenta_id_stripe;
            }

            if ($cuentaId) {
                $cuenta = \App\Models\CuentaBancaria::find($cuentaId);
                if ($cuenta) {
                    $cuenta->registrarMovimiento(
                        'deposito',
                        (float) $this->total,
                        "Pago Tienda Online ({$this->metodo_pago}): Pedido {$this->numero_pedido}",
                        'venta'
                    );
                }
            }
        } catch (\Exception $e) {
            \Log::error("Error registrando movimiento bancario automático para pedido {$this->numero_pedido}: " . $e->getMessage());
        }

        // INTEGRACIÓN CON CVA:
        // Si el pedido tiene productos de CVA, enviarlo a CVA cuando se confirme el pago
        // (Solo si no fue enviado previamente, por ejemplo en manual ya se envía)
        if (str_contains($this->notas ?? '', '[PEDIDO CVA:') || str_contains($this->notas ?? '', '[DETALLE CVA:')) {
            return; // Ya fue enviado
        }

        try {
            $itemsCVA = [];
            foreach ($this->items as $item) {
                if (isset($item['origen']) && $item['origen'] === 'CVA') {
                    $itemsCVA[] = [
                        'id' => $item['id'] ?? $item['producto_id'],
                        'cantidad' => $item['cantidad'],
                    ];
                }
            }

            if (!empty($itemsCVA)) {
                // Limpiar las claves para CVA (quitar prefijo CVA- si existe)
                $itemsCVAFormateados = [];
                foreach ($itemsCVA as $item) {
                    $clave = $item['id'] ?? $item['producto_id'];
                    $claveLimpia = str_replace('CVA-', '', $clave);
                    $itemsCVAFormateados[] = [
                        'clave' => $claveLimpia,
                        'cantidad' => $item['cantidad'],
                    ];
                }

                $serviceCva = app(\App\Services\CVAService::class);
                $orderData = [
                    'productos' => $itemsCVAFormateados,
                ];

                if ($this->tipo_entrega === 'domicilio') {
                    $orderData['tipo_flete'] = 'CP'; // Con paquetería
                    $orderData['flete'] = [
                        'calle' => $this->direccion['calle'] ?? '',
                        'numero' => 'SN',
                        'colonia' => $this->direccion['colonia'] ?? '',
                        'cp' => $this->direccion['cp'] ?? '',
                        'estado' => $this->direccion['estado'] ?? '',
                        'ciudad' => $this->direccion['ciudad'] ?? '',
                    ];
                } else {
                    $orderData['tipo_flete'] = 'SF'; // Sin flete (Recoge)
                }

                $cvaResult = $serviceCva->createOrder($orderData);

                if ($cvaResult['success']) {
                    $cvaPedidoId = $cvaResult['data']['pedido'] ?? null;
                    $cvaTotal = (float) ($cvaResult['data']['total'] ?? 0);

                    $this->update([
                        'cva_pedido_id' => $cvaPedidoId,
                        'notas' => ($this->notas ? $this->notas . " " : "") . "[DETALLE CVA: " . ($cvaPedidoId ?? 'ORDEN CREADA') . "]"
                    ]);

                    $this->registrarEvento(
                        'ENVIO_CVA',
                        "Pedido enviado a CVA exitosamente. ID: " . ($cvaPedidoId ?? 'N/A'),
                        $cvaResult['data'] ?? []
                    );

                    // --- NUEVA LÓGICA: CUENTAS POR PAGAR Y PAGO AUTOMÁTICO ---
                    try {
                        // 1. Asegurar que existe el proveedor CVA
                        $proveedor = \App\Models\Proveedor::firstOrCreate(
                            ['rfc' => 'CVO000522HU6'], // RFC Real de CVA (Comercializadora de Valor Agregado)
                            [
                                'nombre_razon_social' => 'COMERCIALIZADORA DE VALOR AGREGADO SA DE CV',
                                'nombre_corto' => 'CVA',
                                'email' => 'ventas@grupocva.com',
                                'activo' => true
                            ]
                        );

                        // 2. Crear Cuenta por Pagar (CXP)
                        $cxp = \App\Models\CuentasPorPagar::create([
                            'empresa_id' => $this->empresa_id,
                            'proveedor_id' => $proveedor->id,
                            'monto_total' => $cvaTotal,
                            'monto_pendiente' => $cvaTotal,
                            'fecha_vencimiento' => now()->addDays(2), // CVA suele dar poco tiempo o es contado
                            'estado' => 'pendiente',
                            'notas' => "Compra automatizada desde Pedido Online: {$this->numero_pedido} (CVA Ref: {$cvaPedidoId})",
                        ]);

                        // 3. Intento de Pago Automático (si está activo)
                        $config = \App\Models\EmpresaConfiguracion::getConfig($this->empresa_id);
                        if ($config && $config->cva_auto_pago && $cvaPedidoId && $cvaTotal > 0) {
                            $payResult = $serviceCva->payOrderWithMonedero($cvaPedidoId, $cvaTotal);

                            if ($payResult['success']) {
                                // Marcar CXP como pagada
                                $cxp->update([
                                    'pagado' => true,
                                    'estado' => 'pagado',
                                    'monto_pagado' => $cvaTotal,
                                    'monto_pendiente' => 0,
                                    'fecha_pago' => now(),
                                    'metodo_pago' => 'monedero_cva',
                                    'notas' => $cxp->notas . "\n[PAGO AUTOMÁTICO EXITOSO]"
                                ]);

                                $this->registrarEvento(
                                    'PAGO_CVA_AUTO',
                                    "Pago automático a CVA realizado con éxito vía Monedero. Monto: {$cvaTotal}",
                                    $payResult['data'] ?? []
                                );

                                \Log::info("Pago automático CVA exitoso para pedido {$this->numero_pedido}");
                            } else {
                                $this->registrarEvento(
                                    'PAGO_CVA_ERROR',
                                    "Error al realizar pago automático a CVA: " . ($payResult['error'] ?? 'Desconocido'),
                                    $payResult
                                );
                                \Log::error("Error en pago automático CVA para pedido {$this->numero_pedido}: " . ($payResult['error'] ?? 'N/A'));
                            }
                        }
                    } catch (\Exception $ecxp) {
                        \Log::error("Error en registro CXP / Pago CVA para pedido {$this->numero_pedido}: " . $ecxp->getMessage());
                    }

                    \Log::info("Pedido {$this->numero_pedido} enviado a CVA tras pago confirmado");
                } else {
                    $this->registrarEvento(
                        'ERROR_CVA',
                        "Error al enviar pedido a CVA: " . ($cvaResult['error'] ?? 'Desconocido'),
                        $cvaResult
                    );
                    \Log::error("Error al enviar pedido {$this->numero_pedido} a CVA tras pago", ['error' => $cvaResult['error']]);
                }
            }
        } catch (\Exception $e) {
            \Log::error("Excepción al enviar pedido {$this->numero_pedido} a CVA tras pago: " . $e->getMessage());
        }
    }

    /**
     * Obtener color del estado para UI
     */
    public function getEstadoColorAttribute(): string
    {
        return match ($this->estado) {
            'pendiente' => 'yellow',
            'pagado' => 'blue',
            'enviado' => 'purple',
            'entregado' => 'green',
            'cancelado' => 'red',
            default => 'gray',
        };
    }

    /**
     * Obtener etiqueta del estado
     */
    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'pendiente' => 'Pendiente de Pago',
            'pagado' => 'Pagado',
            'enviado' => 'En Camino',
            'entregado' => 'Entregado',
            'cancelado' => 'Cancelado',
            default => ucfirst($this->estado),
        };
    }

    /**
     * Obtener la URL de rastreo según la paquetería
     */
    public function getTrackingUrlAttribute(): ?string
    {
        if (!$this->guia_envio)
            return null;

        $paqueteria = strtoupper($this->paqueteria ?? '');

        if (str_contains($paqueteria, 'PAQUETEXPRESS')) {
            return "https://www.paquetexpress.com.mx/seguimiento-de-envio?rastreo={$this->guia_envio}";
        }
        if (str_contains($paqueteria, 'ESTAFETA')) {
            return "https://www.estafeta.com/Herramientas/Rastreo?waybill={$this->guia_envio}";
        }
        if (str_contains($paqueteria, 'FEDEX')) {
            return "https://www.fedex.com/fedextrack/?trknbr={$this->guia_envio}";
        }
        if (str_contains($paqueteria, 'DHL')) {
            return "https://www.dhl.com/mx-es/home/rastreo.html?tracking_number={$this->guia_envio}";
        }

        return null;
    }
}
