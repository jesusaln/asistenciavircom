<?php

namespace App\Http\Controllers;

use App\Models\CajaChica;
use App\Http\Controllers\Traits\BuildCajaChicaQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Traits\ImageOptimizerTrait;

class CajaChicaController extends Controller
{
    use BuildCajaChicaQuery, ImageOptimizerTrait;

    public function index(Request $request)
    {
        $filters = array_merge([
            'tipo' => null,
            'q' => '',
            'desde' => null,
            'hasta' => null,
            'sort_by' => null,
            'sort_dir' => null,
            'per_page' => 10,
            'categoria' => null,
        ], $request->only(['tipo', 'q', 'desde', 'hasta', 'sort_by', 'sort_dir', 'per_page', 'categoria']));
        $sortBy = in_array($filters['sort_by'] ?? '', ['fecha', 'monto', 'created_at', 'concepto', 'usuario', 'categoria']) ? $filters['sort_by'] : 'fecha';
        $sortDir = (($filters['sort_dir'] ?? '') === 'asc') ? 'asc' : 'desc';
        $perPage = in_array((int) ($filters['per_page'] ?? 10), [10, 25, 50, 100]) ? (int) $filters['per_page'] : 10;

        $baseQuery = $this->cajaChicaBaseQuery($filters, $sortBy, $sortDir);

        $movimientos = (clone $baseQuery)
            ->when($sortBy === 'usuario', function ($query) use ($sortDir) {
                $query->orderBy('usuario_nombre', $sortDir);
            }, function ($query) use ($sortBy, $sortDir) {
                $query->orderBy($sortBy, $sortDir);
            })
            ->orderBy('caja_chica.created_at', 'desc')
            ->with(['user', 'adjuntos'])
            ->paginate($perPage)
            ->withQueryString();

        $totalIngresos = (clone $baseQuery)->where('tipo', 'ingreso')->sum('monto');
        $totalEgresos = (clone $baseQuery)->where('tipo', 'egreso')->sum('monto');
        $balance = $totalIngresos - $totalEgresos;

        $trend = (clone $baseQuery)
            ->selectRaw("fecha, sum(case when tipo = 'ingreso' then monto else -monto end) as neto")
            ->groupBy('fecha')
            ->orderBy('fecha', 'asc')
            ->limit(30)
            ->get();

        return Inertia::render('CajaChica/Index', [
            'movimientos' => $movimientos,
            'balance' => (float) $balance,
            'totalIngresos' => (float) $totalIngresos,
            'totalEgresos' => (float) $totalEgresos,
            'trend' => $trend,
            'filters' => [
                'tipo' => $filters['tipo'] ?? null,
                'q' => $filters['q'] ?? '',
                'desde' => $filters['desde'] ?? null,
                'hasta' => $filters['hasta'] ?? null,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
                'categoria' => $filters['categoria'] ?? '',
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'concepto' => 'required|string|max:255',
            'categoria' => 'nullable|string|max:100',
            'monto' => 'required|numeric|min:0.01',
            'tipo' => 'required|in:ingreso,egreso',
            'fecha' => 'required|date',
            'nota' => 'nullable|string',
            'comprobante' => 'nullable|file|image|max:2048',
            'comprobantes.*' => 'nullable|file|image|max:2048',
        ]);

        $path = null;
        if ($request->hasFile('comprobante')) {
            $path = $this->saveImageAsWebP($request->file('comprobante'), 'caja-chica');
        }
        $adjuntos = [];
        if ($request->hasFile('comprobantes')) {
            foreach ($request->file('comprobantes') as $file) {
                $adjuntos[] = $this->saveImageAsWebP($file, 'caja-chica');
            }
        }

        $caja = CajaChica::create([
            'concepto' => $validated['concepto'],
            'categoria' => $validated['categoria'] ?? null,
            'monto' => $validated['monto'],
            'tipo' => $validated['tipo'],
            'fecha' => $validated['fecha'],
            'nota' => $validated['nota'] ?? null,
            'comprobante_path' => $path,
            'user_id' => Auth::id(),
        ]);

        if (!empty($adjuntos)) {
            foreach ($adjuntos as $filePath) {
                $caja->adjuntos()->create(['path' => $filePath]);
            }
        }

        return redirect()->back()->with('success', 'Movimiento registrado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $movimiento = CajaChica::findOrFail($id);

        $validated = $request->validate([
            'concepto' => 'required|string|max:255',
            'categoria' => 'nullable|string|max:100',
            'monto' => 'required|numeric|min:0.01',
            'tipo' => 'required|in:ingreso,egreso',
            'fecha' => 'required|date',
            'nota' => 'nullable|string',
            'comprobante' => 'nullable|file|image|max:2048',
            'eliminar_comprobante' => 'nullable|boolean',
            'comprobantes.*' => 'nullable|file|image|max:2048',
            'eliminar_adjuntos' => 'array',
            'eliminar_adjuntos.*' => 'integer',
        ]);

        if ($request->hasFile('comprobante')) {
            if ($movimiento->comprobante_path) {
                Storage::disk('public')->delete($movimiento->comprobante_path);
            }
            $movimiento->comprobante_path = $this->saveImageAsWebP($request->file('comprobante'), 'caja-chica');
        } elseif ($request->boolean('eliminar_comprobante') && $movimiento->comprobante_path) {
            Storage::disk('public')->delete($movimiento->comprobante_path);
            $movimiento->comprobante_path = null;
        }

        if ($request->hasFile('comprobantes')) {
            foreach ($request->file('comprobantes') as $file) {
                $movimiento->adjuntos()->create([
                    'path' => $this->saveImageAsWebP($file, 'caja-chica'),
                ]);
            }
        }

        if (!empty($validated['eliminar_adjuntos'] ?? [])) {
            $adjuntos = $movimiento->adjuntos()->whereIn('id', $validated['eliminar_adjuntos'])->get();
            foreach ($adjuntos as $adj) {
                Storage::disk('public')->delete($adj->path);
                $adj->delete();
            }
        }

        $movimiento->update([
            'concepto' => $validated['concepto'],
            'categoria' => $validated['categoria'] ?? null,
            'monto' => $validated['monto'],
            'tipo' => $validated['tipo'],
            'fecha' => $validated['fecha'],
            'nota' => $validated['nota'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Movimiento actualizado correctamente.');
    }

    public function destroy($id)
    {
        $movimiento = CajaChica::findOrFail($id);

        if ($movimiento->comprobante_path) {
            Storage::disk('public')->delete($movimiento->comprobante_path);
        }

        $movimiento->delete();

        return redirect()->back()->with('success', 'Movimiento eliminado correctamente.');
    }

    public function export(Request $request)
    {
        $filters = $request->only(['tipo', 'q', 'desde', 'hasta', 'sort_by', 'sort_dir', 'categoria']);
        $sortBy = in_array($filters['sort_by'] ?? '', ['fecha', 'monto', 'created_at', 'concepto', 'usuario']) ? $filters['sort_by'] : 'fecha';
        $sortDir = (($filters['sort_dir'] ?? '') === 'asc') ? 'asc' : 'desc';

        $query = $this->cajaChicaBaseQuery($filters, $sortBy, $sortDir)
            ->with('user');

        $movimientos = $query
            ->when($sortBy === 'usuario', function ($q) use ($sortDir) {
                $q->orderBy('usuario_nombre', $sortDir);
            }, function ($q) use ($sortBy, $sortDir) {
                $q->orderBy($sortBy, $sortDir);
            })
            ->orderBy('caja_chica.created_at', 'desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="caja_chica.csv"',
        ];

        $callback = function () use ($movimientos) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Fecha', 'Concepto', 'Categoria', 'Tipo', 'Monto', 'Nota', 'Usuario']);
            foreach ($movimientos as $mov) {
                fputcsv($handle, [
                    $mov->fecha?->format('Y-m-d'),
                    $mov->concepto,
                    $mov->categoria,
                    $mov->tipo,
                    number_format((float) $mov->monto, 2, '.', ''),
                    $mov->nota,
                    $mov->user?->name,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
