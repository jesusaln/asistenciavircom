<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoOnline extends Model
{
    use BelongsToEmpresa;

    protected $table = 'pedidos_online';

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
        'payment_details',
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
