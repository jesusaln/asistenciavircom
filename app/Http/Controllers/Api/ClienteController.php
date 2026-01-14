<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\SatEstado;
use App\Models\SatRegimenFiscal;
use App\Models\SatUsoCfdi;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
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

class ClienteController extends Controller
{
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

    // ========================= Helpers de catálogos (DB + Cache) =========================
    private function getTiposPersona(): array
    {
        return Cache::remember('tipos_persona', self::CACHE_TTL, fn() => [
            'fisica' => 'Persona Física',
            'moral' => 'Persona Moral',
        ]);
    }

    private function getRegimenesFiscales(): array
    {
        try {
            return Cache::remember('regimenes_fiscales_db', self::CACHE_TTL, function () {
                return SatRegimenFiscal::orderBy('clave')
                    ->get(['clave', 'descripcion', 'persona_fisica', 'persona_moral'])
                    ->keyBy('clave')
                    ->toArray();
            });
        } catch (\Exception $e) {
            Log::warning('Error obteniendo regímenes fiscales del cache, usando DB directa', ['error' => $e->getMessage()]);
            return SatRegimenFiscal::orderBy('clave')
                ->get(['clave', 'descripcion', 'persona_fisica', 'persona_moral'])
                ->keyBy('clave')
                ->toArray();
        }
    }

    private function getUsosCFDI(): array
    {
        try {
            return Cache::remember('usos_cfdi_db', self::CACHE_TTL, function () {
                return SatUsoCfdi::orderBy('clave')
                    ->get(['clave', 'descripcion', 'persona_fisica', 'persona_moral', 'activo'])
                    ->keyBy('clave')
                    ->toArray();
            });
        } catch (\Exception $e) {
            Log::warning('Error obteniendo usos CFDI del cache, usando DB directa', ['error' => $e->getMessage()]);
            return SatUsoCfdi::orderBy('clave')
                ->get(['clave', 'descripcion', 'persona_fisica', 'persona_moral', 'activo'])
                ->keyBy('clave')
                ->toArray();
        }
    }

    private function getEstadosMexico(): array
    {
        try {
            return Cache::remember('estados_mexico_db', self::CACHE_TTL, function () {
                return SatEstado::orderBy('nombre')
                    ->get(['clave', 'nombre'])
                    ->pluck('nombre', 'clave')
                    ->toArray();
            });
        } catch (\Exception $e) {
            Log::warning('Error obteniendo estados del cache, usando DB directa', ['error' => $e->getMessage()]);
            return SatEstado::orderBy('nombre')
                ->get(['clave', 'nombre'])
                ->pluck('nombre', 'clave')
                ->toArray();
        }
    }

    private function formatForVueSelect(array $options, bool $includeEmpty = false, bool $showCode = false): array
    {
        $formatted = collect($options)->map(function ($value, $key) use ($showCode) {
            if (is_array($value)) {
                $label = $value['descripcion'] ?? ($value['nombre'] ?? $key);
            } else {
                $label = $value;
            }
            return [
                'value' => $key,
                'label' => $showCode ? "{$key} - {$label}" : $label,
            ];
        })->values()->toArray();

        if ($includeEmpty) {
            array_unshift($formatted, ['value' => '', 'label' => 'Selecciona una opción']);
        }
        return $formatted;
    }

    private function getTipoPersonaNombre(?string $codigo): string
    {
        if (!$codigo) {
            return 'No aplica';
        }
        $tipos = $this->getTiposPersona();
        return $tipos[$codigo] ?? $codigo;
    }

    private function getRegimenFiscalNombre(?string $codigo): string
    {
        if (!$codigo) {
            return 'No aplica';
        }
        $reg = $this->getRegimenesFiscales();
        return isset($reg[$codigo]) ? ($reg[$codigo]['descripcion'] ?? $codigo) : $codigo;
    }

    private function getUsoCFDINombre(?string $codigo): string
    {
        if (!$codigo) {
            return 'No aplica';
        }
        $u = $this->getUsosCFDI();
        return isset($u[$codigo]) ? ($u[$codigo]['descripcion'] ?? $codigo) : $codigo;
    }

