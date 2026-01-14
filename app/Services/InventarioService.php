<?php

namespace App\Services;

use App\Models\InventarioMovimiento;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\User;
use App\Models\Lote;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class InventarioService
{
    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function entrada(Producto $producto, int $cantidad, array $contexto = []): void
    {
        if ($producto->expires) {
            $this->entradaConLote($producto, $cantidad, $contexto);
        } else {
            $this->ajustar($producto, 'entrada', $cantidad, $contexto);
        }
    }

    public function salida(Producto $producto, int $cantidad, array $contexto = []): array
    {
        if ($producto->expires) {
            return $this->salidaConLote($producto, $cantidad, $contexto);
        } else {
            $this->ajustar($producto, 'salida', $cantidad, $contexto);
            return [];
        }
    }

    protected function ajustar(Producto $producto, string $tipo, int $cantidad, array $contexto = []): void
    {
        if (!in_array($tipo, ['entrada', 'salida'], true)) {
            throw new InvalidArgumentException('Tipo de movimiento inválido.');
        }

        if ($cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad del movimiento debe ser mayor que cero.');
        }

        // ✅ CRITICAL FIX: Check if we're already inside a transaction to prevent nesting
        $skipTransaction = Arr::get($contexto, 'skip_transaction', false);
        $insideTransaction = DB::transactionLevel() > 0;

        // If already in a transaction or explicitly told to skip, execute directly
        if ($insideTransaction || $skipTransaction) {
            $this->ejecutarAjuste($producto, $tipo, $cantidad, $contexto);
        } else {
            // Otherwise, wrap in a transaction
            $this->db->transaction(function () use ($producto, $tipo, $cantidad, $contexto) {
                $this->ejecutarAjuste($producto, $tipo, $cantidad, $contexto);
            });
        }
    }

    /**
     * Execute the actual inventory adjustment logic
     * ✅ CRITICAL FIX: Extracted from ajustar() to allow reuse with/without transactions
     */
    protected function ejecutarAjuste(Producto $producto, string $tipo, int $cantidad, array $contexto = []): void
    {
        // ✅ BLINDAJE KITS: Si es un Kit, procesar recursivamente sus componentes
        if ($producto->tipo_producto === 'kit') {
            $producto->loadMissing('kitItems.item');

            foreach ($producto->kitItems as $kitItem) {
                if ($kitItem->item) { // item puede ser Producto o Servicio
                    // Si es servicio, ignorar inventario
                    if ($kitItem->esServicio())
                        continue;

                    $cantidadComponente = $kitItem->cantidad * $cantidad;

                    // Enriquecer motivo
                    $contextoComponente = $contexto;
                    $motivoOriginal = Arr::get($contexto, 'motivo', 'Movimiento');
                    $contextoComponente['motivo'] = "$motivoOriginal (Componente de Kit: {$producto->codigo})";

                    // Llamada recursiva
                    $this->ejecutarAjuste($kitItem->item, $tipo, $cantidadComponente, $contextoComponente);
                }
            }
            // El Kit en sí mismo no tiene inventario físico, retorna aquí.
            return;
        }

        $almacenId = Arr::get($contexto, 'almacen_id');
        if (!$almacenId) {
            // ✅ FIX #3: Usar lockForUpdate para evitar race conditions al seleccionar almacén
            $almacen = Almacen::where('estado', 'activo')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$almacen) {
                throw new RuntimeException('No hay almacenes activos disponibles.');
            }
            $almacenId = $almacen->id;
        } else {
            // Verificar si el almacén existe y está activo
            $almacen = Almacen::lockForUpdate()->find($almacenId);
            if (!$almacen || $almacen->estado !== 'activo') {
                throw new RuntimeException('El almacén especificado no existe o no está activo.');
            }
        }

        // Obtener o crear registro de inventario para este producto en este almacén
        // Usamos lockForUpdate para evitar condiciones de carrera
        $inventario = \App\Models\Inventario::where('producto_id', $producto->id)
            ->where('almacen_id', $almacenId)
            ->lockForUpdate()
            ->first();

        if (!$inventario) {
            $inventario = \App\Models\Inventario::create([
                'producto_id' => $producto->id,
                'almacen_id' => $almacenId,
                'cantidad' => ($tipo === 'salida' ? max(0, (int) ($producto->stock ?? 0)) : 0),
                'stock_minimo' => 0,
            ]);
            // Bloquear el registro recién creado para mantener consistencia dentro de la transacción
            $inventario = \App\Models\Inventario::where('id', $inventario->id)->lockForUpdate()->first();
        }

        if (!$inventario) {
            $almacenNombre = $almacen ? $almacen->nombre : "ID: {$almacenId}";
            throw new RuntimeException("No se pudo recuperar o crear el registro de inventario para el producto '{$producto->nombre}' en el almacén '{$almacenNombre}'. Verifique el contexto de empresa.");
        }

        $stockAnterior = $inventario->cantidad;

        // ✅ FIX #10: Validar ANTES del cálculo, no después
        if ($tipo === 'salida' && $stockAnterior < $cantidad) {
            $almacenNombre = $almacen ? $almacen->nombre : "ID: {$almacenId}";
            throw new RuntimeException("Stock insuficiente para el producto '{$producto->nombre}' en el almacén '{$almacenNombre}'. Disponible: {$stockAnterior}, Solicitado: {$cantidad}");
        }

        $nuevoStock = ($tipo === 'entrada')
            ? $stockAnterior + $cantidad
            : $stockAnterior - $cantidad;

        // Actualizar inventario específico
        $inventario->update(['cantidad' => $nuevoStock]);

        // Actualizar stock total del producto
        $totalStock = \App\Models\Inventario::where('producto_id', $producto->id)->sum('cantidad');
        $producto->update(['stock' => $totalStock]);

        // Registrar movimiento en inventario_movimientos
        $userId = Arr::get($contexto, 'user_id');
        if (!$userId && $this->usuarioAutenticado()) {
            $userId = Auth::id();
        }

        $referencia = Arr::get($contexto, 'referencia');
        $esObjeto = is_object($referencia);
        $referenciaType = $esObjeto ? get_class($referencia) : null;
        $referenciaId = $esObjeto ? $referencia->id : null;

        InventarioMovimiento::create([
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'almacen_id' => $almacenId,
            'almacen_nombre' => $almacen->nombre,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'stock_anterior' => $stockAnterior,
            'stock_posterior' => $nuevoStock,
            'motivo' => Arr::get($contexto, 'motivo', 'Movimiento de inventario'),
            'referencia_type' => $referenciaType,
            'referencia_id' => $referenciaId,
            'user_id' => $userId,
            'usuario_nombre' => $userId ? User::find($userId)?->name : null,
            'detalles' => Arr::get($contexto, 'detalles', []),
        ]);
    }

    protected function usuarioAutenticado(): bool
    {
        try {
            return Auth::check();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Verifica si un producto tiene stock suficiente para una salida.
     *
     * @param Producto $producto
     * @param int $cantidad
     * @return bool
     */
    public function tieneStockSuficiente(Producto $producto, int $cantidad): bool
    {
        return $producto->stock >= $cantidad;
    }

    /**
     * Obtiene el historial de movimientos de un producto.
     *
     * @param Producto $producto
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerHistorial(Producto $producto)
    {
        return $producto->movimientos()->with('user')->orderBy('created_at', 'desc')->get();
    }

    /**
     * Obtiene estadísticas generales de movimientos de inventario.
     *
     * @return array
     */
    public function obtenerEstadisticasGenerales()
    {
        return [
            'total_movimientos' => InventarioMovimiento::count(),
            'total_entradas' => InventarioMovimiento::where('tipo', 'entrada')->sum('cantidad'),
            'total_salidas' => InventarioMovimiento::where('tipo', 'salida')->sum('cantidad'),
            'productos_con_movimientos' => InventarioMovimiento::distinct('producto_id')->count('producto_id'),
            'usuarios_que_registraron' => InventarioMovimiento::distinct('user_id')->count('user_id'),
            'movimientos_hoy' => InventarioMovimiento::whereDate('created_at', today())->count(),
            'movimientos_este_mes' => InventarioMovimiento::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count(),
        ];
    }

    /**
     * Obtiene movimientos con filtros avanzados.
     *
     * @param array $filtros
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function obtenerMovimientosConFiltros($filtros = [])
    {
        return InventarioMovimiento::with(['producto', 'usuario']);

        if (!empty($filtros['producto_id'])) {
            $query->where('producto_id', $filtros['producto_id']);
        }

        if (!empty($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }

        if (!empty($filtros['motivo'])) {
            $query->where('motivo', 'like', '%' . $filtros['motivo'] . '%');
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('created_at', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('created_at', '<=', $filtros['fecha_hasta']);
        }

        if (!empty($filtros['user_id'])) {
            $query->where('user_id', $filtros['user_id']);
        }

        return $query;
    }

    /**
     * Obtiene productos con mayor movimiento de inventario.
     *
     * @param int $limite
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerProductosMasMovidos($limite = 10)
    {
        return Producto::select('productos.*', DB::raw('SUM(ABS(inventario_movimientos.cantidad)) as total_movido'))
            ->join('inventario_movimientos', 'productos.id', '=', 'inventario_movimientos.producto_id')
            ->groupBy('productos.id')
            ->orderBy('total_movido', 'desc')
            ->limit($limite)
            ->with('categoria', 'marca')
            ->get();
    }

    /**
     * Obtiene usuarios que más movimientos registran.
     *
     * @param int $limite
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerUsuariosMasActivos($limite = 10)
    {
        return User::select('users.*', DB::raw('COUNT(inventario_movimientos.id) as total_movimientos'))
            ->join('inventario_movimientos', 'users.id', '=', 'inventario_movimientos.user_id')
            ->groupBy('users.id')
            ->orderBy('total_movimientos', 'desc')
            ->limit($limite)
            ->get();
    }

    /**
     * Entrada de productos con manejo de lotes
     */
    protected function entradaConLote(Producto $producto, int $cantidad, array $contexto = []): void
    {
        $numeroLote = Arr::get($contexto, 'numero_lote');
        $fechaCaducidad = Arr::get($contexto, 'fecha_caducidad');
        $costoUnitario = Arr::get($contexto, 'costo_unitario');

        if (!$numeroLote) {
            throw new InvalidArgumentException('Número de lote requerido para productos que vencen');
        }

        // ✅ CRITICAL FIX: Check if we're already inside a transaction
        $skipTransaction = Arr::get($contexto, 'skip_transaction', false);
        $insideTransaction = DB::transactionLevel() > 0;

        if ($insideTransaction || $skipTransaction) {
            $this->ejecutarEntradaConLote($producto, $cantidad, $contexto, $numeroLote, $fechaCaducidad, $costoUnitario);
        } else {
            $this->db->transaction(function () use ($producto, $cantidad, $contexto, $numeroLote, $fechaCaducidad, $costoUnitario) {
                $this->ejecutarEntradaConLote($producto, $cantidad, $contexto, $numeroLote, $fechaCaducidad, $costoUnitario);
            });
        }
    }

    /**
     * Execute entrada con lote logic
     */
    protected function ejecutarEntradaConLote(Producto $producto, int $cantidad, array $contexto, string $numeroLote, $fechaCaducidad, $costoUnitario): void
    {
        $almacenId = Arr::get($contexto, 'almacen_id');
        if (!$almacenId) {
            $almacenId = Almacen::where('estado', 'activo')->orderBy('id')->first()?->id;
            if (!$almacenId) {
                throw new RuntimeException('No hay almacenes activos disponibles.');
            }
        }

        // Verificar si el almacén existe y está activo
        $almacen = Almacen::find($almacenId);
        if (!$almacen || $almacen->estado !== 'activo') {
            throw new RuntimeException('El almacén especificado no existe o no está activo.');
        }

        // Crear o actualizar lote
        // ✅ FIX: Use lockForUpdate to prevent race conditions
        $lote = Lote::where('producto_id', $producto->id)
            ->where('almacen_id', $almacenId)
            ->where('numero_lote', $numeroLote)
            ->lockForUpdate()
            ->first();

        if (!$lote) {
            $lote = Lote::create([
                'producto_id' => $producto->id,
                'almacen_id' => $almacenId,
                'numero_lote' => $numeroLote,
                'fecha_caducidad' => $fechaCaducidad,
                'cantidad_inicial' => 0,
                'cantidad_actual' => 0,
                'costo_unitario' => $costoUnitario,
            ]);
            // Lock the newly created record
            $lote = Lote::where('id', $lote->id)->lockForUpdate()->first();
        }

        $lote->increment('cantidad_inicial', $cantidad);
        $lote->increment('cantidad_actual', $cantidad);

        // Actualizar inventario general
        // ✅ FIX: Use lockForUpdate
        $inventario = \App\Models\Inventario::where('producto_id', $producto->id)
            ->where('almacen_id', $almacenId)
            ->lockForUpdate()
            ->first();

        if (!$inventario) {
            $inventario = \App\Models\Inventario::create([
                'producto_id' => $producto->id,
                'almacen_id' => $almacenId,
                'cantidad' => 0,
                'stock_minimo' => 0,
            ]);
            $inventario = \App\Models\Inventario::where('id', $inventario->id)->lockForUpdate()->first();
        }

        $stockAnterior = $inventario->cantidad;
        $inventario->increment('cantidad', $cantidad);

        // Actualizar stock total del producto
        $totalStock = \App\Models\Inventario::where('producto_id', $producto->id)->sum('cantidad');
        $producto->update(['stock' => $totalStock]);

        // Registrar movimiento
        $userId = Arr::get($contexto, 'user_id');
        if (!$userId && $this->usuarioAutenticado()) {
            $userId = Auth::id();
        }

        $referencia = Arr::get($contexto, 'referencia');
        $esObjeto = is_object($referencia);
        $referenciaType = $esObjeto ? get_class($referencia) : null;
        $referenciaId = $esObjeto ? $referencia->id : null;

        InventarioMovimiento::create([
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'almacen_id' => $almacenId,
            'almacen_nombre' => $almacen->nombre,
            'lote_id' => $lote->id,
            'tipo' => 'entrada',
            'cantidad' => $cantidad,
            'stock_anterior' => $stockAnterior,
            'stock_posterior' => $stockAnterior + $cantidad,
            'motivo' => Arr::get($contexto, 'motivo', 'Entrada con lote'),
            'referencia_type' => $referenciaType,
            'referencia_id' => $referenciaId,
            'user_id' => $userId,
            'usuario_nombre' => $userId ? User::find($userId)?->name : null,
            'detalles' => Arr::get($contexto, 'detalles', []),
        ]);
    }

    /**
     * Salida de productos con manejo de lotes (FIFO)
     */
    /**
     * Salida de productos con manejo de lotes (FIFO)
     * @return array Returns array of used lots [['lote' => Lote, 'cantidad' => int], ...]
     */
    protected function salidaConLote(Producto $producto, int $cantidad, array $contexto = []): array
    {
        // ✅ CRITICAL FIX: Check if we're already inside a transaction
        $skipTransaction = Arr::get($contexto, 'skip_transaction', false);
        $insideTransaction = DB::transactionLevel() > 0;

        if ($insideTransaction || $skipTransaction) {
            return $this->ejecutarSalidaConLote($producto, $cantidad, $contexto);
        } else {
            return $this->db->transaction(function () use ($producto, $cantidad, $contexto) {
                return $this->ejecutarSalidaConLote($producto, $cantidad, $contexto);
            });
        }
    }

    /**
     * Execute salida con lote logic
     */
    protected function ejecutarSalidaConLote(Producto $producto, int $cantidad, array $contexto): array
    {
        $almacenId = Arr::get($contexto, 'almacen_id');
        if (!$almacenId) {
            $almacenId = Almacen::where('estado', 'activo')->orderBy('id')->first()?->id;
            if (!$almacenId) {
                throw new RuntimeException('No hay almacenes activos disponibles.');
            }
        }

        // Verificar si el almacén existe y está activo
        $almacen = Almacen::find($almacenId);
        if (!$almacen || $almacen->estado !== 'activo') {
            throw new RuntimeException('El almacén especificado no existe o no está activo.');
        }

        // Obtener lotes disponibles ordenados por fecha de caducidad (FIFO)
        // ✅ FIX: Lock selected lots to prevent concurrent usage
        // ✅ CRITICAL FIX: Filter by almacen_id to ensure we use lots from the correct warehouse
        $lotes = $producto->lotes()
            ->where('almacen_id', $almacenId)
            ->where('cantidad_actual', '>', 0)
            ->where(function ($q) {
                $q->whereNull('fecha_caducidad')
                    ->orWhere('fecha_caducidad', '>', now());
            })
            ->orderBy('fecha_caducidad', 'asc')
            ->lockForUpdate() // Lock these rows
            ->get();

        $cantidadRestante = $cantidad;
        $lotesUsados = [];

        foreach ($lotes as $lote) {
            if ($cantidadRestante <= 0)
                break;

            $cantidadLote = min($cantidadRestante, $lote->cantidad_actual);
            $lote->decrement('cantidad_actual', $cantidadLote);
            $cantidadRestante -= $cantidadLote;
            $lotesUsados[] = ['lote' => $lote, 'cantidad' => $cantidadLote];
        }

        if ($cantidadRestante > 0) {
            throw new RuntimeException("Stock insuficiente. Faltan {$cantidadRestante} unidades del producto '{$producto->nombre}'.");
        }

        // Actualizar inventario general
        // ✅ FIX: Lock inventory row
        $inventario = \App\Models\Inventario::where('producto_id', $producto->id)
            ->where('almacen_id', $almacenId)
            ->lockForUpdate()
            ->first();

        if (!$inventario || $inventario->cantidad < $cantidad) {
            throw new RuntimeException("Stock insuficiente en almacén para el producto '{$producto->nombre}'.");
        }

        $stockAnterior = $inventario->cantidad;
        $inventario->decrement('cantidad', $cantidad);

        // Actualizar stock total del producto
        $totalStock = \App\Models\Inventario::where('producto_id', $producto->id)->sum('cantidad');
        $producto->update(['stock' => $totalStock]);

        // Registrar movimientos por lote
        $userId = Arr::get($contexto, 'user_id');
        if (!$userId && $this->usuarioAutenticado()) {
            $userId = Auth::id();
        }

        $referencia = Arr::get($contexto, 'referencia');
        $esObjeto = is_object($referencia);
        $referenciaType = $esObjeto ? get_class($referencia) : null;
        $referenciaId = $esObjeto ? $referencia->id : null;

        foreach ($lotesUsados as $loteData) {
            InventarioMovimiento::create([
                'producto_id' => $producto->id,
                'producto_nombre' => $producto->nombre,
                'almacen_id' => $almacenId,
                'almacen_nombre' => $almacen->nombre,
                'lote_id' => $loteData['lote']->id,
                'tipo' => 'salida',
                'cantidad' => $loteData['cantidad'],
                'stock_anterior' => $stockAnterior,
                'stock_posterior' => $stockAnterior - $cantidad,
                'motivo' => Arr::get($contexto, 'motivo', 'Salida con lote'),
                'referencia_type' => $referenciaType,
                'referencia_id' => $referenciaId,
                'user_id' => $userId,
                'usuario_nombre' => $userId ? User::find($userId)?->name : null,
                'detalles' => Arr::get($contexto, 'detalles', []),
            ]);
        }

        return $lotesUsados;
    }
}
