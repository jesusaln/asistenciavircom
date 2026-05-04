<?php

namespace App\Http\Controllers;

use App\Models\InventarioFisico;
use App\Models\InventarioFisicoItem;
use App\Models\Inventario;
use App\Models\Almacen;
use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class InventarioFisicoController extends Controller
{
    public function __construct(private readonly InventarioService $inventarioService)
    {
    }

    public function index()
    {
        $auditorias = InventarioFisico::with(['almacen', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Inventario/Fisico/Index', [
            'auditorias' => $auditorias
        ]);
    }

    public function create()
    {
        return Inertia::render('Inventario/Fisico/Create', [
            'almacenes' => Almacen::where('estado', 'activo')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'almacen_id' => 'required|exists:almacenes,id',
            'nombre' => 'required|string|max:255',
            'notas' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $audit = InventarioFisico::create([
                'empresa_id' => session('empresa_id', 1), // Default to 1 if not in session, but BelongsToEmpresa should handle it
                'almacen_id' => $validated['almacen_id'],
                'user_id' => Auth::id(),
                'nombre' => $validated['nombre'],
                'notas' => $validated['notas'],
                'estado' => 'borrador',
                'fecha_inicio' => now(),
            ]);

            // Snapshot current inventory
            $inventarios = Inventario::where('almacen_id', $validated['almacen_id'])
                ->where('cantidad', '>', 0)
                ->get();

            foreach ($inventarios as $inv) {
                InventarioFisicoItem::create([
                    'inventario_fisico_id' => $audit->id,
                    'producto_id' => $inv->producto_id,
                    'stock_sistema' => $inv->cantidad,
                    'stock_fisico' => $inv->cantidad, // Default to system stock
                    'diferencia' => 0,
                ]);
            }

            return redirect()->route('inventarios-fisicos.show', $audit->id)
                ->with('success', 'Sesión de inventario iniciada correctamente.');
        });
    }

    public function show($id)
    {
        $audit = InventarioFisico::with(['almacen', 'user', 'items.producto'])->findOrFail($id);

        return Inertia::render('Inventario/Fisico/Show', [
            'audit' => $audit
        ]);
    }

    public function updateItem(Request $request, $auditId, $itemId)
    {
        $validated = $request->validate([
            'stock_fisico' => 'required|numeric|min:0',
        ]);

        $item = InventarioFisicoItem::where('inventario_fisico_id', $auditId)->findOrFail($itemId);
        
        $item->update([
            'stock_fisico' => $validated['stock_fisico'],
            'diferencia' => $validated['stock_fisico'] - $item->stock_sistema,
        ]);

        return response()->json(['success' => true, 'item' => $item]);
    }

    public function procesar($id)
    {
        $audit = InventarioFisico::with('items.producto')->findOrFail($id);

        if ($audit->estado !== 'borrador') {
            return redirect()->back()->with('error', 'Esta auditoría ya ha sido procesada o cancelada.');
        }

        return DB::transaction(function () use ($audit) {
            foreach ($audit->items as $item) {
                if (abs($item->diferencia) > 0.0001) {
                    $tipo = $item->diferencia > 0 ? 'entrada' : 'salida';
                    $cantidad = abs($item->diferencia);

                    $this->inventarioService->{$tipo}($item->producto, $cantidad, [
                        'almacen_id' => $audit->almacen_id,
                        'motivo' => "Ajuste por Inventario Físico: {$audit->nombre}",
                        'referencia' => $audit,
                        'user_id' => Auth::id(),
                        'detalles' => [
                            'audit_id' => $audit->id,
                            'stock_sistema' => $item->stock_sistema,
                            'stock_fisico' => $item->stock_fisico,
                        ]
                    ]);
                    
                    $item->update(['ajustado' => true]);
                }
            }

            $audit->update([
                'estado' => 'procesado',
                'fecha_fin' => now(),
            ]);

            return redirect()->route('inventarios-fisicos.show', $audit->id)
                ->with('success', 'Inventario procesado y ajustes aplicados.');
        });
    }
}