    private function getEstadoNombre(?string $clave): ?string
    {
        if (!$clave)
            return 'No especificado';

        if (strlen($clave) === 3) {
            $est = $this->getEstadosMexico();
            return $est[$clave] ?? $clave;
        }

        return $clave;
    }

    private function getCatalogData(): array
    {
        return [
            'tiposPersona' => $this->formatForVueSelect($this->getTiposPersona(), true),
            'regimenesFiscales' => $this->formatForVueSelect($this->getRegimenesFiscales(), true, false),
            'usosCFDI' => $this->formatForVueSelect($this->getUsosCFDI(), true, false),
            'estados' => $this->formatForVueSelect($this->getEstadosMexico(), true),
            'priceLists' => \App\Models\PriceList::activas()
                ->get(['id', 'nombre', 'descripcion'])
                ->map(function ($lista) {
                    return [
                        'value' => $lista->id,
                        'text' => $lista->nombre,
                        'descripcion' => $lista->descripcion,
                    ];
                })
                ->toArray(),
        ];
    }

    private function hasFulltextIndex(): bool
    {
        $driver = Schema::getConnection()->getDriverName();
        return $driver === 'mysql';
    }

    private function buildSearchQuery(Request $request)
    {
        $withRelations = [];
        if ($request->input('include_relations', false)) {
            $withRelations = ['estadoSat', 'regimen', 'uso'];
        }

        $q = \App\Models\Cliente::query()->with($withRelations);

        if ($s = trim((string) $request->input('search', ''))) {
            if (strlen($s) >= 3 && $this->hasFulltextIndex()) {
                $q->whereRaw("MATCH(nombre_razon_social, email, rfc) AGAINST(? IN NATURAL LANGUAGE MODE)", [$s]);
            } else {
                $q->where(function ($w) use ($s) {
                    $w->where('nombre_razon_social', 'like', "%{$s}%")
                        ->orWhere('rfc', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%");
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

        if ($request->query->has('activo')) {
            $val = (string) $request->query('activo');

            if ($val === '1') {
                $q->where(function ($query) {
                    $query->where('activo', true)->orWhereNull('activo');
                });
            } elseif ($val === '0') {
                $q->where('activo', false);
            }
        }

        return $q->orderByDesc('created_at');
    }

    private function transformClientesPaginator($paginator)
    {
        $collection = $paginator->getCollection();

        $collection->transform(function ($cliente) {
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
            $cliente->requiere_factura_texto = $cliente->requiere_factura ? 'Sí requiere factura' : 'No requiere factura';
            $cliente->prestamos_count = $cliente->prestamos_count ?? 0;

            return $cliente;
        });

        $paginator->setCollection($collection);
        return $paginator;
    }

    private function formatClienteForView(Cliente $c): Cliente
    {
        $c->tipo_persona_nombre = $this->getTipoPersonaNombre($c->tipo_persona);
        $c->regimen_fiscal_nombre = $c->regimen?->descripcion ?? $this->getRegimenFiscalNombre($c->regimen_fiscal);
        $c->uso_cfdi_nombre = $c->uso?->descripcion ?? $this->getUsoCFDINombre($c->uso_cfdi);
        $c->estado_nombre = $c->estadoSat?->nombre ?? $c->estado;
        $c->estado_texto = $c->activo ? 'Activo' : 'Inactivo';

        return $c;
    }

    // ============================= CRUD (API JSON Responses) =============================

    /**
     * Mostrar listado de clientes con búsqueda, filtros y paginación
     */
    public function index(Request $request): JsonResponse
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

            $noPaginate = $request->has('nopaginate') || $request->input('all') == '1';

            if ($noPaginate) {
                $clientes = $query->get();
                $clientesTransformados = $clientes->map(function ($cliente) {
                    return $this->formatClienteForView($cliente);
                });

                return response()->json([
                    'success' => true,
                    'data' => $clientesTransformados,
                ]);
            }

            $perPage = min((int) $request->input('per_page', self::ITEMS_PER_PAGE), 100);
            $paginator = $query->paginate($perPage)->appends($request->query());
            $paginator = $this->transformClientesPaginator($paginator);

            return response()->json([
                'success' => true,
                'data' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                ],
                'catalogs' => $this->getCatalogData(),
            ]);
        } catch (Exception $e) {
            Log::error('Error en API ClienteController@index: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar la lista de clientes.'
            ], 500);
        }
    }


    /**
     * Crear un nuevo cliente
     */
    public function store(StoreClienteRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            // Normalización de datos básicos
            $data['nombre_razon_social'] = trim($data['nombre_razon_social']);

            if (isset($data['rfc']) && !is_null($data['rfc'])) {
                $data['rfc'] = strtoupper(trim($data['rfc']));
            }

            if (isset($data['email']) && !is_null($data['email'])) {
                $data['email'] = strtolower(trim($data['email']));
            }

            if (!isset($data['domicilio_fiscal_cp']) || is_null($data['domicilio_fiscal_cp'])) {
                $data['domicilio_fiscal_cp'] = $data['codigo_postal'] ?? null;
            }

            try {
                $cliente = Cliente::create($data);
            } catch (\Illuminate\Database\QueryException $qe) {
                if ($qe->getCode() === '23000') {
                    $errorMessage = $qe->getMessage();

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

                    throw ValidationException::withMessages([
                        'general' => 'Ya existe un cliente con estos datos. Por favor, verifique RFC y email.'
                    ]);
                }
                throw $qe;
            }

            if ($data['requiere_factura'] ?? false) {
                $erroresCfdi = $cliente->validarParaCfdi();
                if (!empty($erroresCfdi)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'El cliente no cumple con los requisitos para facturación CFDI',
                        'errors' => ['cfdi' => $erroresCfdi]
                    ], 422);
                }
            }

