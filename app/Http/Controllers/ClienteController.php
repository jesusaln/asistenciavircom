<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Cliente;
use App\Models\SatEstado;
use App\Models\SatRegimenFiscal;
use App\Models\SatUsoCfdi;
use App\Models\SatFormaPago;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Services\Clientes\ClienteRelationsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ClientesExport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use App\Mail\ClientAccountApprovedMail;



class ClienteController extends Controller
{
    public function __construct(
        private readonly ClienteRelationsService $clienteRelationsService,
        private readonly \App\Services\Clientes\ClienteService $clienteService
    ) {
        $this->authorizeResource(Cliente::class);
    }

    private const ITEMS_PER_PAGE = 10;
    private const CACHE_TTL = 60;

    // Constantes para validaciones y valores por defecto
    private const DEFAULT_COUNTRY = 'MX';
    private const RFC_GENERIC_FOREIGN = 'XEXX010101000';
    private const RFC_GENERIC_DOMESTIC = 'XAXX010101000';
    private const MIN_SEARCH_LENGTH = 2;
    private const MAX_SEARCH_LIMIT = 50;
    private const VALID_SORT_FIELDS = ['nombre_razon_social', 'rfc', 'email', 'created_at', 'activo'];
    private const VALID_SORT_DIRECTIONS = ['asc', 'desc'];
    private const DEFAULT_SORT_BY = 'created_at';
    private const DEFAULT_SORT_DIRECTION = 'desc';

    // Estados de registros relacionados que bloquean eliminación
    private const BLOCKING_STATES = [
        'cotizaciones' => ['cancelada'],
        'ventas' => ['cancelada'],
        'pedidos' => ['cancelado'],
        'rentas' => ['finalizada']
    ];



    private function getTipoPersonaNombre(?string $codigo): string
    {
        if (!$codigo) {
            return 'No aplica';
        }
        $tipos = $this->clienteService->getTiposPersona();
        return $tipos[$codigo] ?? $codigo;
    }

    private function getRegimenFiscalNombre(?string $codigo): string
    {
        if (!$codigo) {
            return 'No aplica';
        }
        $reg = $this->clienteService->getRegimenesFiscales();
        return isset($reg[$codigo]) ? ($reg[$codigo]['descripcion'] ?? $codigo) : $codigo;
    }

    private function getUsoCFDINombre(?string $codigo): string
    {
        if (!$codigo) {
            return 'No aplica';
        }
        $u = $this->clienteService->getUsosCFDI();
        return isset($u[$codigo]) ? ($u[$codigo]['descripcion'] ?? $codigo) : $codigo;
    }

    private function getEstadoNombre(?string $clave): ?string
    {
        if (!$clave)
            return 'No especificado';

        // Si la clave tiene 3 caracteres, buscar en la tabla SAT
        if (strlen($clave) === 3) {
            $est = $this->clienteService->getEstadosMexico();
            return $est[$clave] ?? $clave;
        }

        // Si es texto más largo, devolver tal cual (nuevo formato)
        return $clave;
    }



    private function hasFulltextIndex(): bool
    {
        $driver = Schema::getConnection()->getDriverName();
        return $driver === 'mysql';
    }

    // ========================= Query base =========================
    // En tu ClienteController (o trait/repo)
    private function buildSearchQuery(Request $request)
    {
        // Optimizar carga de relaciones - solo cargar las necesarias
        $withRelations = [];
        if ($request->input('include_relations', false)) {
            $withRelations = ['estadoSat', 'regimen', 'uso'];
        }

        $q = Cliente::query()->with($withRelations);

        if ($s = trim((string) $request->input('search', ''))) {
            // Use FULLTEXT search if available and query is long enough
            if (strlen($s) >= 3 && $this->hasFulltextIndex()) {
                $q->whereRaw("MATCH(nombre_razon_social, email, rfc) AGAINST(? IN NATURAL LANGUAGE MODE)", [$s]);
            } else {
                // Fallback to LIKE search (using ilike for PostgreSQL case-insensitivity)
                $operator = config('database.default') === 'pgsql' ? 'ilike' : 'like';
                $q->where(function ($w) use ($s, $operator) {
                    $w->where('nombre_razon_social', $operator, "%{$s}%")
                        ->orWhere('rfc', $operator, "%{$s}%")
                        ->orWhere('email', $operator, "%{$s}%")
                        ->orWhere('codigo', $operator, "%{$s}%");
                });
            }
        }

        if ($tp = $request->input('tipo_persona')) {
            $q->where('tipo_persona', $tp);
        }
        if ($rf = $request->input('regimen_fiscal')) {
            $q->where('regimen_fiscal', $rf);
        }
        if ($uso = $request->input('uso_cfdi')) {
            $q->where('uso_cfdi', $uso);
        }
        if ($edo = $request->input('estado')) {
            $q->where('estado', $edo);
        }

        // Filtrar por estado activo/inactivo
        if ($request->query->has('activo')) {
            $val = (string) $request->query('activo');

            if ($val === '1') {
                // Activos: true o NULL (considerar NULL como true por defecto)
                $q->where(function ($query) {
                    $query->where('activo', true)->orWhereNull('activo');
                });
            } elseif ($val === '0') {
                // Inactivos: solo false
                $q->where('activo', false);
            }
            // Si NO es '0' ni '1', NO filtramos (mostrar todos)
        }
        // Por defecto, mostrar todos los clientes (activos e inactivos)


        return $q->orderByDesc('created_at');
    }


