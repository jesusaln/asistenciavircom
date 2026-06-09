<?php

namespace App\Http\Controllers;

use App\Models\Carro;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Traits\ImageOptimizerTrait;

class CarroController extends Controller
{
    use ImageOptimizerTrait;
    // Mostrar lista de carros
    public function index(Request $request)
    {
        try {
            $query = Carro::query();

            // Filtros
            if ($search = trim($request->input('search', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('marca', 'like', "%{$search}%")
                        ->orWhere('modelo', 'like', "%{$search}%")
                        ->orWhere('placa', 'like', "%{$search}%")
                        ->orWhere('numero_serie', 'like', "%{$search}%");
                });
            }

            if ($combustible = $request->input('combustible')) {
                $query->where('combustible', $combustible);
            }

            if ($estado = $request->input('estado')) {
                if ($estado === '1') {
                    $query->where('activo', '=', true);
                } elseif ($estado === '0') {
                    $query->where('activo', '=', false);
                }
            }

            // Ordenamiento
            $sortBy = $request->input('sort_by', 'marca');
            $sortDirection = $request->input('sort_direction', 'asc');

            $validSortFields = ['marca', 'modelo', 'anio', 'precio', 'kilometraje', 'created_at'];
            if (!in_array($sortBy, $validSortFields)) {
                $sortBy = 'marca';
            }

            $query->orderBy($sortBy, $sortDirection);

            // Paginación
            $perPage = min((int) $request->input('per_page', 10), 50);
            $carros = $query->paginate($perPage)->appends($request->query());

            // Estadísticas mejoradas
            $stats = [
                'total' => Carro::count(),
                'activos' => Carro::where('activo', '=', true)->count(),
                'inactivos' => Carro::where('activo', '=', false)->count(),
                'gasolina' => Carro::where('combustible', 'Gasolina')->count(),
                'diesel' => Carro::where('combustible', 'Diésel')->count(),
                'electrico' => Carro::where('combustible', 'Eléctrico')->count(),
                'hibrido' => Carro::where('combustible', 'Híbrido')->count(),
                'nuevos_mes' => Carro::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ];

            // Mapear fotos
            $carros->getCollection()->transform(function ($carro) {
                if ($carro->foto) {
                    $carro->foto = Storage::url($carro->foto);
                }
                return $carro;
            });

            return Inertia::render('Carros/Index', [
                'carros' => $carros,
                'estadisticas' => $stats,
                'filters' => $request->only(['search', 'combustible']),
                'sorting' => ['sort_by' => $sortBy, 'sort_direction' => $sortDirection],
                'pagination' => [
                    'current_page' => $carros->currentPage(),
                    'last_page' => $carros->lastPage(),
                    'per_page' => $carros->perPage(),
                    'from' => $carros->firstItem(),
                    'to' => $carros->lastItem(),
                    'total' => $carros->total(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en CarroController@index: ' . $e->getMessage());
            return redirect('/carros')->with('error', 'Error al cargar los carros.');
        }
    }

    // Mostrar formulario para crear un nuevo carro
    public function create()
    {
        return Inertia::render('Carros/Create');
    }

    // Guardar un nuevo carro
    public function store(Request $request)
    {
        $validated = $request->validate([
            'marca' => 'required|string|max:255',
            'modelo' => 'required|string|max:255',
            'anio' => 'required|integer|min:1900|max:' . date('Y'),
            'color' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
            'numero_serie' => 'required|string|max:255|unique:carros,numero_serie',
            'combustible' => 'required|in:Gasolina,Diésel,Eléctrico,Híbrido',
            'kilometraje' => 'required|integer|min:0',
            'placa' => 'nullable|string|max:20',
            'foto' => 'nullable|image|max:2048', // Máximo 2MB
            'activo' => 'required|boolean',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $this->saveImageAsWebP($request->file('foto'), 'fotos_carros');
        }

        // Asegurar que activo sea un booleano estricto
        if (isset($validated['activo'])) {
            $validated['activo'] = (bool) $validated['activo'];
        }

        Carro::create($validated);

        return redirect('/carros')->with('success', 'Carro creado exitosamente.');
    }

    // Mostrar formulario para editar un carro
    public function edit(Carro $carro)
    {
        if ($carro->foto) {
            $carro->foto = Storage::url($carro->foto); // Genera la URL pública
        }

        return Inertia::render('Carros/Edit', ['carro' => $carro]);
    }

    // Actualizar un carro existente
    public function update(Request $request, Carro $carro)
    {
        $validated = $request->validate([
            'marca' => 'required|string|max:255',
            'modelo' => 'required|string|max:255',
            'anio' => 'required|integer|min:1900|max:' . date('Y'),
            'color' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
            'numero_serie' => 'required|string|max:255|unique:carros,numero_serie,' . $carro->id,
            'combustible' => 'required|in:Gasolina,Diésel,Eléctrico,Híbrido',
            'kilometraje' => 'required|integer|min:0',
            'placa' => 'nullable|string|max:20',
            'foto' => 'nullable|image|max:2048', // Máximo 2MB
            'activo' => 'required|boolean',
        ]);

        if ($request->hasFile('foto')) {
            // Eliminar imagen anterior si existe
            if ($carro->foto) {
                Storage::disk('public')->delete($carro->foto);
            }
            $validated['foto'] = $this->saveImageAsWebP($request->file('foto'), 'fotos_carros');
        }

        // Asegurar que activo sea un booleano estricto
        if (isset($validated['activo'])) {
            $validated['activo'] = (bool) $validated['activo'];
        }

        $carro->update($validated);

        return redirect('/carros')->with('success', 'Carro actualizado exitosamente.');
    }

    // Eliminar un carro
    public function destroy(Carro $carro)
    {
        $carro->delete();

        return redirect('/carros')->with('success', 'Carro eliminado exitosamente.');
    }

    // Exportar carros a CSV
    public function export(Request $request)
    {
        try {
            $query = Carro::query();

            // Aplicar los mismos filtros que en index
            if ($search = trim($request->input('search', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('marca', 'like', "%{$search}%")
                        ->orWhere('modelo', 'like', "%{$search}%")
                        ->orWhere('placa', 'like', "%{$search}%")
                        ->orWhere('numero_serie', 'like', "%{$search}%");
                });
            }

            if ($combustible = $request->input('combustible')) {
                $query->where('combustible', $combustible);
            }

            if ($estado = $request->input('estado')) {
                if ($estado === '1') {
                    $query->where('activo', '=', true);
                } elseif ($estado === '0') {
                    $query->where('activo', '=', false);
                }
            }

            if ($estado = $request->input('estado')) {
                if ($estado === '1') {
                    $query->where('activo', '=', true);
                } elseif ($estado === '0') {
                    $query->where('activo', '=', false);
                }
            }

            $carros = $query->get();

            $filename = 'carros_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($carros) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'ID',
                    'Marca',
                    'Modelo',
                    'Año',
                    'Color',
                    'Precio',
                    'Número de Serie',
                    'Combustible',
                    'Kilometraje',
                    'Placa',
                    'Fecha Creación'
                ]);

                foreach ($carros as $carro) {
                    fputcsv($file, [
                        $carro->id,
                        $carro->marca,
                        $carro->modelo,
                        $carro->anio,
                        $carro->color,
                        $carro->precio,
                        $carro->numero_serie,
                        $carro->combustible,
                        $carro->kilometraje,
                        $carro->placa ?? '',
                        $carro->created_at?->format('d/m/Y H:i:s')
                    ]);
                }
                fclose($file);
            };

            Log::info('Exportación de carros', ['total' => $carros->count()]);

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error en exportación de carros: ' . $e->getMessage());
            return redirect('/carros')->with('error', 'Error al exportar los carros.');
        }
    }
}
