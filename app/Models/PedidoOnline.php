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
                $serviceCva = app(\App\Services\CVAService::class);
                $orderData = [
                    'productos' => $itemsCVA,
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
                    $this->update([
                        'cva_pedido_id' => $cvaResult['data']['pedido'] ?? null,
                        'notas' => ($this->notas ? $this->notas . " " : "") . "[DETALLE CVA: " . ($cvaResult['data']['pedido'] ?? 'ORDEN CREADA') . "]"
                    ]);

                    $this->registrarEvento(
                        'ENVIO_CVA',
                        "Pedido enviado a CVA exitosamente. ID: " . ($cvaResult['data']['pedido'] ?? 'N/A'),
                        $cvaResult['data'] ?? []
                    );

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
}