    private function transformClientesPaginator($paginator)
    {
        $collection = $paginator->getCollection();

        $collection->transform(function ($cliente) {
            // Solo calcular nombres si las relaciones están cargadas
            $cliente->tipo_persona_nombre = $this->getTipoPersonaNombre($cliente->tipo_persona);

            if ($cliente->relationLoaded('regimen')) {
                $cliente->regimen_fiscal_nombre = $cliente->regimen?->descripcion ?? $this->getRegimenFiscalNombre($cliente->regimen_fiscal);
            } else {
                $cliente->regimen_fiscal_nombre = $this->getRegimenFiscalNombre($cliente->regimen_fiscal);
            }

            if ($cliente->relationLoaded('uso')) {
                $cliente->uso_cfdi_nombre = $cliente->uso?->descripcion ?? $this->getUsoCFDINombre($cliente->uso_cfdi);
            } else {
                $cliente->uso_cfdi_nombre = $this->getUsoCFDINombre($cliente->uso_cfdi);
            }

            if ($cliente->relationLoaded('estadoSat')) {
                $cliente->estado_nombre = $cliente->estadoSat?->nombre ?? $this->getEstadoNombre($cliente->estado);
            } else {
                $cliente->estado_nombre = $this->getEstadoNombre($cliente->estado);
            }

            $cliente->estado_texto = $cliente->activo ? 'Activo' : 'Inactivo';

            // Agregar indicador si requiere factura
            $cliente->requiere_factura_texto = $cliente->requiere_factura ? 'Sí requiere factura' : 'No requiere factura';

            // Agregar conteo de préstamos (usando el atributo cargado por withCount)
            $cliente->prestamos_count = $cliente->prestamos_count ?? 0;

            return $cliente;
        });

        $paginator->setCollection($collection);
        return $paginator;
    }

    // ========================= Validaciones =========================
    // ========================= Validaciones (Eliminadas - Se usan FormRequests) =========================


    private function formatClienteForView(Cliente $c): Cliente
    {
        $c->tipo_persona_nombre = $this->getTipoPersonaNombre($c->tipo_persona);
        $c->regimen_fiscal_nombre = $c->regimen?->descripcion ?? $this->getRegimenFiscalNombre($c->regimen_fiscal);
        $c->uso_cfdi_nombre = $c->uso?->descripcion ?? $this->getUsoCFDINombre($c->uso_cfdi);

        // Para el estado, usar el valor tal cual si no se encuentra en la tabla SAT
        // Esto permite que funcione tanto con códigos como con nombres completos
        $c->estado_nombre = $c->estadoSat?->nombre ?? $c->estado;
        $c->estado_texto = $c->activo ? 'Activo' : 'Inactivo';

        return $c;
    }

    /**
     * Crear respuesta de error para operaciones de cliente
     */
    private function handleClienteError(string $message, array $context = []): \Illuminate\Http\RedirectResponse
    {
        Log::error($message, $context);
        return redirect()->route('clientes.index')->with('error', $message);
    }

