<?php

namespace App\Http\Controllers;

use App\Models\CrmProspecto;
use App\Models\CrmActividad;
use App\Models\CrmTarea;
use App\Models\CrmScript;
use App\Models\CrmMeta;
use App\Models\CrmCampania;
use App\Models\User;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Http\Requests\Crm\StoreProspectoRequest;

class CrmController extends Controller
{

    /**
     * Dashboard principal del CRM con pipeline
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['admin', 'super-admin']);
        $vendedorId = $request->get('vendedor_id');

        // Base query - Include email for quick actions and cotizaciones count for integration
        $query = CrmProspecto::with([
            'vendedor:id,name',
            'cliente:id,nombre_razon_social',
            'cliente.cotizaciones' => fn($q) => $q->select('id', 'cliente_id', 'estado', 'total')->latest()->limit(3)
        ])
            ->select(['id', 'nombre', 'telefono', 'email', 'empresa', 'etapa', 'prioridad', 'valor_estimado', 'vendedor_id', 'cliente_id', 'proxima_actividad_at', 'ultima_actividad_at', 'created_at']);

        // Filtrar por vendedor si no es admin, o si se especifica
        if (!$isAdmin) {
            $query->where('vendedor_id', $user->id);
        } elseif ($vendedorId) {
            $query->where('vendedor_id', $vendedorId);
        }

        $prospectos = $query->activos()->get();

        // Agrupar por etapa para el pipeline
        $pipeline = [];
        foreach (CrmProspecto::ETAPAS as $etapa => $label) {
            if ($etapa === 'cerrado_ganado' || $etapa === 'cerrado_perdido')
                continue;
            $pipeline[$etapa] = [
                'label' => $label,
                'prospectos' => $prospectos->where('etapa', $etapa)->values(),
                'total_valor' => $prospectos->where('etapa', $etapa)->sum('valor_estimado'),
            ];
        }

        // Estadísticas
        $stats = [
            'total_prospectos' => $prospectos->count(),
            'valor_pipeline' => $prospectos->sum('valor_estimado'),
            'con_actividad_pendiente' => $prospectos->filter(fn($p) => $p->proxima_actividad_at && $p->proxima_actividad_at <= now())->count(),
            'cerrados_mes' => CrmProspecto::where('etapa', 'cerrado_ganado')
                ->whereMonth('cerrado_at', now()->month)
                ->when(!$isAdmin, fn($q) => $q->where('vendedor_id', $user->id))
                ->count(),
        ];

        // Tareas pendientes del usuario
        $tareasPendientes = CrmTarea::with('prospecto:id,nombre')
            ->where('user_id', $user->id)
            ->pendientes()
            ->orderBy('fecha_limite')
            ->limit(5)
            ->get();

        // Lista de vendedores para filtro (solo admin)
        $vendedores = $isAdmin ? User::whereHas('roles', fn($q) => $q->whereIn('name', ['ventas', 'admin']))
            ->where('activo', true)
            ->select('id', 'name')
            ->get() : [];

        // Cargar catálogos para el formulario de nuevo prospecto
        $catalogs = $this->getCatalogs();

        // Progreso del usuario actual (metas)
        $miProgreso = CrmMeta::getProgresoUsuario($user->id);

        // Leaderboard (solo para admin o si hay metas)
        $leaderboard = $isAdmin ? CrmMeta::getLeaderboard(10) : [];

        return Inertia::render('Crm/Index', [
            'pipeline' => $pipeline,
            'stats' => $stats,
            'tareasPendientes' => $tareasPendientes,
            'vendedores' => $vendedores,
            'filtros' => [
                'vendedor_id' => $vendedorId,
            ],
            'etapas' => CrmProspecto::ETAPAS,
            'isAdmin' => $isAdmin,
            'catalogs' => $catalogs,
            'miProgreso' => $miProgreso,
            'leaderboard' => $leaderboard,
        ]);
    }

    /**
     * Obtener catálogos para el formulario de prospecto
     */
    private function getCatalogs(): array
    {
        return [
            'regimenes' => \App\Models\SatRegimenFiscal::orderBy('clave')
                ->get()
                ->map(fn($r) => [
                    'value' => $r->clave,
                    'text' => "{$r->clave} - {$r->descripcion}",
                    'personas' => $r->personas ?? 'FM',
                ]),
            'usosCFDI' => \App\Models\SatUsoCfdi::orderBy('clave')
                ->get()
                ->map(fn($u) => [
                    'value' => $u->clave,
                    'text' => "{$u->clave} - {$u->descripcion}",
                ]),
            'priceLists' => \App\Models\PriceList::where('activa', true)
                ->orderBy('nombre')
                ->get()
                ->map(fn($p) => [
                    'value' => $p->id,
                    'text' => $p->nombre,
                ]),
            'estados' => \App\Models\SatEstado::orderBy('nombre')
                ->get()
                ->map(fn($e) => [
                    'value' => $e->clave,
                    'text' => $e->nombre,
                ]),
        ];
    }

