<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventarioFisico;
use App\Models\InventarioFisicoItem;
use App\Models\Almacen;
use App\Models\Producto;
use App\Support\EmpresaResolver;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventarioFisicoController extends Controller
{
    public function __construct(
        private readonly InventarioService $inventarioService
    ) {}

    public function index()
    {
        // El admin ve las que están en borrador y en revisión, incluyendo quién la hizo
        $auditorias = InventarioFisico::with(['almacen', 'user'])
            ->whereIn('estado', ['borrador', 'revision_pendiente'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $auditorias
        ]);
    }

    public function show($id)
    {
        $audit = InventarioFisico::with(['almacen', 'user', 'items.producto'])->findOrFail($id);

        // Calcular totales de discrepancia
        $stats = [
            'faltantes' => 0,
            'sobrantes' => 0,
            'valor_perdida' => 0,
            'valor_ganancia' => 0,
            'items_con_diferencia' => 0
        ];

        foreach ($audit->items as $item) {
            $diff = $item->stock_fisico - $item->stock_sistema;
            if ($diff < 0) {
                $stats['faltantes'] += abs($diff);
                $stats['valor_perdida'] += abs($diff) * ($item->producto->precio_compra ?? 0);
                $stats['items_con_diferencia']++;
            } elseif ($diff > 0) {
                $stats['sobrantes'] += $diff;
                $stats['valor_ganancia'] += $diff * ($item->producto->precio_compra ?? 0);
                $stats['items_con_diferencia']++;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $audit,
            'stats' => $stats
        ]);
    }

    public function updateItem(Request $request, $auditId, $itemId)
    {
        $audit = InventarioFisico::findOrFail($auditId);
        
        if ($audit->estado !== 'borrador') {
            return response()->json(['success' => false, 'message' => 'No se puede editar una auditoría en revisión o completada'], 422);
        }

        $validated = $request->validate([
            'stock_fisico' => 'required|numeric|min:0',
        ]);

        $item = InventarioFisicoItem::where('inventario_fisico_id', $auditId)->findOrFail($itemId);
        
        $item->update([
            'stock_fisico' => $validated['stock_fisico'],
            'diferencia' => $validated['stock_fisico'] - $item->stock_sistema,
        ]);

        return response()->json([
            'success' => true,
            'item' => $item
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'almacen_id' => 'required|exists:almacenes,id',
            'nombre' => 'required|string|max:255',
        ]);

        $empresaId = Auth::user()?->empresa_id ?? EmpresaResolver::resolveId();
        if (!$empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo determinar la empresa para crear el inventario fisico.',
            ], 403);
        }

        $audit = InventarioFisico::create([
            'empresa_id' => $empresaId,
            'almacen_id' => $validated['almacen_id'],
            'user_id' => Auth::id(),
            'nombre' => $validated['nombre'],
            'estado' => 'borrador',
            'fecha_inicio' => now(),
        ]);

        // Snapshots items
        $inventarios = \App\Models\Inventario::where('almacen_id', $validated['almacen_id'])->get();
        foreach ($inventarios as $inv) {
            InventarioFisicoItem::create([
                'inventario_fisico_id' => $audit->id,
                'producto_id' => $inv->producto_id,
                'stock_sistema' => $inv->cantidad,
                'stock_fisico' => $inv->cantidad,
                'diferencia' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $audit->load(['items.producto', 'user'])
        ]);
    }

    /**
     * El técnico envía la auditoría a revisión
     */
    public function finalize($id)
    {
        $audit = InventarioFisico::findOrFail($id);
        
        $audit->update([
            'estado' => 'revision_pendiente',
            'fecha_fin' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Auditoría enviada a revisión por el Superadmin.',
            'data' => $audit
        ]);
    }

    /**
     * El Superadmin aprueba y aplica los cambios al stock real
     */
    public function approve($id)
    {
        $audit = InventarioFisico::with('items.producto')->findOrFail($id);
        
        if ($audit->estado !== 'revision_pendiente') {
            return response()->json(['success' => false, 'message' => 'Solo se pueden aprobar auditorías en revisión'], 422);
        }

        try {
            DB::transaction(function () use ($audit) {
                foreach ($audit->items as $item) {
                    $diff = $item->stock_fisico - $item->stock_sistema;
                    
                    if ($diff != 0) {
                        $tipo = $diff > 0 ? 'entrada' : 'salida';
                        $cantidad = abs($diff);
                        
                        $this->inventarioService->{$tipo}($item->producto, $cantidad, [
                            'almacen_id' => $audit->almacen_id,
                            'motivo' => "Ajuste por Auditoría Física: {$audit->nombre}",
                            'referencia' => $audit,
                            'user_id' => Auth::id(),
                            'skip_transaction' => true
                        ]);
                    }
                }

                $audit->update([
                    'estado' => 'completado',
                    'notas' => 'Aprobado por ' . Auth::user()->name . ' el ' . now()->format('d/m/Y H:i')
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Auditoría aprobada y stock ajustado correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aplicar ajustes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * El Superadmin rechaza la auditoría y la devuelve a borrador
     */
    public function reject(Request $request, $id)
    {
        $audit = InventarioFisico::findOrFail($id);
        
        $audit->update([
            'estado' => 'borrador',
            'notas' => 'Rechazada por Admin: ' . $request->input('motivo', 'No especificado')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Auditoría rechazada y devuelta a borrador.'
        ]);
    }
}