    /**
     * Crear respuesta de éxito para operaciones de cliente
     */
    private function handleClienteSuccess(string $message): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('clientes.index')->with('success', $message);
    }

    // ============================= CRUD =============================
    public function index(Request $request)
    {
        try {
            $query = $this->buildSearchQuery($request);

            $sortBy = $request->get('sort_by', self::DEFAULT_SORT_BY);
            $sortDirection = $request->get('sort_direction', self::DEFAULT_SORT_DIRECTION);

            if (!in_array($sortBy, self::VALID_SORT_FIELDS))
                $sortBy = self::DEFAULT_SORT_BY;
            if (!in_array($sortDirection, self::VALID_SORT_DIRECTIONS))
                $sortDirection = self::DEFAULT_SORT_DIRECTION;

            $query->withCount('prestamos');
            $query->orderBy($sortBy, $sortDirection);

            $clientes = $query->paginate(self::ITEMS_PER_PAGE)->appends($request->query());
            $clientes = $this->transformClientesPaginator($clientes);

            // Optimización de estadísticas: Una sola consulta para múltiples conteos
            // Usar sintaxis compatible con PostgreSQL
            // Agregamos suma de deuda total de clientes activos
            $stats = Cliente::selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN activo = true OR activo IS NULL THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN tipo_persona = 'fisica' THEN 1 ELSE 0 END) as personas_fisicas,
                SUM(CASE WHEN tipo_persona = 'moral' THEN 1 ELSE 0 END) as personas_morales,
                SUM(CASE WHEN EXTRACT(MONTH FROM created_at) = ? AND EXTRACT(YEAR FROM created_at) = ? THEN 1 ELSE 0 END) as nuevos_mes
            ", [now()->month, now()->year])->first();

            // Consulta separada para deuda total para no sobrecargar la consulta de conteo
            $deudaTotal = DB::table('cuentas_por_cobrar')
                ->whereIn('estado', ['pendiente', 'parcial', 'vencida'])
                ->sum('monto_pendiente');

            $total = (int) $stats->total;
            $activos = (int) $stats->activos;

            return Inertia::render('Clientes/Index', [
                'clientes' => $clientes,
                'estadisticas' => [
                    'total' => $total,
                    'activos' => $activos,
                    'inactivos' => $total - $activos,
                    'personas_fisicas' => (int) $stats->personas_fisicas,
                    'personas_morales' => (int) $stats->personas_morales,
                    'nuevos_mes' => (int) $stats->nuevos_mes,
                    'deuda_total' => (float) $deudaTotal,
                ],
                'pagination' => [
                    'current_page' => $clientes->currentPage(),
                    'last_page' => $clientes->lastPage(),
                    'per_page' => $clientes->perPage(),
                    'from' => $clientes->firstItem(),
                    'to' => $clientes->lastItem(),
                    'total' => $clientes->total(),
                ],
                'catalogs' => $this->clienteService->getCatalogData(),
                'filters' => $request->only(['search', 'tipo_persona', 'regimen_fiscal', 'uso_cfdi', 'activo', 'estado']),
                'sorting' => ['sort_by' => $sortBy, 'sort_direction' => $sortDirection],
            ]);
        } catch (Exception $e) {
            Log::error('Error en ClienteController@index: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Error al cargar la lista de clientes.');
        }
    }

    public function create()
    {
        return Inertia::render('Clientes/Create', [
            'catalogs' => $this->clienteService->getCatalogData(),
            'cliente' => [ // valores por defecto
                'tipo_persona' => 'fisica',
                'pais' => 'MX',
                'estado' => 'SON', // Sonora por defecto
                'uso_cfdi' => 'G03', // G03 - Gastos en general
                'price_list_id' => \App\Models\PriceList::where('clave', 'publico_general')->value('id'), // Default a público general
            ],
        ]);
    }

    public function store(StoreClienteRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            // Si no se muestra la dirección en el formulario, evitar sobrescribirla con null/''
            $camposDireccion = ['calle', 'numero_exterior', 'numero_interior', 'colonia', 'codigo_postal', 'municipio', 'estado'];
            if (!$request->boolean('mostrar_direccion')) {
                foreach ($camposDireccion as $campo) {
                    if (!array_key_exists($campo, $data) || $data[$campo] === null || $data[$campo] === '') {
                        unset($data[$campo]);
                    }
                }
            }

            // Establecer domicilio_fiscal_cp igual al codigo_postal si no se proporcionó uno específicamente
            if (empty($data['domicilio_fiscal_cp'])) {
                $data['domicilio_fiscal_cp'] = $data['codigo_postal'] ?? null;
            }

            // Hashear password si se proporcionó
            if (!empty($data['password'])) {
                $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
            } else {
                unset($data['password']); // Evitar guardar null/vacío si no es nullable o default en DB
            }

            // Crear cliente con manejo de race condition para RFC/email duplicados
            try {
                $cliente = Cliente::create($data);
            } catch (\Illuminate\Database\QueryException $qe) {
                // Código 23000 = Integrity constraint violation (duplicado)
                if ($qe->getCode() === '23000') {
                    $errorMessage = $qe->getMessage();

                    // Detectar qué campo causó el duplicado
                    if (stripos($errorMessage, 'rfc') !== false) {
                        throw ValidationException::withMessages([
                            'rfc' => 'El RFC ya está registrado. Otro usuario lo registró mientras usted completaba el formulario.'
                        ]);
                    }
                    if (stripos($errorMessage, 'email') !== false) {
                        throw ValidationException::withMessages([
                            'email' => 'El email ya está registrado. Otro usuario lo registró mientras usted completaba el formulario.'
                        ]);
                    }

                    // Error de duplicado genérico
                    throw ValidationException::withMessages([
                        'general' => 'Ya existe un cliente con estos datos. Por favor, verifique RFC y email.'
                    ]);
                }
                throw $qe; // Re-lanzar si no es error de duplicado
            }

            // Solo validar CFDI si el cliente requiere factura
            if ($data['requiere_factura'] ?? false) {
                $erroresCfdi = $cliente->validarParaCfdi();
                if (!empty($erroresCfdi)) {
                    DB::rollBack();
                    throw ValidationException::withMessages([
                        'cfdi' => 'El cliente no cumple con los requisitos para facturación CFDI: ' . implode(', ', $erroresCfdi)
                    ]);
                }
            }

            // Crear notificaciones directamente (sistema simplificado)
            try {
                \App\Models\UserNotification::createClientNotification($cliente);
                Log::info('Notificaciones creadas para nuevo cliente', ['cliente_id' => $cliente->id]);
            } catch (Exception $e) {
                Log::error('Error creando notificaciones para cliente', [
                    'cliente_id' => $cliente->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // No fallar la creación del cliente por error en notificaciones
            }

            DB::commit();

            Log::info('Cliente creado', ['id' => $cliente->id]);

            // Redirige a la lista con mensaje de éxito
            return redirect()->route('clientes.index')->with('success', 'Cliente creado correctamente');
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Error al crear cliente: ' . $e->getMessage(), [
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            // En lugar de redirect()->back()->with('error'), lanza el error
            // Inertia lo manejará y lo mostrará en form.errors
            throw $e;
        }
    }

    public function show(Cliente $cliente)
    {
        try {
            // Optimizar carga de relaciones - solo cargar las necesarias para mostrar
            $cliente->load(['regimen', 'uso', 'estadoSat', 'credenciales']);
            $cliente->loadCount(['cotizaciones', 'ventas', 'pedidos', 'facturas', 'tickets', 'citas', 'polizas']);
            $cliente = $this->formatClienteForView($cliente);
            // Append expensive attributes only here
            $cliente->append(['saldo_pendiente', 'credito_disponible']);

            // Historial de compras
            $historialCompras = \App\Models\Venta::where('cliente_id', $cliente->id)
                ->select(['id', 'numero_venta', 'fecha', 'total', 'pagado', 'metodo_pago', 'estado'])
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            // Historial de crédito
            $historialCredito = \App\Models\CuentasPorCobrar::whereHas('venta', function ($q) use ($cliente) {
                $q->where('cliente_id', $cliente->id);
            })->with(['venta:id,numero_venta'])
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            // Tickets de soporte
            $tickets = \App\Models\Ticket::where('cliente_id', $cliente->id)
                ->with(['asignado:id,name', 'categoria:id,nombre'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Citas / Visitas técnicas
            $citas = \App\Models\Cita::where('cliente_id', $cliente->id)
                ->with(['tecnico:id,name'])
                ->orderBy('fecha_hora', 'desc')
                ->limit(10)
                ->get();

            // Pólizas de servicio activas
            $polizas = \App\Models\PolizaServicio::where('cliente_id', $cliente->id)
                ->with(['plan:id,nombre'])
                ->orderBy('created_at', 'desc')
                ->get();

            return Inertia::render('Clientes/Show', [
                'cliente' => $cliente,
                'historialCompras' => $historialCompras,
                'historialCredito' => $historialCredito,
                'tickets' => $tickets,
                'citas' => $citas,
                'polizas' => $polizas,
            ]);
        } catch (ModelNotFoundException $e) {
            Log::warning('Cliente no encontrado', ['id' => request()->route('cliente')]);
            return redirect()->route('clientes.index')->with('error', 'Cliente no encontrado.');
        } catch (Exception $e) {
            Log::error('Error al mostrar cliente: ' . $e->getMessage());
            return redirect()->route('clientes.index')->with('error', 'Error al cargar el cliente.');
        }
    }

    public function edit(Cliente $cliente)
    {
        try {
            // Optimizar carga de relaciones - solo cargar las necesarias para editar
            $cliente->load(['regimen', 'uso', 'estadoSat']);
            $cliente = $this->formatClienteForView($cliente);
            $cliente->append(['saldo_pendiente', 'credito_disponible']);

            return Inertia::render('Clientes/Edit', [
                'cliente' => $cliente,
                'catalogs' => $this->clienteService->getCatalogData(),
                'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : [],
            ]);
        } catch (ModelNotFoundException $e) {
            Log::warning('Cliente no encontrado para edición', ['id' => request()->route('cliente')]);
            return redirect()->route('clientes.index')->with('error', 'Cliente no encontrado.');
        } catch (Exception $e) {
            Log::error('Error al cargar formulario de edición: ' . $e->getMessage());
            return redirect()->route('clientes.index')->with('error', 'Error al cargar el formulario de edición.');
        }
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            // Si no se muestra la dirección en el formulario, evitar sobrescribirla con null/''
            $camposDireccion = ['calle', 'numero_exterior', 'numero_interior', 'colonia', 'codigo_postal', 'municipio', 'estado'];
            if (!$request->boolean('mostrar_direccion')) {
                foreach ($camposDireccion as $campo) {
                    if (!array_key_exists($campo, $data) || $data[$campo] === null || $data[$campo] === '') {
                        unset($data[$campo]);
                    }
                }
            }

            // Establecer domicilio_fiscal_cp igual al codigo_postal si no se proporcionó uno específicamente
            if (empty($data['domicilio_fiscal_cp'])) {
                $data['domicilio_fiscal_cp'] = $data['codigo_postal'] ?? null;
            }

            // Actualizar datos con manejo de race condition para RFC/email duplicados
            try {
                $cliente->update($data);
            } catch (\Illuminate\Database\QueryException $qe) {
                // Código 23000 = Integrity constraint violation (duplicado)
                if ($qe->getCode() === '23000') {
                    $errorMessage = $qe->getMessage();

                    // Detectar qué campo causó el duplicado
                    if (stripos($errorMessage, 'rfc') !== false) {
                        throw ValidationException::withMessages([
                            'rfc' => 'El RFC ya está registrado por otro cliente.'
                        ]);
                    }
                    if (stripos($errorMessage, 'email') !== false) {
                        throw ValidationException::withMessages([
                            'email' => 'El email ya está registrado por otro cliente.'
                        ]);
                    }

                    // Error de duplicado genérico
                    throw ValidationException::withMessages([
                        'general' => 'Ya existe un cliente con estos datos. Por favor, verifique RFC y email.'
                    ]);
                }
                throw $qe; // Re-lanzar si no es error de duplicado
            }

            // Validar que el cliente actualizado tenga datos completos para CFDI
            // Pero NO bloquear la actualización si hay errores de CFDI
            $erroresCfdi = $cliente->fresh()->validarParaCfdi();
            if (!empty($erroresCfdi)) {
                Log::warning('Cliente actualizado pero no cumple requisitos CFDI', [
                    'cliente_id' => $cliente->id,
                    'errores_cfdi' => $erroresCfdi,
                ]);
                // No lanzar excepción, solo loguear advertencia
            }

            DB::commit();

            Log::info('Cliente actualizado', ['cliente_id' => $cliente->id]);

            // ✅ Redirige con mensaje flash (asegúrate de leerlo en el frontend)
            return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::warning('Cliente no encontrado para actualización', ['id' => $cliente->id ?? 'N/A']);

            // ✅ En lugar de redirect()->back(), lanza error 404 o redirige
            return redirect()->route('clientes.index')->with('error', 'Cliente no encontrado.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar cliente: ' . $e->getMessage(), [
                'cliente_id' => $cliente->id ?? 'N/A',
                'trace' => $e->getTraceAsString()
            ]);

            // ✅ ¡NO uses redirect()->back()! Lanza la excepción.
            // Inertia la capturará y la mostrará en form.errors
            throw $e;
        }
    }

    public function destroy(Cliente $cliente)
    {
        try {
            Log::info('Iniciando eliminación de cliente', [
                'cliente_id' => $cliente->id,
                'cliente_nombre' => $cliente->nombre_razon_social,
                'usuario_id' => Auth::id()
            ]);

            // Verificar permisos de eliminación
            Gate::authorize('delete', $cliente);

            // Verificar relaciones antes de eliminar
            $relaciones = $this->clienteRelationsService->verificarRelaciones($cliente);

            if (!empty($relaciones)) {
                // Crear mensaje simple y claro
                $mensajeSimple = $this->clienteRelationsService->generarMensajeSimple($cliente, $relaciones);

                Log::warning('Cliente no eliminado por relaciones existentes', [
                    'cliente_id' => $cliente->id,
                    'cliente_nombre' => $cliente->nombre_razon_social,
                    'estado_activo' => $cliente->activo,
                    'relaciones' => $relaciones
                ]);

                return redirect()->back()->with('error', $mensajeSimple);
            }

            DB::beginTransaction();
            $cliente->delete(); // Ahora usa soft delete
            DB::commit();

            Log::info('Cliente eliminado correctamente', [
                'cliente_id' => $cliente->id,
                'cliente_nombre' => $cliente->nombre_razon_social
            ]);

            return redirect()->route('clientes.index')->with('success', 'Cliente eliminado correctamente');

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::warning('Cliente no encontrado para eliminación', [
                'id' => request()->route('cliente'),
                'error' => $e->getMessage()
            ]);
            return redirect()->route('clientes.index')->with('error', 'Cliente no encontrado.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar cliente: ' . $e->getMessage(), [
                'cliente_id' => $cliente->id ?? 'N/A',
                'cliente_nombre' => $cliente->nombre_razon_social ?? 'N/A',
                'usuario_id' => Auth::id() ?? 'N/A',
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error interno al eliminar el cliente. Detalles: ' . $e->getMessage());
        }
    }

    // ======================== Funcionalidades extra ========================
    public function approve(Cliente $cliente)
    {
        try {
            DB::beginTransaction();
            $cliente->update(['activo' => true]);
            DB::commit();

            // Enviar notificación por correo
            try {
                Mail::to($cliente->email)->send(new ClientAccountApprovedMail($cliente));
                Log::info('Email de aprobación enviado', ['cliente_id' => $cliente->id]);
            } catch (Exception $mailEx) {
                Log::error('Error al enviar email de aprobación: ' . $mailEx->getMessage());
                // No detenemos el flujo si falla el correo
            }

            Log::info('Cliente aprobado', [
                'cliente_id' => $cliente->id,
                'usuario_id' => Auth::id()
            ]);

            return redirect()->back()->with('success', 'Cuenta de cliente aprobada correctamente. Ahora puede acceder al portal.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al aprobar cliente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al aprobar la cuenta.');
        }
    }

    public function toggle(Cliente $cliente)
    {
        try {
            DB::beginTransaction();
            $cliente->update(['activo' => !$cliente->activo]);
            DB::commit();

            Log::info('Estado de cliente cambiado', [
                'cliente_id' => $cliente->id,
                'cliente_nombre' => $cliente->nombre_razon_social,
                'nuevo_estado' => $cliente->activo ? 'activo' : 'inactivo',
                'usuario_id' => Auth::id()
            ]);

            // Retornar respuesta JSON para peticiones AJAX
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $cliente->activo ? 'Cliente activado correctamente' : 'Cliente desactivado correctamente',
                    'cliente' => [
                        'id' => $cliente->id,
                        'activo' => $cliente->activo,
                        'estado_texto' => $cliente->activo ? 'Activo' : 'Inactivo'
                    ]
                ]);
            }

            return redirect()->back()->with('success', $cliente->activo ? 'Cliente activado correctamente' : 'Cliente desactivado correctamente');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::warning('Cliente no encontrado para cambio de estado', ['id' => $cliente->id ?? 'N/A']);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado.'
                ], 404);
            }

            return redirect()->route('clientes.index')->with('error', 'Cliente no encontrado.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al cambiar estado del cliente: ' . $e->getMessage(), [
                'cliente_id' => $cliente->id ?? 'N/A',
                'trace' => $e->getTraceAsString()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hubo un problema al cambiar el estado del cliente.'
                ], 500);
            }

            return redirect()->back()->with('error', 'Hubo un problema al cambiar el estado del cliente.');
        }
    }
    // ============================= API/AJAX =============================
    public function validarRfc(Request $request): JsonResponse
    {
        try {
            $rfc = strtoupper(trim($request->input('rfc', '')));
            $clienteId = $request->input('cliente_id');

            if (empty($rfc)) {
                return response()->json(['success' => false, 'exists' => false, 'message' => 'RFC requerido'], 422);
            }
            if (!preg_match('/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/', $rfc)) {
                return response()->json(['success' => false, 'exists' => false, 'message' => 'Formato de RFC inválido'], 422);
            }

            // Allow generic RFCs
            if ($rfc === self::RFC_GENERIC_FOREIGN || $rfc === self::RFC_GENERIC_DOMESTIC) {
                return response()->json(['success' => true, 'exists' => false, 'message' => 'RFC genérico válido']);
            }

            $query = Cliente::where('rfc', $rfc);
            if ($clienteId)
                $query->where('id', '!=', $clienteId);

            $existe = $query->exists();
            $cliente = $existe ? $query->first() : null;

            return response()->json([
                'success' => true,
                'exists' => $existe,
                'message' => $existe ? 'RFC ya registrado' : 'RFC disponible',
                'cliente' => $existe ? ['id' => $cliente->id, 'nombre' => $cliente->nombre_razon_social] : null
            ]);
        } catch (Exception $e) {
            Log::error('Error validando RFC: ' . $e->getMessage(), ['rfc' => $request->input('rfc')]);
            return response()->json(['success' => false, 'exists' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    public function validarEmail(Request $request): JsonResponse
    {
        try {
            $email = strtolower(trim($request->input('email', '')));

            if (empty($email)) {
                return response()->json(['success' => false, 'message' => 'Email requerido'], 422);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['success' => false, 'message' => 'Formato de email inválido'], 422);
            }

            return response()->json(['success' => true, 'message' => 'Email con formato válido']);
        } catch (Exception $e) {
            Log::error('Error validando email: ' . $e->getMessage(), ['email' => $request->input('email')]);
            return response()->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    public function getRegimenesByTipoPersona(Request $request): JsonResponse
    {
        try {
            $tipoPersona = $request->input('tipo_persona');
            if (!in_array($tipoPersona, ['fisica', 'moral'])) {
                return response()->json(['success' => false, 'message' => 'Tipo de persona inválido'], 422);
            }

            $validos = SatRegimenFiscal::query()
                ->when($tipoPersona === 'fisica', fn($q) => $q->where('persona_fisica', true))
                ->when($tipoPersona === 'moral', fn($q) => $q->where('persona_moral', true))
                ->orderBy('clave')
                ->get(['clave', 'descripcion']);

            $opciones = [['value' => '', 'label' => 'Selecciona una opción']];
            foreach ($validos as $r) {
                $opciones[] = [
                    'value' => $r->clave,
                    'label' => "{$r->clave} - {$r->descripcion}",
                    'tipo_persona' => $tipoPersona
                ];
            }

            return response()->json([
                'success' => true,
                'regimenes' => $opciones,
                'tipo_persona' => $tipoPersona,
                'total' => $validos->count()
            ]);
        } catch (Exception $e) {
            Log::error('Error obteniendo regímenes fiscales: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $query = trim($request->input('q', ''));
            $limit = min((int) $request->input('limit', 10), self::MAX_SEARCH_LIMIT);

            if (mb_strlen($query) < self::MIN_SEARCH_LENGTH) {
                return response()->json(['success' => false, 'message' => 'Mínimo ' . self::MIN_SEARCH_LENGTH . ' caracteres para búsqueda'], 422);
            }

            $clientes = Cliente::where('activo', true)
                ->where(function ($q) use ($query) {
                    $q->where('nombre_razon_social', 'like', "%{$query}%")
                        ->orWhere('rfc', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                })
                ->select('id', 'nombre_razon_social', 'rfc', 'email', 'tipo_persona')
                ->limit($limit)
                ->get();

            $resultados = $clientes->map(fn($c) => [
                'id' => $c->id,
                'nombre' => $c->nombre_razon_social,
                'rfc' => $c->rfc,
                'email' => $c->email,
                'tipo_persona' => $this->getTipoPersonaNombre($c->tipo_persona),
                'label' => "{$c->nombre_razon_social} ({$c->rfc})"
            ]);

            return response()->json(['success' => true, 'clientes' => $resultados]);
        } catch (Exception $e) {
            Log::error('Error en búsqueda de clientes: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    public function export(Request $request)
    {
        try {
            // Verificar permisos de exportación
            Gate::authorize('export', Cliente::class);

            $query = $this->buildSearchQuery($request);
            $clientes = $query->get();

            $filename = 'clientes_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($clientes) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'ID',
                    'Nombre/Razón Social',
                    'Tipo Persona',
                    'RFC',
                    'Régimen Fiscal',
                    'Uso CFDI',
                    'Email',
                    'Teléfono',
                    'Dirección Completa',
                    'Estado (clave)',
                    'Estado (nombre)',
                    'Código Postal',
                    'Activo',
                    'Notas',
                    'Fecha Creación'
                ]);

                foreach ($clientes as $cliente) {
                    $direccion = trim(implode(' ', [
                        $cliente->calle,
                        $cliente->numero_exterior,
                        $cliente->numero_interior ? "Int. {$cliente->numero_interior}" : '',
                        $cliente->colonia,
                        $cliente->municipio
                    ]));

                    fputcsv($file, [
                        $cliente->id,
                        $cliente->nombre_razon_social,
                        $this->getTipoPersonaNombre($cliente->tipo_persona),
                        $cliente->rfc,
                        $this->getRegimenFiscalNombre($cliente->regimen_fiscal),
                        $this->getUsoCFDINombre($cliente->uso_cfdi),
                        $cliente->email,
                        $cliente->telefono,
                        $direccion,
                        $cliente->estado,
                        $this->getEstadoNombre($cliente->estado),
                        $cliente->codigo_postal,
                        $cliente->activo ? 'Sí' : 'No',
                        $cliente->notas,
                        $cliente->created_at?->format('d/m/Y H:i:s')
                    ]);
                }
                fclose($file);
            };

            Log::info('Exportación de clientes', ['total' => $clientes->count(), 'usuario' => Auth::id()]);

            return response()->stream($callback, 200, $headers);
        } catch (Exception $e) {
            Log::error('Error en exportación de clientes: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al exportar los clientes.');
        }
    }

    // ============================= Stats / Utils =============================
    public function stats(): JsonResponse
    {
        try {
            // Verificar permisos de estadísticas
            Gate::authorize('stats', Cliente::class);

            $stats = Cache::remember('clientes_stats', 5, function () {
                return [
                    'total' => Cliente::count(),
                    'activos' => Cliente::where('activo', true)->count(),
                    'inactivos' => Cliente::where('activo', false)->count(),
                    'personas_fisicas' => Cliente::where('tipo_persona', 'fisica')->count(),
                    'personas_morales' => Cliente::where('tipo_persona', 'moral')->count(),
                    'nuevos_mes' => Cliente::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                    'top_regimenes' => Cliente::select('regimen_fiscal')->selectRaw('COUNT(*) as total')->groupBy('regimen_fiscal')
                        ->orderByDesc('total')->limit(5)->get()->map(fn($item) => [
                            'regimen' => $item->regimen_fiscal,
                            'nombre' => $this->getRegimenFiscalNombre($item->regimen_fiscal),
                            'total' => $item->total
                        ]),
                    'top_estados' => Cliente::select('estado')->selectRaw('COUNT(*) as total')->groupBy('estado')
                        ->orderByDesc('total')->limit(5)->get()->map(fn($item) => [
                            'estado' => $item->estado,
                            'nombre' => $this->getEstadoNombre($item->estado),
                            'total' => $item->total,
                        ]),
                ];
            });

            return response()->json(['success' => true, 'stats' => $stats]);
        } catch (Exception $e) {
            Log::error('Error obteniendo estadísticas de clientes: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    public function clearCache(): JsonResponse
    {
        try {
            Cache::forget('tipos_persona');
            Cache::forget('regimenes_fiscales_db');
            Cache::forget('usos_cfdi_db');
            Cache::forget('estados_mexico_db');
            Cache::forget('clientes_stats');

            Log::info('Cache de clientes limpiada', ['usuario' => Auth::id()]);

            return response()->json(['success' => true, 'message' => 'Cache limpiada correctamente']);
        } catch (Exception $e) {
            Log::error('Error limpiando cache: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Verificar si un cliente puede ser eliminado (sin documentos relacionados)
     */
    public function canDelete(Cliente $cliente): JsonResponse
    {
        try {
            $relaciones = $this->clienteRelationsService->verificarRelaciones($cliente);

            $puedeEliminar = empty($relaciones);

            return response()->json([
                'success' => true,
                'can_delete' => $puedeEliminar,
                'relaciones' => $relaciones,
                'message' => $puedeEliminar
                    ? 'El cliente puede ser eliminado'
                    : 'El cliente tiene documentos relacionados: ' . implode(', ', $relaciones)
            ]);
        } catch (Exception $e) {
            Log::error('Error verificando si cliente puede ser eliminado: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Verificar si un cliente tiene préstamos
     */
    public function hasPrestamos(Cliente $cliente): JsonResponse
    {
        try {
            $prestamosCount = $cliente->prestamos()->count();
            $tienePrestamos = $prestamosCount > 0;

            return response()->json([
                'success' => true,
                'has_prestamos' => $tienePrestamos,
                'prestamos_count' => $prestamosCount,
                'message' => $tienePrestamos
                    ? "El cliente tiene {$prestamosCount} préstamo(s)"
                    : 'El cliente no tiene préstamos'
            ]);
        } catch (Exception $e) {
            Log::error('Error verificando préstamos del cliente: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    // Servicios extraidos: ClienteRelationsService
}