    /**
     * Lista de prospectos con filtros
     */
    public function prospectos(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['admin', 'super-admin']);

        $query = CrmProspecto::with(['vendedor:id,name', 'cliente:id,nombre_razon_social'])
            ->when(!$isAdmin, fn($q) => $q->where('vendedor_id', $user->id))
            ->when($request->etapa, fn($q, $e) => $q->where('etapa', $e))
            ->when($request->search, fn($q, $s) => $q->where(function ($qq) use ($s) {
                $qq->where('nombre', 'ilike', "%{$s}%")
                    ->orWhere('telefono', 'ilike', "%{$s}%")
                    ->orWhere('empresa', 'ilike', "%{$s}%");
            }))
            ->orderByDesc('updated_at');

        $prospectos = $query->paginate(20)->appends($request->query());

        return Inertia::render('Crm/Prospectos/Index', [
            'prospectos' => $prospectos,
            'etapas' => CrmProspecto::ETAPAS,
            'filtros' => $request->only(['etapa', 'search']),
        ]);
    }

    /**
     * Crear nuevo prospecto
     */
    public function crearProspecto(StoreProspectoRequest $request)
    {
        $validated = $request->validated();

        $validated['vendedor_id'] = $validated['vendedor_id'] ?? Auth::id();
        $validated['created_by'] = Auth::id();
        $validated['etapa'] = 'prospecto';

        $prospecto = CrmProspecto::create($validated);

        return redirect()->route('crm.prospecto.show', $prospecto)
            ->with('success', 'Prospecto creado exitosamente');
    }

    /**
     * Detalle de prospecto con actividades y scripts
     */
    public function showProspecto(CrmProspecto $prospecto)
    {
        $prospecto->load(['vendedor:id,name', 'cliente', 'actividades.usuario:id,name', 'tareas' => fn($q) => $q->pendientes()]);

        // Scripts relevantes para la etapa actual
        $scripts = CrmScript::activos()
            ->porEtapa($prospecto->etapa)
            ->get();

        return Inertia::render('Crm/Prospectos/Show', [
            'prospecto' => $prospecto,
            'scripts' => $scripts,
            'etapas' => CrmProspecto::ETAPAS,
            'tiposActividad' => CrmActividad::TIPOS,
            'resultadosActividad' => CrmActividad::RESULTADOS,
        ]);
    }

    /**
     * Actualizar prospecto
     */
    public function actualizarProspecto(Request $request, CrmProspecto $prospecto)
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'empresa' => 'nullable|string|max:255',
            'etapa' => 'nullable|in:' . implode(',', array_keys(CrmProspecto::ETAPAS)),
            'vendedor_id' => 'nullable|exists:users,id',
            'prioridad' => 'nullable|in:alta,media,baja',
            'valor_estimado' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:2000',
            'motivo_perdida' => 'nullable|string|max:500',
            'price_list_id' => 'nullable|exists:price_lists,id',
            // Campos fiscales
            'requiere_factura' => 'boolean',
            'tipo_persona' => 'nullable|in:fisica,moral',
            'rfc' => ['nullable', 'string', 'max:13'],
            'curp' => ['nullable', 'string', 'max:18'],
            'regimen_fiscal' => 'nullable|string|max:10',
            'uso_cfdi' => 'nullable|string|max:10',
            'domicilio_fiscal_cp' => ['nullable', 'string', 'size:5'],
            // Dirección
            'calle' => 'nullable|string|max:255',
            'numero_exterior' => 'nullable|string|max:50',
            'numero_interior' => 'nullable|string|max:50',
            'codigo_postal' => ['nullable', 'string', 'size:5'],
            'colonia' => 'nullable|string|max:255',
            'municipio' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:3',
            'pais' => 'nullable|string|max:3',
        ]);

        // Si cambia a cerrado, actualizar fecha
        if (isset($validated['etapa'])) {
            if ($validated['etapa'] === 'cerrado_ganado' || $validated['etapa'] === 'cerrado_perdido') {
                $validated['cerrado_at'] = now();
            }
        }

        $prospecto->update($validated);

        return back()->with('success', 'Prospecto actualizado');
    }

    /**
     * Mover prospecto a otra etapa (para drag & drop)
     */
    public function moverEtapa(Request $request, CrmProspecto $prospecto)
    {
        $validated = $request->validate([
            'etapa' => 'required|in:' . implode(',', array_keys(CrmProspecto::ETAPAS)),
        ]);

        // Si se cierra como ganado, convertir automáticamente a cliente
        if ($validated['etapa'] === 'cerrado_ganado') {
            $cliente = $prospecto->cerrarGanadoYConvertir();
            return back()->with('success', $cliente ? "¡Felicidades! Cliente '{$cliente->nombre_razon_social}' creado." : 'Prospecto cerrado');
        }

        $prospecto->update([
            'etapa' => $validated['etapa'],
            'cerrado_at' => $validated['etapa'] === 'cerrado_perdido' ? now() : null,
        ]);

        return back()->with('success', 'Etapa actualizada');
    }

    /**
     * Convertir prospecto a cliente manualmente
     */
    public function convertirACliente(CrmProspecto $prospecto)
    {
        $cliente = $prospecto->convertirACliente();

        if ($cliente) {
            return back()->with('success', "Cliente '{$cliente->nombre_razon_social}' creado exitosamente.");
        }

        return back()->with('error', 'No se pudo crear el cliente.');
    }

    /**
     * Registrar actividad en prospecto
     */
    public function registrarActividad(Request $request, CrmProspecto $prospecto)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:' . implode(',', array_keys(CrmActividad::TIPOS)),
            'resultado' => 'nullable|in:' . implode(',', array_keys(CrmActividad::RESULTADOS)),
            'notas' => 'nullable|string|max:2000',
            'duracion_minutos' => 'nullable|integer|min:0',
            'proxima_actividad_at' => 'nullable|date',
            'proxima_actividad_tipo' => 'nullable|string',
        ]);

        $validated['prospecto_id'] = $prospecto->id;
        $validated['user_id'] = Auth::id();

        $actividad = CrmActividad::create($validated);

        // Actualizar prospecto
        $prospecto->update([
            'ultima_actividad_at' => now(),
            'proxima_actividad_at' => $validated['proxima_actividad_at'] ?? null,
        ]);

        // Auto-avanzar etapa si resultado es positivo
        if (in_array($validated['resultado'], ['interesado', 'cita_agendada'])) {
            if ($prospecto->etapa === 'prospecto') {
                $prospecto->update(['etapa' => 'contactado']);
            } elseif ($prospecto->etapa === 'contactado') {
                $prospecto->update(['etapa' => 'interesado']);
            }
        }

        return back()->with('success', 'Actividad registrada');
    }

    /**
     * Tareas del vendedor
     */
    public function tareas(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['admin', 'super-admin']);
        $filtro = $request->get('filtro', 'pendientes');

        $query = CrmTarea::with(['prospecto:id,nombre', 'usuario:id,name'])
            ->when(!$isAdmin, fn($q) => $q->where('user_id', $user->id))
            ->when($isAdmin && $request->user_id, fn($q, $id) => $q->where('user_id', $id));

        switch ($filtro) {
            case 'hoy':
                $query->paraHoy();
                break;
            case 'vencidas':
                $query->vencidas();
                break;
            case 'completadas':
                $query->completadas();
                break;
            default:
                $query->pendientes();
        }

        $tareas = $query->orderBy('fecha_limite')->paginate(20);

        // Stats
        $stats = [
            'pendientes' => CrmTarea::when(!$isAdmin, fn($q) => $q->where('user_id', $user->id))->pendientes()->count(),
            'hoy' => CrmTarea::when(!$isAdmin, fn($q) => $q->where('user_id', $user->id))->paraHoy()->count(),
            'vencidas' => CrmTarea::when(!$isAdmin, fn($q) => $q->where('user_id', $user->id))->vencidas()->count(),
            'completadas_semana' => CrmTarea::when(!$isAdmin, fn($q) => $q->where('user_id', $user->id))
                ->completadas()
                ->where('completada_at', '>=', now()->startOfWeek())
                ->count(),
        ];

        $vendedores = $isAdmin ? User::whereHas('roles', fn($q) => $q->whereIn('name', ['ventas', 'admin']))
            ->where('activo', true)
            ->select('id', 'name')
            ->get() : [];

        return Inertia::render('Crm/Tareas/Index', [
            'tareas' => $tareas,
            'stats' => $stats,
            'filtros' => ['filtro' => $filtro, 'user_id' => $request->user_id],
            'tiposTarea' => CrmTarea::TIPOS,
            'vendedores' => $vendedores,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Crear tarea
     */
    public function crearTarea(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'prospecto_id' => 'nullable|exists:crm_prospectos,id',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'tipo' => 'required|in:' . implode(',', array_keys(CrmTarea::TIPOS)),
            'prioridad' => 'nullable|in:alta,media,baja',
            'fecha_limite' => 'required|date',
        ]);

        $validated['created_by'] = Auth::id();

        CrmTarea::create($validated);

        return back()->with('success', 'Tarea creada exitosamente');
    }

    /**
     * Completar tarea
     */
    public function completarTarea(Request $request, CrmTarea $tarea)
    {
        $validated = $request->validate([
            'notas_resultado' => 'nullable|string|max:1000',
        ]);

        $tarea->completar($validated['notas_resultado'] ?? null);

        return back()->with('success', 'Tarea completada');
    }

    /**
     * Scripts de venta
     */
    public function scripts()
    {
        $scripts = CrmScript::with('creador:id,name')
            ->orderBy('tipo')
            ->orderBy('orden')
            ->get();

        return Inertia::render('Crm/Scripts/Index', [
            'scripts' => $scripts,
            'tipos' => CrmScript::TIPOS,
            'etapas' => CrmScript::ETAPAS,
        ]);
    }

    /**
     * Guardar script
     */
    public function guardarScript(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:crm_scripts,id',
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:' . implode(',', array_keys(CrmScript::TIPOS)),
            'etapa' => 'required|in:' . implode(',', array_keys(CrmScript::ETAPAS)),
            'contenido' => 'required|string|max:5000',
            'tips' => 'nullable|string|max:2000',
            'activo' => 'boolean',
            'orden' => 'nullable|integer',
        ]);

        if ($validated['id'] ?? null) {
            $script = CrmScript::findOrFail($validated['id']);
            $script->update($validated);
        } else {
            $validated['created_by'] = Auth::id();
            CrmScript::create($validated);
        }

        return back()->with('success', 'Script guardado exitosamente');
    }

    /**
     * Eliminar script
     */
    public function eliminarScript(CrmScript $script)
    {
        $script->delete();
        return back()->with('success', 'Script eliminado');
    }

    /**
     * Importar clientes existentes como prospectos
     */
    public function importarClientes(Request $request)
    {
        $validated = $request->validate([
            'cliente_ids' => 'required|array',
            'cliente_ids.*' => 'exists:clientes,id',
            'vendedor_id' => 'required|exists:users,id',
        ]);

        $creados = 0;
        foreach ($validated['cliente_ids'] as $clienteId) {
            $cliente = Cliente::find($clienteId);

            // Verificar si ya existe como prospecto
            $existe = CrmProspecto::where('cliente_id', $clienteId)->exists();
            if (!$existe) {
                CrmProspecto::create([
                    'cliente_id' => $clienteId,
                    'nombre' => $cliente->nombre_razon_social,
                    'telefono' => $cliente->telefono,
                    'email' => $cliente->email,
                    'origen' => 'otro',
                    'etapa' => 'prospecto',
                    'vendedor_id' => $validated['vendedor_id'],
                    'created_by' => Auth::id(),
                ]);
                $creados++;
            }
        }

        return back()->with('success', "{$creados} clientes importados como prospectos");
    }

    /**
     * Vista de metas (admin)
     */
    public function metas()
    {
        // Protegido por middleware role:admin en la ruta

        $vendedores = User::whereHas('roles', fn($q) => $q->whereIn('name', ['ventas', 'admin']))
            ->where('activo', true)
            ->select('id', 'name')
            ->get();

        $metas = CrmMeta::with(['user:id,name', 'creador:id,name'])
            ->activas()
            ->orderBy('user_id')
            ->get()
            ->map(function (CrmMeta $meta) {
                $progreso = $meta->getProgresoHoy();
                return [
                    'id' => $meta->id,
                    'user_id' => $meta->user_id,
                    'user' => $meta->user,
                    'tipo' => $meta->tipo,
                    'tipo_label' => CrmMeta::TIPOS[$meta->tipo] ?? $meta->tipo,
                    'meta_diaria' => $meta->meta_diaria,
                    'fecha_inicio' => optional($meta->fecha_inicio)->format('Y-m-d'),
                    'fecha_fin' => optional($meta->fecha_fin)->format('Y-m-d'),
                    'progreso' => $progreso,
                    'creador' => $meta->creador,
                ];
            });

        $leaderboard = CrmMeta::getLeaderboard(20);

        return Inertia::render('Crm/Metas/Index', [
            'vendedores' => $vendedores,
            'metas' => $metas,
            'tiposMeta' => CrmMeta::TIPOS,
            'leaderboard' => $leaderboard,
        ]);
    }

    /**
     * Guardar o actualizar meta
     */
    public function guardarMeta(Request $request)
    {
        // Protegido por middleware role:admin en la ruta
        $validated = $request->validate([
            'id' => 'nullable|exists:crm_metas,id',
            'user_id' => 'required|exists:users,id',
            'tipo' => 'required|in:actividades,prospectos',
            'meta_diaria' => 'required|integer|min:1|max:100',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        if ($validated['id'] ?? null) {
            $meta = CrmMeta::findOrFail($validated['id']);
            $meta->update($validated);
            $mensaje = 'Meta actualizada correctamente';
        } else {
            // Verificar si ya existe una meta activa de este tipo para el usuario
            $existente = CrmMeta::where('user_id', $validated['user_id'])
                ->where('tipo', $validated['tipo'])
                ->activas()
                ->first();

            if ($existente) {
                // Actualizar la existente
                $existente->update($validated);
                $mensaje = 'Meta actualizada correctamente';
            } else {
                // Crear nueva
                $validated['created_by'] = Auth::id();
                CrmMeta::create($validated);
                $mensaje = 'Meta creada correctamente';
            }
        }

        return back()->with('success', $mensaje);
    }

    /**
     * Eliminar (desactivar) meta
     */
    public function eliminarMeta(CrmMeta $meta)
    {
        // Protegido por middleware role:admin en la ruta
        $meta->update(['activa' => false]);

        return back()->with('success', 'Meta eliminada correctamente');
    }

    /**
     * Exportar datos de vendedores para que una IA sugiera metas
     */
    public function exportarVendedoresCSV()
    {
        // Obtener vendedores activos con su rendimiento
        $vendedores = User::whereHas('roles', fn($q) => $q->whereIn('name', ['ventas', 'admin']))
            ->where('activo', true)
            ->get()
            ->map(function ($user) {
                // Calcular rendimiento del último mes
                $actividadesMes = CrmActividad::where('user_id', $user->id)
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->count();

                $prospectosMes = CrmProspecto::where('vendedor_id', $user->id)
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->count();

                $cerradosMes = CrmProspecto::where('vendedor_id', $user->id)
                    ->where('etapa', 'cerrado_ganado')
                    ->where('cerrado_at', '>=', now()->startOfMonth())
                    ->count();

                // Verificar si tiene meta actual
                $metaActual = CrmMeta::where('user_id', $user->id)
                    ->activas()
                    ->first();

                return [
                    'user_id' => $user->id,
                    'nombre' => $user->name,
                    'actividades_mes' => $actividadesMes,
                    'prospectos_mes' => $prospectosMes,
                    'cerrados_mes' => $cerradosMes,
                    'meta_actual_tipo' => $metaActual?->tipo ?? 'sin_meta',
                    'meta_actual_diaria' => $metaActual?->meta_diaria ?? 0,
                ];
            });

        // Generar CSV
        $csv = "user_id,nombre,actividades_mes,prospectos_mes,cerrados_mes,meta_actual_tipo,meta_actual_diaria\n";
        foreach ($vendedores as $v) {
            $csv .= "{$v['user_id']},\"{$v['nombre']}\",{$v['actividades_mes']},{$v['prospectos_mes']},{$v['cerrados_mes']},{$v['meta_actual_tipo']},{$v['meta_actual_diaria']}\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="vendedores_para_metas_' . now()->format('Y-m-d') . '.csv"');
    }

    /**
     * Importar metas desde CSV
     */
    public function importarMetasCSV(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $archivo = $request->file('archivo');
        $contenido = file_get_contents($archivo->getRealPath());
        $lineas = explode("\n", trim($contenido));

        // Saltar header
        array_shift($lineas);

        $importadas = 0;
        $errores = [];

        foreach ($lineas as $i => $linea) {
            if (empty(trim($linea)))
                continue;

            $campos = str_getcsv($linea);
            if (count($campos) < 4) {
                $errores[] = "Línea " . ($i + 2) . ": formato inválido";
                continue;
            }

            // Formato esperado: user_id,tipo,meta_diaria,fecha_inicio,fecha_fin
            $userId = intval($campos[0]);
            $tipo = trim($campos[1]);
            $metaDiaria = intval($campos[2]);
            $fechaInicio = isset($campos[3]) ? trim($campos[3]) : now()->toDateString();
            $fechaFin = isset($campos[4]) ? trim($campos[4]) : now()->addYear()->toDateString();

            // Validar user existe
            if (!User::find($userId)) {
                $errores[] = "Línea " . ($i + 2) . ": usuario $userId no existe";
                continue;
            }

            // Validar tipo
            if (!in_array($tipo, ['actividades', 'prospectos'])) {
                $errores[] = "Línea " . ($i + 2) . ": tipo '$tipo' inválido (use 'actividades' o 'prospectos')";
                continue;
            }

            // Desactivar meta anterior del mismo tipo
            CrmMeta::where('user_id', $userId)
                ->where('tipo', $tipo)
                ->where('activa', true)
                ->update(['activa' => false]);

            // Crear nueva meta
            CrmMeta::create([
                'user_id' => $userId,
                'tipo' => $tipo,
                'meta_diaria' => $metaDiaria,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'activa' => true,
                'created_by' => Auth::id(),
            ]);

            $importadas++;
        }

        $mensaje = "Se importaron $importadas metas correctamente.";
        if (count($errores) > 0) {
            $mensaje .= " Errores: " . implode(', ', array_slice($errores, 0, 5));
        }

        return back()->with('success', $mensaje);
    }

    // ==========================================
    // CAMPAÑAS CON SCRIPTS
    // ==========================================

    /**
     * Vista de campañas (admin)
     */
    public function campanias()
    {
        $campanias = CrmCampania::with(['producto:id,nombre,codigo,precio_venta', 'creador:id,name'])
            ->withCount('scripts')
            ->orderBy('activa', 'desc')
            ->orderBy('fecha_fin', 'desc')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                    'producto' => $c->producto?->nombre,
                    'objetivo' => $c->objetivo,
                    'fecha_inicio' => optional($c->fecha_inicio)->format('d/m/Y'),
                    'fecha_fin' => optional($c->fecha_fin)->format('d/m/Y'),
                    'dias_restantes' => $c->diasRestantes(),
                    'meta_actividades_dia' => $c->meta_actividades_dia,
                    'scripts_count' => $c->scripts_count,
                    'activa' => $c->activa,
                    'vigente' => $c->estaVigente(),
                    'creador' => $c->creador?->name,
                ];
            });

        $productos = \App\Models\Producto::where('estado', 'activo')
            ->select('id', 'nombre', 'codigo')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Crm/Campanias/Index', [
            'campanias' => $campanias,
            'productos' => $productos,
        ]);
    }

    /**
     * Guardar campaña
     */
    public function guardarCampania(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:crm_campanias,id',
            'nombre' => 'required|string|max:255',
            'producto_id' => 'nullable|exists:productos,id',
            'descripcion' => 'nullable|string',
            'objetivo' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'meta_actividades_dia' => 'required|integer|min:1|max:50',
        ]);

        if ($validated['id'] ?? null) {
            $campania = CrmCampania::findOrFail($validated['id']);
            $campania->update($validated);
            $mensaje = 'Campaña actualizada';
        } else {
            $validated['created_by'] = Auth::id();
            $validated['activa'] = true;
            CrmCampania::create($validated);
            $mensaje = 'Campaña creada';
        }

        return back()->with('success', $mensaje);
    }

    /**
     * Ver detalle de campaña con scripts
     */
    public function verCampania(CrmCampania $campania)
    {
        $campania->load(['producto', 'scripts', 'metas.user']);

        return Inertia::render('Crm/Campanias/Show', [
            'campania' => [
                'id' => $campania->id,
                'nombre' => $campania->nombre,
                'descripcion' => $campania->descripcion,
                'objetivo' => $campania->objetivo,
                'fecha_inicio' => optional($campania->fecha_inicio)->format('Y-m-d'),
                'fecha_fin' => optional($campania->fecha_fin)->format('Y-m-d'),
                'meta_actividades_dia' => $campania->meta_actividades_dia,
                'activa' => $campania->activa,
                'producto' => $campania->producto ? [
                    'id' => $campania->producto->id,
                    'nombre' => $campania->producto->nombre,
                    'precio' => $campania->producto->precio_venta,
                ] : null,
            ],
            'scripts' => $campania->scripts->groupBy('tipo'),
            'metas' => $campania->metas->map(fn($m) => [
                'id' => $m->id,
                'user' => $m->user?->name,
                'meta_diaria' => $m->meta_diaria,
            ]),
        ]);
    }

    /**
     * Exportar campaña como JSON para IA
     */
    public function exportarCampaniaJSON(CrmCampania $campania)
    {
        $campania->load('producto');
        $data = $campania->toArrayParaIA();

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response($json)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="campania_' . $campania->id . '_para_ia.json"');
    }

    /**
     * Importar scripts desde CSV para una campaña
     */
    public function importarScriptsCampania(Request $request, CrmCampania $campania)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $archivo = $request->file('archivo');
        $contenido = file_get_contents($archivo->getRealPath());
        $lineas = explode("\n", trim($contenido));

        // Saltar header
        array_shift($lineas);

        $importados = 0;
        $errores = [];
        $orden = 1;

        // Eliminar scripts anteriores de esta campaña
        CrmScript::where('campania_id', $campania->id)->delete();

        foreach ($lineas as $i => $linea) {
            if (empty(trim($linea)))
                continue;

            $campos = str_getcsv($linea);
            if (count($campos) < 3) {
                $errores[] = "Línea " . ($i + 2) . ": formato inválido";
                continue;
            }

            // Formato: tipo,nombre,contenido,tips
            $tipo = strtolower(trim($campos[0]));
            $nombre = trim($campos[1]);
            $contenidoScript = trim($campos[2]);
            $tips = isset($campos[3]) ? trim($campos[3]) : null;

            // Validar tipo
            if (!in_array($tipo, ['apertura', 'presentacion', 'objecion', 'cierre', 'seguimiento'])) {
                $errores[] = "Línea " . ($i + 2) . ": tipo '$tipo' inválido";
                continue;
            }

            CrmScript::create([
                'nombre' => $nombre,
                'tipo' => $tipo,
                'etapa' => 'general',
                'contenido' => $contenidoScript,
                'tips' => $tips,
                'activo' => true,
                'orden' => $orden++,
                'created_by' => Auth::id(),
                'campania_id' => $campania->id,
            ]);

            $importados++;
        }

        $mensaje = "Se importaron $importados scripts correctamente.";
        if (count($errores) > 0) {
            $mensaje .= " Errores: " . implode(', ', array_slice($errores, 0, 3));
        }

        return back()->with('success', $mensaje);
    }

    /**
     * Activar/Desactivar campaña
     */
    public function toggleCampania(CrmCampania $campania)
    {
        $campania->update(['activa' => !$campania->activa]);
        return back()->with('success', $campania->activa ? 'Campaña activada' : 'Campaña desactivada');
    }
}