            try {
                \App\Models\UserNotification::createClientNotification($cliente);
                Log::info('Notificaciones creadas para nuevo cliente', ['cliente_id' => $cliente->id]);
            } catch (\Exception $e) {
                Log::error('Error creando notificaciones para cliente', [
                    'cliente_id' => $cliente->id,
                    'error' => $e->getMessage(),
                ]);
            }

            DB::commit();

            Log::info('Cliente creado via API', ['id' => $cliente->id]);

            return response()->json([
                'success' => true,
                'message' => 'Cliente creado correctamente',
                'data' => $cliente
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al crear cliente via API: ' . $e->getMessage(), [
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar un cliente específico
     */
    public function show($id): JsonResponse
    {
        try {
            $cliente = Cliente::findOrFail($id);
            $cliente->load(['regimen', 'uso', 'estadoSat', 'priceList']);
            $cliente->loadCount(['cotizaciones', 'ventas', 'pedidos', 'facturas']);
            $cliente = $this->formatClienteForView($cliente);

            return response()->json([
                'success' => true,
                'data' => $cliente
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        } catch (Exception $e) {
            Log::error('Error al mostrar cliente via API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el cliente.'
            ], 500);
        }
    }

    /**
     * Actualizar un cliente existente
     */
    public function update(UpdateClienteRequest $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $cliente = Cliente::findOrFail($id);
            $data = $request->validated();

            // ⚠️ LOG TEMPORAL - Ver qué datos llegan
            Log::info('=== DATOS RECIBIDOS EN UPDATE ===', [
                'cliente_id' => $id,
                'requiere_factura' => $request->input('requiere_factura'),
                'rfc' => $request->input('rfc'),
                'regimen_fiscal' => $request->input('regimen_fiscal'),
                'uso_cfdi' => $request->input('uso_cfdi'),
                'email' => $request->input('email'),
                'ALL_DATA' => $request->all()
            ]);

            $data['nombre_razon_social'] = trim($data['nombre_razon_social']);

            if (isset($data['rfc']) && !is_null($data['rfc'])) {
                $data['rfc'] = strtoupper(trim($data['rfc']));
            }

            if (isset($data['email']) && !is_null($data['email'])) {
                $data['email'] = strtolower(trim($data['email']));
            }

            if (isset($data['codigo_postal']) && !is_null($data['codigo_postal'])) {
                $data['domicilio_fiscal_cp'] = $data['codigo_postal'];
            }

            try {
                $cliente->update($data);
            } catch (\Illuminate\Database\QueryException $qe) {
                if ($qe->getCode() === '23000') {
                    $errorMessage = $qe->getMessage();

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

                    throw ValidationException::withMessages([
                        'general' => 'Ya existe un cliente con estos datos. Por favor, verifique RFC y email.'
                    ]);
                }
                throw $qe;
            }

            $erroresCfdi = $cliente->fresh()->validarParaCfdi();
            if (!empty($erroresCfdi)) {
                Log::warning('Cliente actualizado pero no cumple requisitos CFDI', [
                    'cliente_id' => $cliente->id,
                    'errores_cfdi' => $erroresCfdi,
                ]);
            }

            DB::commit();

            Log::info('Cliente actualizado via API', ['cliente_id' => $cliente->id]);

            return response()->json([
                'success' => true,
                'message' => 'Cliente actualizado correctamente',
                'data' => $cliente->fresh()
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar cliente via API: ' . $e->getMessage(), [
                'cliente_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar un cliente
     */
    public function destroy($id): JsonResponse
    {
        try {
            $cliente = Cliente::findOrFail($id);

            Log::info('Iniciando eliminación de cliente via API', [
                'cliente_id' => $cliente->id,
                'cliente_nombre' => $cliente->nombre_razon_social,
            ]);

            $relaciones = $this->verificarRelacionesCliente($cliente);

            if (!empty($relaciones)) {
                $mensajeSimple = $this->generarMensajeSimple($cliente, $relaciones);

                Log::warning('Cliente no eliminado por relaciones existentes', [
                    'cliente_id' => $cliente->id,
                    'relaciones' => $relaciones
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $mensajeSimple,
                    'relaciones' => $relaciones
                ], 422);
            }

            DB::beginTransaction();
            $cliente->delete();
            DB::commit();

            Log::info('Cliente eliminado correctamente via API', [
                'cliente_id' => $cliente->id,
                'cliente_nombre' => $cliente->nombre_razon_social
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cliente eliminado correctamente'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar cliente via API: ' . $e->getMessage(), [
                'cliente_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error interno al eliminar el cliente.'
            ], 500);
        }
    }

    // ======================== Funcionalidades extra ========================

    /**
     * Activar/desactivar cliente
     */
    public function toggle($id): JsonResponse
    {
        try {
            $cliente = Cliente::findOrFail($id);

            DB::beginTransaction();
            $cliente->update(['activo' => !$cliente->activo]);
            DB::commit();

            Log::info('Estado de cliente cambiado via API', [
                'cliente_id' => $cliente->id,
                'nuevo_estado' => $cliente->activo ? 'activo' : 'inactivo',
            ]);

            return response()->json([
                'success' => true,
                'message' => $cliente->activo ? 'Cliente activado correctamente' : 'Cliente desactivado correctamente',
                'data' => [
                    'id' => $cliente->id,
                    'activo' => $cliente->activo,
                    'estado_texto' => $cliente->activo ? 'Activo' : 'Inactivo'
                ]
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado.'
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al cambiar estado del cliente via API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hubo un problema al cambiar el estado del cliente.'
            ], 500);
        }
    }

    /**
     * Verificar si un email ya existe
     */
    public function checkEmail(Request $request): JsonResponse
    {
        try {
            $email = strtolower(trim($request->input('email', '')));
            $clienteId = $request->input('cliente_id');

            if (empty($email)) {
                return response()->json([
                    'success' => false,
                    'exists' => false,
                    'message' => 'Email requerido'
                ], 422);
            }

            $query = Cliente::where('email', $email);
            if ($clienteId) {
                $query->where('id', '!=', $clienteId);
            }

            $exists = $query->exists();

            return response()->json([
                'success' => true,
                'exists' => $exists,
                'message' => $exists ? 'Email ya registrado' : 'Email disponible'
            ]);
        } catch (Exception $e) {
            Log::error('Error validando email via API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Validar RFC
     */
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
            Log::error('Error validando RFC via API: ' . $e->getMessage());
            return response()->json(['success' => false, 'exists' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Obtener regímenes fiscales por tipo de persona
     */
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
            Log::error('Error obteniendo regímenes fiscales via API: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Búsqueda rápida de clientes
     */
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
            Log::error('Error en búsqueda de clientes via API: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Obtener estadísticas de clientes
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = Cache::remember('clientes_stats', 5, function () {
                return [
                    'total' => Cliente::count(),
                    'activos' => Cliente::where('activo', true)->count(),
                    'inactivos' => Cliente::where('activo', false)->count(),
                    'personas_fisicas' => Cliente::where('tipo_persona', 'fisica')->count(),
                    'personas_morales' => Cliente::where('tipo_persona', 'moral')->count(),
                    'nuevos_mes' => Cliente::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                ];
            });

            return response()->json(['success' => true, 'stats' => $stats]);
        } catch (Exception $e) {
            Log::error('Error obteniendo estadísticas de clientes via API: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Obtener catálogos SAT
     */
    public function catalogs(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'catalogs' => $this->getCatalogData()
            ]);
        } catch (Exception $e) {
            Log::error('Error obteniendo catálogos via API: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    private function verificarRelacionesCliente(Cliente $cliente): array
    {
        $relaciones = [];

        $cotizacionesCount = $cliente->cotizaciones()->count();
        if ($cotizacionesCount > 0) {
            $relaciones[] = "tiene {$cotizacionesCount} cotización(es)";
        }

        if (!$cliente->activo) {
            $ventasCount = $cliente->ventas()->count();
            if ($ventasCount > 0) {
                $relaciones[] = "tiene {$ventasCount} venta(s)";
            }

            $pedidosActivos = $cliente->pedidos()->where('estado', '!=', 'cancelado')->count();
            if ($pedidosActivos > 0) {
                $relaciones[] = "tiene {$pedidosActivos} pedido(s) activo(s)";
            }

            return $relaciones;
        }

        $ventasCount = $cliente->ventas()->count();
        if ($ventasCount > 0) {
            $relaciones[] = "tiene {$ventasCount} venta(s)";
        }

        $pedidosActivos = $cliente->pedidos()->where('estado', '!=', 'cancelado')->count();
        if ($pedidosActivos > 0) {
            $relaciones[] = "tiene {$pedidosActivos} pedido(s) activo(s)";
        }

        $facturasCount = $cliente->facturas()->count();
        if ($facturasCount > 0) {
            $relaciones[] = "tiene {$facturasCount} factura(s) emitida(s)";
        }

        $rentasActivas = $cliente->rentas()->where('estado', '!=', 'finalizada')->count();
        if ($rentasActivas > 0) {
            $relaciones[] = "tiene {$rentasActivas} renta(s) activa(s)";
        }

        $prestamosActivos = $cliente->prestamos()->count(); // Asumiendo que prestamos() existe en el modelo
        if ($prestamosActivos > 0) {
            $relaciones[] = "tiene {$prestamosActivos} préstamo(s)";
        }

        return $relaciones;
    }

    private function generarMensajeSimple(Cliente $cliente, array $relaciones): string
    {
        $count = count($relaciones);

        if ($count === 1) {
            $mensaje = 'No se puede eliminar el cliente "' . $cliente->nombre_razon_social . '" porque ' . $relaciones[0] . '.';
        } elseif ($count <= 3) {
            $mensaje = 'No se puede eliminar el cliente "' . $cliente->nombre_razon_social . '" porque ' . implode(' y ', $relaciones) . '.';
        } else {
            $mensaje = 'No se puede eliminar el cliente "' . $cliente->nombre_razon_social . '" porque tiene múltiples registros relacionados (' . $count . ' tipos de relación).';
        }

        $mensaje .= "\n\nPara eliminar este cliente, cancele o elimine los registros relacionados.";

        return $mensaje;
    }
}
