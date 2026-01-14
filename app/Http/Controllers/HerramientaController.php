<?php

namespace App\Http\Controllers;

use App\Models\Herramienta;
use App\Models\CategoriaHerramienta;
use App\Models\HistorialHerramienta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Traits\ImageOptimizerTrait;

class HerramientaController extends Controller
{
    use ImageOptimizerTrait;
    public function index(Request $request)
    {
        $search = (string) $request->query('search', '');
        $estado = (string) $request->query('estado', '');
        $categoria = (string) $request->query('categoria', '');
        $mantenimiento = (string) $request->query('mantenimiento', '');

        $query = Herramienta::query()
            ->with(['categoriaHerramienta', 'tecnico'])
            ->select(
                'id',
                'nombre',
                'numero_serie',
                'estado',
                'foto',
                'categoria_id',
                'tecnico_id',
                'fecha_ultimo_mantenimiento',
                'dias_para_mantenimiento',
                'vida_util_meses',
                'requiere_mantenimiento',
                'created_at'
            );

        // Búsqueda avanzada
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('numero_serie', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        // Filtro por estado
        if ($estado !== '') {
            $query->where('estado', $estado);
        }

        // Filtro por categoría
        if ($categoria !== '') {
            if ($categoria === 'sin_categoria') {
                $query->where(function ($q) {
                    $q->whereNull('categoria_id')->whereNull('categoria');
                });
            } else {
                $query->where('categoria_id', $categoria);
            }
        }

        // Filtro por mantenimiento
        if ($mantenimiento !== '') {
            switch ($mantenimiento) {
                case 'requiere':
                    $query->where('requiere_mantenimiento', true)
                        ->whereRaw('(CURRENT_DATE - fecha_ultimo_mantenimiento) >= dias_para_mantenimiento');
                    break;
                case 'proximo':
                    $query->where('requiere_mantenimiento', true)
                        ->whereRaw('(CURRENT_DATE - fecha_ultimo_mantenimiento) >= (dias_para_mantenimiento * 0.8)');
                    break;
                case 'vencida':
                    $query->where('requiere_mantenimiento', true)
                        ->whereRaw('(CURRENT_DATE - fecha_ultimo_mantenimiento) >= dias_para_mantenimiento');
                    break;
            }
        }

        $herramientas = $query->orderByDesc('created_at')->paginate(12)->withQueryString();

        // Estadísticas generales
        $estadisticas = [
            'total' => Herramienta::count(),
            'disponibles' => Herramienta::disponibles()->count(),
            'asignadas' => Herramienta::asignadas()->count(),
            'mantenimiento' => Herramienta::enMantenimiento()->count(),
            'baja' => Herramienta::where('estado', Herramienta::ESTADO_BAJA)->count(),
            'perdida' => Herramienta::where('estado', Herramienta::ESTADO_PERDIDA)->count(),
            'requieren_mantenimiento' => Herramienta::requierenMantenimiento()
                ->whereRaw('(CURRENT_DATE - fecha_ultimo_mantenimiento) >= dias_para_mantenimiento')->count(),
        ];

        return Inertia::render('Herramientas/Index', [
            'herramientas' => $herramientas,
            'estadisticas' => $estadisticas,
            'categorias' => CategoriaHerramienta::orderBy('nombre')->get(),
            'filters' => [
                'search' => $search,
                'estado' => $estado,
                'categoria' => $categoria,
                'mantenimiento' => $mantenimiento,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Herramientas/Create', [
            'categorias' => CategoriaHerramienta::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'numero_serie' => 'nullable|string|max:255|unique:herramientas,numero_serie',
            'estado' => 'nullable|string|in:disponible,asignada,mantenimiento,baja,perdida',
            'descripcion' => 'nullable|string|max:1000',
            'foto' => 'nullable|image|max:5120|mimes:jpeg,png,jpg,gif,webp',
            'categoria_id' => 'nullable|exists:categoria_herramientas,id',
            'vida_util_meses' => 'nullable|integer|min:1|max:120',
            'costo_reemplazo' => 'nullable|numeric|min:0|max:999999.99',
            'dias_para_mantenimiento' => 'nullable|integer|min:1|max:365',
            'requiere_mantenimiento' => 'nullable|boolean',
        ]);

        $fotoPath = null;

        try {
            DB::beginTransaction();

            // Solo procesar la foto si se proporciona
            if ($request->hasFile('foto')) {
                $fotoPath = $this->saveImageAsWebP($request->file('foto'), 'herramientas');
                $data['foto'] = $fotoPath;
            }

            // Estado por defecto
            if (empty($data['estado'])) {
                $data['estado'] = Herramienta::ESTADO_DISPONIBLE;
            }

            Herramienta::create($data);

            DB::commit();

            return redirect()->route('herramientas.index')->with('success', 'Herramienta creada correctamente');
        } catch (\Exception $e) {
            DB::rollBack();

            // Eliminar foto si se subió pero falló la creación
            if ($fotoPath && Storage::disk('public')->exists($fotoPath)) {
                Storage::disk('public')->delete($fotoPath);
            }

            \Log::error('Error al crear herramienta: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear la herramienta: ' . $e->getMessage());
        }
    }

    public function edit(Herramienta $herramienta)
    {
        $herramientaData = $herramienta->only([
            'id',
            'nombre',
            'numero_serie',
            'estado',
            'descripcion',
            'foto',
            'categoria_id',
            'vida_util_meses',
            'costo_reemplazo',
            'dias_para_mantenimiento',
            'requiere_mantenimiento'
        ]);

        \Log::info('🔧 Herramienta Edit - Datos enviados al frontend:', $herramientaData);

        return Inertia::render('Herramientas/Edit', [
            'herramienta' => $herramientaData,
            'categorias' => CategoriaHerramienta::orderBy('nombre')->get(),
        ]);
    }

    public function show(Herramienta $herramienta)
    {
        $herramienta->load(['categoriaHerramienta', 'tecnico', 'historial']);

        return Inertia::render('Herramientas/Show', [
            'herramienta' => [
                'id' => $herramienta->id,
                'nombre' => $herramienta->nombre,
                'numero_serie' => $herramienta->numero_serie,
                'estado' => $herramienta->estado,
                'descripcion' => $herramienta->descripcion,
                'foto' => $herramienta->foto,
                'categoria_id' => $herramienta->categoria_id,
                'categoria_herramienta' => $herramienta->categoriaHerramienta,
                'tecnico_id' => $herramienta->tecnico_id,
                'tecnico' => $herramienta->tecnico,
                'fecha_ultimo_mantenimiento' => $herramienta->fecha_ultimo_mantenimiento,
                'dias_para_mantenimiento' => $herramienta->dias_para_mantenimiento,
                'vida_util_meses' => $herramienta->vida_util_meses,
                'costo_reemplazo' => $herramienta->costo_reemplazo,
                'requiere_mantenimiento' => $herramienta->requiere_mantenimiento,
                'fecha_asignacion' => $herramienta->fecha_asignacion,
                'fecha_recepcion' => $herramienta->fecha_recepcion,
                'dias_desde_ultimo_mantenimiento' => $herramienta->dias_desde_ultimo_mantenimiento,
                'dias_para_proximo_mantenimiento' => $herramienta->dias_para_proximo_mantenimiento,
                'porcentaje_vida_util' => $herramienta->porcentaje_vida_util,
                'vida_util_proxima_a_vencer' => $herramienta->vidaUtilProximaAVencer(),
                'necesita_mantenimiento' => $herramienta->necesitaMantenimiento(),
                'created_at' => $herramienta->created_at,
            ],
            'estadisticas' => $herramienta->estadisticas,
            'historial_completo' => $herramienta->historial_completo,
        ]);
    }

    public function update(Request $request, Herramienta $herramienta)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'numero_serie' => 'nullable|string|max:255|unique:herramientas,numero_serie,' . $herramienta->id,
            'estado' => 'nullable|string|in:disponible,asignada,mantenimiento,baja,perdida',
            'descripcion' => 'nullable|string|max:1000',
            'foto' => 'nullable|image|max:5120|mimes:jpeg,png,jpg,gif,webp',
            'categoria_id' => 'nullable|exists:categoria_herramientas,id',
            'vida_util_meses' => 'nullable|integer|min:1|max:120',
            'costo_reemplazo' => 'nullable|numeric|min:0|max:999999.99',
            'dias_para_mantenimiento' => 'nullable|integer|min:1|max:365',
            'requiere_mantenimiento' => 'nullable|boolean',
        ]);

