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
        'responsable_id',
    ];

    protected $casts = [
        'saldo_inicial' => 'decimal:2',
        'saldo_actual' => 'decimal:2',
        'activa' => 'boolean',
    ];

    // ==================== RELACIONES ====================

    public function responsable(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

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
     * Obtener la cuenta bancaria del nuevo módulo de Bancos correspondiente
     */
    public function getBancoCuentaCorrespondiente(): ?\App\Models\Bancos\BancoCuenta
    {
        // 1. Intentar coincidencia exacta por número de cuenta limpio (removiendo espacios y guiones)
        if (!empty($this->numero_cuenta)) {
            $numLimpio = preg_replace('/[^0-9]/', '', $this->numero_cuenta);
            if (!empty($numLimpio)) {
                $match = \App\Models\Bancos\BancoCuenta::where('empresa_id', $this->empresa_id)
                    ->whereRaw("REGEXP_REPLACE(numero_cuenta, '[^0-9]', '') = ?", [$numLimpio])
                    ->first();
                if ($match) {
                    return $match;
                }
            }
        }

        // 2. Intentar coincidencia por CLABE limpia
        if (!empty($this->clabe)) {
            $clabeLimpia = preg_replace('/[^0-9]/', '', $this->clabe);
            if (!empty($clabeLimpia)) {
                $match = \App\Models\Bancos\BancoCuenta::where('empresa_id', $this->empresa_id)
                    ->whereRaw("REGEXP_REPLACE(clabe, '[^0-9]', '') = ?", [$clabeLimpia])
                    ->first();
                if ($match) {
                    return $match;
                }
            }
        }

        // 3. Intentar coincidencia por Nombre / Alias (Ignorando mayúsculas y acentos)
        $match = \App\Models\Bancos\BancoCuenta::where('empresa_id', $this->empresa_id)
            ->where(function($q) {
                $nombreLimpio = trim(strtolower($this->nombre));
                $q->whereRaw("LOWER(alias) = ?", [$nombreLimpio])
                  ->orWhereRaw("LOWER(nombre_banco) = ?", [$nombreLimpio]);
            })->first();

        if ($match) {
            return $match;
        }

        // 4. Si aún no existe, AUTOCREAR la cuenta correspondiente en el nuevo módulo
        // para garantizar integridad y evitar fallas silenciosas en la conciliación.
        try {
            return \App\Models\Bancos\BancoCuenta::create([
                'empresa_id' => $this->empresa_id,
                'nombre_banco' => $this->banco ?: 'Banco Desconocido',
                'alias' => $this->nombre,
                'numero_cuenta' => $this->numero_cuenta,
                'clabe' => $this->clabe,
                'moneda' => $this->moneda ?: 'MXN',
                'saldo_inicial' => $this->saldo_inicial ?: 0,
                'saldo_actual' => $this->saldo_actual ?: 0,
                'es_fiscal' => false,
                'tipo' => $this->tipo ?: 'corriente',
            ]);
        } catch (\Exception $e) {
            \Log::error("Error autocreando BancoCuenta correspondiente: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Registrar movimiento y actualizar saldo de forma segura (Atómica) y sincronizar con módulo Bancos
     * @param string $tipo 'deposito' o 'retiro'
     * @param float $monto Monto del movimiento
     * @param string $concepto Descripción del movimiento
     * @param string|null $origenTipo Tipo de origen: venta, renta, cobro, prestamo, traspaso, pago, otro
     */
    public function registrarMovimiento(string $tipo, float $monto, string $concepto = '', ?string $origenTipo = null, $conciliable = null, ?string $fechaCustom = null): MovimientoBancario
    {
        $fechaReal = $fechaCustom ? \Carbon\Carbon::parse($fechaCustom) : now();

        return \Illuminate\Support\Facades\DB::transaction(function () use ($tipo, $monto, $concepto, $origenTipo, $conciliable, $fechaReal) {
            // Bloquear la cuenta para lectura/escritura (Pessimistic Locking)
            $cuentaBloqueada = static::where('id', $this->id)->lockForUpdate()->first();

            $montoFinal = $tipo === 'retiro' ? -abs($monto) : abs($monto);

            $movimiento = $cuentaBloqueada->movimientos()->create([
                'empresa_id' => $cuentaBloqueada->empresa_id,
                'fecha' => $fechaReal,
                'concepto' => $concepto,
                'monto' => $montoFinal,
                'tipo' => $tipo,
                'origen_tipo' => $origenTipo,
                'banco' => $cuentaBloqueada->banco,
                'estado' => 'conciliado',
                'usuario_id' => auth()->id(),
                'conciliable_type' => $conciliable ? get_class($conciliable) : null,
                'conciliable_id' => $conciliable ? $conciliable->id : null,
            ]);

            // Actualizar saldo de la instancia bloqueada
            $cuentaBloqueada->saldo_actual += $montoFinal;
            $cuentaBloqueada->save();

            // Actualizar también la instancia actual en memoria para reflejar cambios
            $this->saldo_actual = $cuentaBloqueada->saldo_actual;

            // SINCRONIZACIÓN AUTOMÁTICA CON EL NUEVO MÓDULO DE BANCOS
            $bancoCuenta = $cuentaBloqueada->getBancoCuentaCorrespondiente();
            if ($bancoCuenta) {
                $montoAbs = abs($monto);
                $tipoBanco = $tipo === 'retiro' ? 'egreso' : 'ingreso';

                // Verificar si ya existe el movimiento en el nuevo módulo para esa fecha
                $exists = \App\Models\Bancos\BancoMovimiento::where('cuenta_bancaria_id', $bancoCuenta->id)
                    ->where('fecha', $fechaReal->toDateString())
                    ->where('monto', $montoAbs)
                    ->where('concepto', $concepto)
                    ->exists();

                if (!$exists) {
                    $userId = auth()->id() ?? 1;
                    if (!\App\Models\User::where('id', $userId)->exists()) {
                        $userId = \App\Models\User::first()->id ?? 1;
                    }

                    \App\Models\Bancos\BancoMovimiento::create([
                        'cuenta_bancaria_id' => $bancoCuenta->id,
                        'fecha' => $fechaReal,
                        'tipo' => $tipoBanco,
                        'forma_pago_sat' => '03', // Transferencia
                        'monto' => $montoAbs,
                        'concepto' => $concepto,
                        'referencia' => null,
                        'conciliable_type' => $conciliable ? get_class($conciliable) : null,
                        'conciliable_id' => $conciliable ? $conciliable->id : null,
                        'estado_conciliacion' => 'conciliado',
                        'created_by' => $userId,
                    ]);

                    // Actualizar el saldo en BancoCuenta
                    $bancoCuenta->increment('saldo_actual', $tipo === 'retiro' ? -$montoAbs : $montoAbs);
                }
            }

            return $movimiento;
        });
    }

    /**
     * Actualizar saldo después de conciliar un movimiento
     */
    public function actualizarSaldoPorMovimiento(MovimientoBancario $movimiento): void
    {
        $montoAbs = abs($movimiento->monto);
        $diff = $movimiento->tipo === 'deposito' ? $montoAbs : -$montoAbs;
        $this->saldo_actual += $diff;
        $this->save();

        $bancoCuenta = $this->getBancoCuentaCorrespondiente();
        if ($bancoCuenta) {
            $bancoCuenta->increment('saldo_actual', $diff);
        }
    }

    /**
     * Revertir saldo después de revertir conciliación
     */
    public function revertirSaldoPorMovimiento(MovimientoBancario $movimiento): void
    {
        $montoAbs = abs($movimiento->monto);
        $diff = $movimiento->tipo === 'deposito' ? -$montoAbs : $montoAbs;
        $this->saldo_actual += $diff;
        $this->save();

        $bancoCuenta = $this->getBancoCuentaCorrespondiente();
        if ($bancoCuenta) {
            $bancoCuenta->increment('saldo_actual', $diff);
        }
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
