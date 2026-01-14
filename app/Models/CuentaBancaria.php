<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuentaBancaria extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'cuentas_bancarias';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'banco',
        'numero_cuenta',
        'clabe',
        'saldo_inicial',
        'saldo_actual',
        'moneda',
        'tipo',
        'activa',
        'notas',
        'color',
    ];

    protected $casts = [
        'saldo_inicial' => 'decimal:2',
        'saldo_actual' => 'decimal:2',
        'activa' => 'boolean',
    ];

    // ==================== RELACIONES ====================

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoBancario::class, 'cuenta_bancaria_id');
    }

    // ==================== SCOPES ====================

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    public function scopeBanco($query, string $banco)
    {
        return $query->where('banco', $banco);
    }

    // ==================== MÉTODOS ====================

    /**
     * Recalcular saldo desde movimientos conciliados
     */
    public function recalcularSaldo(): float
    {
        $depositos = $this->movimientos()
            ->where('estado', 'conciliado')
            ->where('tipo', 'deposito')
            ->sum('monto');

        $retiros = $this->movimientos()
            ->where('estado', 'conciliado')
            ->where('tipo', 'retiro')
            ->sum('monto'); // Ya viene negativo

        $this->saldo_actual = $this->saldo_inicial + $depositos + $retiros;
        $this->save();

        return $this->saldo_actual;
    }

    /**
     * Registrar movimiento y actualizar saldo
     */
    /**
     * Registrar movimiento y actualizar saldo de forma segura (Atómica)
     * @param string $tipo 'deposito' o 'retiro'
     * @param float $monto Monto del movimiento
     * @param string $concepto Descripción del movimiento
     * @param string|null $origenTipo Tipo de origen: venta, renta, cobro, prestamo, traspaso, pago, otro
     */
    public function registrarMovimiento(string $tipo, float $monto, string $concepto = '', ?string $origenTipo = null): MovimientoBancario
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($tipo, $monto, $concepto, $origenTipo) {
            // Bloquear la cuenta para lectura/escritura (Pessimistic Locking)
            // Esto evita condiciones de carrera si dos usuarios guardan al mismo tiempo.
            $cuentaBloqueada = static::where('id', $this->id)->lockForUpdate()->first();

            $montoFinal = $tipo === 'retiro' ? -abs($monto) : abs($monto);

            $movimiento = $cuentaBloqueada->movimientos()->create([
                'fecha' => now(),
                'concepto' => $concepto,
                'monto' => $montoFinal,
                'tipo' => $tipo,
                'origen_tipo' => $origenTipo,
                'banco' => $cuentaBloqueada->banco,
                'estado' => 'conciliado', // Se asume conciliado porque afecta saldo inmediatamente
                'usuario_id' => auth()->id(),
            ]);

            // Actualizar saldo de la instancia bloqueada
            $cuentaBloqueada->saldo_actual += $montoFinal;
            $cuentaBloqueada->save();

            // Actualizar también la instancia actual en memoria para reflejar cambios
            $this->saldo_actual = $cuentaBloqueada->saldo_actual;

            return $movimiento;
        });
    }

    /**
     * Actualizar saldo después de conciliar un movimiento
     */
    public function actualizarSaldoPorMovimiento(MovimientoBancario $movimiento): void
    {
        if ($movimiento->tipo === 'deposito') {
            $this->saldo_actual += abs($movimiento->monto);
        } else {
            $this->saldo_actual -= abs($movimiento->monto);
        }
        $this->save();
    }

    /**
     * Revertir saldo después de revertir conciliación
     */
    public function revertirSaldoPorMovimiento(MovimientoBancario $movimiento): void
    {
        if ($movimiento->tipo === 'deposito') {
            $this->saldo_actual -= abs($movimiento->monto);
        } else {
            $this->saldo_actual += abs($movimiento->monto);
        }
        $this->save();
    }

    /**
     * Obtener número de cuenta enmascarado
     */
    public function getNumeroCuentaMascaradoAttribute(): string
    {
        if (!$this->numero_cuenta) return '****';
        return '****' . substr($this->numero_cuenta, -4);
    }

    /**
     * Obtener color por defecto según banco
     */
    public static function getColorPorBanco(?string $banco): string
    {
        if (!$banco) {
            return '#6b7280';
        }
        
        return match(strtoupper($banco)) {
            'BBVA' => '#004481',
            'BANORTE' => '#eb0029',
            'SANTANDER' => '#ec0000',
            'HSBC' => '#db0011',
            'BANAMEX', 'CITIBANAMEX' => '#056dae',
            'SCOTIABANK' => '#ec111a',
            default => '#6b7280',
        };
    }
}