        $fotoAntigua = $herramienta->foto;
        $fotoNueva = null;

        try {
            DB::beginTransaction();

            // Estado por defecto si no se proporciona
            if (empty($data['estado'])) {
                $data['estado'] = $herramienta->estado; // Mantener el estado actual
            }

            // Solo actualizar la foto si se proporciona una nueva
            if ($request->hasFile('foto')) {
                $fotoNueva = $this->saveImageAsWebP($request->file('foto'), 'herramientas');
                $data['foto'] = $fotoNueva;
            } else {
                // Mantener la foto actual si no se proporciona una nueva
                unset($data['foto']);
            }

            // Actualizar la herramienta
            $herramienta->update($data);

            // Si todo fue exitoso y hay foto nueva, eliminar la antigua
            if ($fotoNueva && $fotoAntigua) {
                Storage::disk('public')->delete($fotoAntigua);
            }

            DB::commit();

            return redirect()->route('herramientas.index')->with('success', 'Herramienta actualizada correctamente');
        } catch (\Exception $e) {
            DB::rollBack();

            // Eliminar foto nueva si se subió pero falló la actualización
            if ($fotoNueva && Storage::disk('public')->exists($fotoNueva)) {
                Storage::disk('public')->delete($fotoNueva);
            }

            \Log::error('Error al actualizar herramienta: ' . $e->getMessage(), [
                'herramienta_id' => $herramienta->id,
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar la herramienta: ' . $e->getMessage());
        }
    }

    public function destroy(Herramienta $herramienta)
    {
        // Usar método del modelo para validación (evita duplicación de lógica)
        if (!$herramienta->puedeSerEliminada()) {
            $mensaje = 'No se puede eliminar esta herramienta. ';

            if ($herramienta->estado !== Herramienta::ESTADO_DISPONIBLE) {
                $mensaje .= 'Estado actual: ' . $herramienta->estado_label . '. ';
            }

            if ($herramienta->tecnico_id !== null) {
                $mensaje .= 'Está asignada a un técnico. ';
            }

            if ($herramienta->historial()->where('created_at', '>=', now()->subDays(30))->exists()) {
                $mensaje .= 'Tiene historial reciente. Considere marcarla como "De Baja" en su lugar.';
            }

            return redirect()->route('herramientas.index')->with('error', $mensaje);
        }

        if ($herramienta->foto) {
            Storage::disk('public')->delete($herramienta->foto);
        }

        $herramienta->delete();
        return redirect()->route('herramientas.index')->with('success', 'Herramienta eliminada correctamente');
    }

    public function dashboard()
    {
        $estadisticas = [
            'total_herramientas' => Herramienta::count(),
            'herramientas_disponibles' => Herramienta::disponibles()->count(),
            'herramientas_asignadas' => Herramienta::asignadas()->count(),
            'herramientas_mantenimiento' => Herramienta::enMantenimiento()->count(),
            'herramientas_baja' => Herramienta::where('estado', Herramienta::ESTADO_BAJA)->count(),
            'herramientas_perdidas' => Herramienta::where('estado', Herramienta::ESTADO_PERDIDA)->count(),
            'herramientas_requieren_mantenimiento' => Herramienta::requierenMantenimiento()
                ->whereRaw('(CURRENT_DATE - fecha_ultimo_mantenimiento) >= dias_para_mantenimiento')->count(),
            'herramientas_proximo_mantenimiento' => Herramienta::requierenMantenimiento()
                ->whereRaw('(CURRENT_DATE - fecha_ultimo_mantenimiento) >= (dias_para_mantenimiento * 0.8)')
                ->whereRaw('(CURRENT_DATE - fecha_ultimo_mantenimiento) < dias_para_mantenimiento')->count(),
        ];

        // Herramientas que requieren mantenimiento urgente
        $mantenimiento_urgente = Herramienta::requierenMantenimiento()
            ->whereRaw('(CURRENT_DATE - fecha_ultimo_mantenimiento) >= dias_para_mantenimiento')
            ->with(['categoriaHerramienta', 'tecnico'])
            ->limit(10)
            ->get();

        // Herramientas próximas a vencer vida útil
        $vida_util_proxima = Herramienta::whereNotNull('vida_util_meses')
            ->whereNotNull('fecha_ultimo_mantenimiento')
            ->whereRaw('(CURRENT_DATE - fecha_ultimo_mantenimiento) >= (vida_util_meses * 30 * 0.8)')
            ->with(['categoriaHerramienta', 'tecnico'])
            ->limit(10)
            ->get();

        // Estadísticas por categoría
        $por_categoria = Herramienta::select('categoria_id', DB::raw('count(*) as total'))
            ->with('categoriaHerramienta')
            ->groupBy('categoria_id')
            ->get()
            ->map(function ($item) {
                return [
                    'categoria' => $item->categoriaHerramienta?->nombre ?? 'Sin categoría',
                    'total' => $item->total,
                ];
            });

        // Herramientas más utilizadas
        $mas_utilizadas = Herramienta::select(
            'herramientas.id',
            'herramientas.nombre',
            'herramientas.numero_serie',
            'herramientas.estado',
            'herramientas.foto',
            DB::raw('COUNT(historial_herramientas.id) as usos')
        )
            ->leftJoin('historial_herramientas', 'herramientas.id', '=', 'historial_herramientas.herramienta_id')
            ->groupBy(
                'herramientas.id',
                'herramientas.nombre',
                'herramientas.numero_serie',
                'herramientas.estado',
                'herramientas.foto'
            )
            ->orderByDesc('usos')
            ->limit(10)
            ->get();

        return Inertia::render('Herramientas/Dashboard', [
            'estadisticas' => $estadisticas,
            'mantenimiento_urgente' => $mantenimiento_urgente,
            'vida_util_proxima' => $vida_util_proxima,
            'por_categoria' => $por_categoria,
            'mas_utilizadas' => $mas_utilizadas,
        ]);
    }

    public function mantenimiento()
    {
        $herramientas = Herramienta::requierenMantenimiento()
            ->with(['categoriaHerramienta', 'tecnico'])
            ->orderByRaw('(CURRENT_DATE - fecha_ultimo_mantenimiento) DESC')
            ->paginate(15);

        return Inertia::render('Herramientas/Mantenimiento', [
            'herramientas' => $herramientas,
        ]);
    }

    public function registrarMantenimiento(Request $request, Herramienta $herramienta)
    {
        $data = $request->validate([
            'fecha_mantenimiento' => 'required|date',
            'costo_mantenimiento' => 'nullable|numeric|min:0',
            'descripcion_mantenimiento' => 'required|string',
            'proximo_mantenimiento_dias' => 'nullable|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            // Actualizar fecha de último mantenimiento
            $herramienta->update([
                'fecha_ultimo_mantenimiento' => $data['fecha_mantenimiento'],
                'dias_para_mantenimiento' => $data['proximo_mantenimiento_dias'] ?? $herramienta->dias_para_mantenimiento,
            ]);

            // TODO: Crear registro en tabla de mantenimientos separada
            // Por ahora solo actualizamos las fechas en la herramienta
            // HistorialHerramienta NO tiene las columnas necesarias para mantenimientos

            \Log::info('Mantenimiento registrado', [
                'herramienta_id' => $herramienta->id,
                'fecha' => $data['fecha_mantenimiento'],
                'costo' => $data['costo_mantenimiento'] ?? 0,
                'descripcion' => $data['descripcion_mantenimiento'],
                'usuario_id' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Mantenimiento registrado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error al registrar mantenimiento: ' . $e->getMessage(), [
                'herramienta_id' => $herramienta->id,
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al registrar el mantenimiento: ' . $e->getMessage());
        }
    }

    public function estadisticas(Herramienta $herramienta)
    {
        $herramienta->load(['categoriaHerramienta', 'tecnico', 'historial']);

        return Inertia::render('Herramientas/Estadisticas', [
            'herramienta' => [
                'id' => $herramienta->id,
                'nombre' => $herramienta->nombre,
                'numero_serie' => $herramienta->numero_serie,
                'estado' => $herramienta->estado,
                'descripcion' => $herramienta->descripcion,
                'foto' => $herramienta->foto,
                'categoria_herramienta' => $herramienta->categoriaHerramienta,
                'tecnico' => $herramienta->tecnico,
                'fecha_ultimo_mantenimiento' => $herramienta->fecha_ultimo_mantenimiento,
                'dias_para_mantenimiento' => $herramienta->dias_para_mantenimiento,
                'vida_util_meses' => $herramienta->vida_util_meses,
                'costo_reemplazo' => $herramienta->costo_reemplazo,
                'requiere_mantenimiento' => $herramienta->requiere_mantenimiento,
                'dias_desde_ultimo_mantenimiento' => $herramienta->dias_desde_ultimo_mantenimiento,
                'dias_para_proximo_mantenimiento' => $herramienta->dias_para_proximo_mantenimiento,
                'porcentaje_vida_util' => $herramienta->porcentaje_vida_util,
                'vida_util_proxima_a_vencer' => $herramienta->vidaUtilProximaAVencer(),
                'necesita_mantenimiento' => $herramienta->necesitaMantenimiento(),
                'created_at' => $herramienta->created_at,
            ],
            'estadisticas' => $herramienta->estadisticas,
            'historial_completo' => $herramienta->historial_completo,
        ]);
    }

    public function cambiarEstado(Request $request, Herramienta $herramienta)
    {
        $data = $request->validate([
            'estado' => 'required|in:disponible,asignada,mantenimiento,baja,perdida',
            'observaciones' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $estado_anterior = $herramienta->estado;
            $nuevoEstado = $data['estado'];

            // Validar transiciones de estado permitidas
            $this->validarTransicionEstado($herramienta, $nuevoEstado);

            // Actualizar estado (el observer se encargará de sincronizar tecnico_id)
            $herramienta->update(['estado' => $nuevoEstado]);

            // El Observer automáticamente crea el historial si cambió tecnico_id
            // NO crear historial manualmente aquí para evitar duplicación

            DB::commit();

            return redirect()->back()->with('success', 'Estado cambiado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error al cambiar estado: ' . $e->getMessage(), [
                'herramienta_id' => $herramienta->id,
                'estado_anterior' => $estado_anterior ?? null,
                'estado_nuevo' => $nuevoEstado ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Error al cambiar el estado: ' . $e->getMessage());
        }
    }

    /**
     * Validar si la transición de estado es permitida
     */
    protected function validarTransicionEstado(Herramienta $herramienta, string $nuevoEstado)
    {
        $estadoActual = $herramienta->estado;

        // Si cambia a asignada, debe tener técnico (el observer validará esto también)
        if ($nuevoEstado === Herramienta::ESTADO_ASIGNADA && empty($herramienta->tecnico_id)) {
            throw new \Exception('No se puede cambiar a estado "asignada" sin un técnico asignado');
        }

        // No permitir cambiar de perdida a disponible directamente
        if ($estadoActual === Herramienta::ESTADO_PERDIDA && $nuevoEstado === Herramienta::ESTADO_DISPONIBLE) {
            throw new \Exception('Una herramienta perdida no puede volver a estar disponible directamente. Debe pasar por mantenimiento o validación.');
        }

        // No permitir cambiar de baja a asignada directamente
        if ($estadoActual === Herramienta::ESTADO_BAJA && $nuevoEstado === Herramienta::ESTADO_ASIGNADA) {
            throw new \Exception('Una herramienta dada de baja no puede ser asignada');
        }

        return true;
    }

    public function alertas()
    {
        // Herramientas que requieren mantenimiento urgente
        $mantenimiento_urgente = Herramienta::requierenMantenimiento()
            ->whereRaw('(CURRENT_DATE - fecha_ultimo_mantenimiento) >= dias_para_mantenimiento')
            ->with(['categoriaHerramienta', 'tecnico'])
            ->get();

        // Herramientas próximas a mantenimiento
        $mantenimiento_proximo = Herramienta::requierenMantenimiento()
            ->whereRaw('(CURRENT_DATE - fecha_ultimo_mantenimiento) >= (dias_para_mantenimiento * 0.8)')
            ->whereRaw('(CURRENT_DATE - fecha_ultimo_mantenimiento) < dias_para_mantenimiento')
            ->with(['categoriaHerramienta', 'tecnico'])
            ->get();

        // Herramientas con vida útil vencida
        $vida_util_vencida = Herramienta::whereNotNull('vida_util_meses')
            ->whereNotNull('fecha_ultimo_mantenimiento')
            ->whereRaw('(CURRENT_DATE - fecha_ultimo_mantenimiento) >= (vida_util_meses * 30)')
            ->with(['categoriaHerramienta', 'tecnico'])
            ->get();

        // Herramientas próximas a vencer vida útil
        $vida_util_proxima = Herramienta::whereNotNull('vida_util_meses')
            ->whereNotNull('fecha_ultimo_mantenimiento')
            ->whereRaw('(CURRENT_DATE - fecha_ultimo_mantenimiento) >= (vida_util_meses * 30 * 0.8)')
            ->whereRaw('(CURRENT_DATE - fecha_ultimo_mantenimiento) < (vida_util_meses * 30)')
            ->with(['categoriaHerramienta', 'tecnico'])
            ->get();

        // Herramientas perdidas
        $herramientas_perdidas = Herramienta::where('estado', Herramienta::ESTADO_PERDIDA)
            ->with(['categoriaHerramienta', 'tecnico'])
            ->get();

        // Herramientas sin categoría
        $sin_categoria = Herramienta::where(function ($query) {
            $query->whereNull('categoria_id')->whereNull('categoria');
        })->with(['categoriaHerramienta', 'tecnico'])->get();

        // Combinar todas las herramientas para mostrar en una sola lista
        $todas_alertas = collect()
            ->merge($mantenimiento_urgente->map(fn($h) => array_merge($h->toArray(), ['tipo_alerta' => 'mantenimiento_urgente'])))
            ->merge($mantenimiento_proximo->map(fn($h) => array_merge($h->toArray(), ['tipo_alerta' => 'mantenimiento_proximo'])))
            ->merge($vida_util_vencida->map(fn($h) => array_merge($h->toArray(), ['tipo_alerta' => 'vida_util_vencida'])))
            ->merge($vida_util_proxima->map(fn($h) => array_merge($h->toArray(), ['tipo_alerta' => 'vida_util_proxima'])))
            ->merge($herramientas_perdidas->map(fn($h) => array_merge($h->toArray(), ['tipo_alerta' => 'herramientas_perdidas'])))
            ->merge($sin_categoria->map(fn($h) => array_merge($h->toArray(), ['tipo_alerta' => 'herramientas_sin_categoria'])));

        return Inertia::render('Herramientas/Alertas', [
            'herramientas' => $todas_alertas,
        ]);
    }

    public function reportes(Request $request)
    {
        $tipo = $request->query('tipo', 'general');
        $fecha_inicio = $request->query('fecha_inicio');
        $fecha_fin = $request->query('fecha_fin');
        $categoria = $request->query('categoria');
        $estado = $request->query('estado');

        $query = Herramienta::query()->with(['categoriaHerramienta', 'tecnico']);

        // Aplicar filtros
        if ($categoria) {
            $query->where('categoria_id', $categoria);
        }

        if ($estado) {
            $query->where('estado', $estado);
        }

        if ($fecha_inicio) {
            $query->where('created_at', '>=', $fecha_inicio);
        }

        if ($fecha_fin) {
            $query->where('created_at', '<=', $fecha_fin);
        }

        $herramientas = $query->get();

        // Estadísticas generales
        $estadisticas = [
            'total_herramientas' => Herramienta::count(),
            'herramientas_disponibles' => Herramienta::disponibles()->count(),
            'herramientas_asignadas' => Herramienta::asignadas()->count(),
            'herramientas_mantenimiento' => Herramienta::enMantenimiento()->count(),
            'herramientas_requieren_mantenimiento' => Herramienta::requierenMantenimiento()
                ->whereRaw('(CURRENT_DATE - fecha_ultimo_mantenimiento) >= dias_para_mantenimiento')->count(),
            'total_asignaciones' => 0, // Esto vendría de historial si existe
            'promedio_dias_uso' => 0, // Esto vendría de historial si existe
        ];

        return Inertia::render('Herramientas/Reportes', [
            'herramientas' => $herramientas,
            'estadisticas' => $estadisticas,
        ]);
    }
}
