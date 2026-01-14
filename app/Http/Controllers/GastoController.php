<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\CategoriaGasto;
use App\Models\CuentasPorPagar;
use App\Models\Proveedor;
use App\Models\Proyecto;
use App\Enums\EstadoCompra;
use App\Services\Compras\CompraCuentasPagarService;
use App\Services\Compras\CompraValidacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GastoController extends Controller
{
    public function __construct(
        private readonly CompraCuentasPagarService $cuentasPagarService
    ) {
    }

    /**
     * Listar gastos operativos
     */
    public function index(Request $request)
    {
        $perPage = (int) ($request->integer('per_page') ?: 10);
        $validPerPages = [10, 15, 25, 50, 100];
        if (!in_array($perPage, $validPerPages)) {
            $perPage = 10;
        }

        $query = Compra::with(['proveedor', 'categoriaGasto', 'cuentasPorPagar', 'proyecto', 'createdBy'])
            ->where('tipo', 'gasto')
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($search = trim($request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_compra', 'like', "%{$search}%")
                    ->orWhere('notas', 'like', "%{$search}%")
                    ->orWhereHas('proveedor', fn($q) => $q->where('nombre', 'like', "%{$search}%"));
            });
        }

        if ($categoriaId = $request->get('categoria_id')) {
            $query->where('categoria_gasto_id', $categoriaId);
        }

        if ($estado = $request->get('estado')) {
            $query->where('estado', $estado);
        }

        if ($proyectoId = $request->get('proyecto_id')) {
            $query->where('proyecto_id', $proyectoId);
        }

        $gastos = $query->paginate($perPage)->withQueryString();
        $categorias = CategoriaGasto::activas()->orderBy('nombre')->get();
        $proyectos = Proyecto::orderBy('nombre')->get(['id', 'nombre']);

        return Inertia::render('Gastos/Index', [
            'gastos' => $gastos,
            'categorias' => $categorias,
            'proyectos' => $proyectos,
            'filters' => $request->only(['search', 'categoria_id', 'proyecto_id', 'estado', 'per_page']),
        ]);
    }

    /**
     * Formulario de creación
     */
    public function create()
    {
        $categorias = CategoriaGasto::activas()->orderBy('nombre')->get();
        // ✅ FIX: Usar campo booleano 'activo' (no 'estado' que es para dirección)
        $proveedores = Proveedor::where('activo', true)->orderBy('nombre_razon_social')->get();
        $cuentasBancarias = \App\Models\CuentaBancaria::activas()->orderBy('banco')->orderBy('nombre')->get(['id', 'nombre', 'banco', 'saldo_actual']);
        $proyectos = Proyecto::orderBy('nombre')->get(['id', 'nombre']);

        return Inertia::render('Gastos/Create', [
            'categorias' => $categorias,
            'proveedores' => $proveedores,
            'cuentasBancarias' => $cuentasBancarias,
            'proyectos' => $proyectos,
        ]);
    }

    /**
     * Guardar nuevo gasto
     */
    public function store(Request $request)
    {
        // ✅ FIX: Eliminadas reglas duplicadas
        $validated = $request->validate([
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'categoria_gasto_id' => 'required|exists:categoria_gastos,id,activo,1',
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'required|string|max:500',
            'fecha' => 'nullable|date',
            'metodo_pago' => 'required|string',
            'notas' => 'nullable|string',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            'proyecto_id' => 'nullable|exists:proyectos,id',
            // Campos CFDI opcionales (cuando se importa desde XML)
            'cfdi_uuid' => 'nullable|string|max:36',
            'cfdi_folio' => 'nullable|string|max:50',
            'cfdi_serie' => 'nullable|string|max:25',
            'cfdi_tipo_comprobante' => 'nullable|string|max:5',
            'cfdi_forma_pago' => 'nullable|string|max:5',
            'cfdi_metodo_pago' => 'nullable|string|max:5',
            'cfdi_uso' => 'nullable|string|max:10',
            'cfdi_fecha' => 'nullable|date',
            'cfdi_emisor_rfc' => 'nullable|string|max:20',
            'cfdi_emisor_nombre' => 'nullable|string|max:255',
            // Si viene del XML, podemos recibir subtotal e IVA directamente
            'subtotal_cfdi' => 'nullable|numeric|min:0',
            'iva_cfdi' => 'nullable|numeric|min:0',
            'descuento_cfdi' => 'nullable|numeric|min:0',
        ]);

        try {
            \DB::transaction(function () use ($validated) {
                // Si viene del CFDI, usar subtotal e IVA del XML
                if (!empty($validated['subtotal_cfdi']) && !empty($validated['iva_cfdi'])) {
                    $subtotal = $validated['subtotal_cfdi'];
                    $iva = $validated['iva_cfdi'];
                    $monto = $validated['monto'];
                    $descuento = $validated['descuento_cfdi'] ?? 0;
                } else {
                    // Calcular IVA normalmente
                    $monto = $validated['monto'];
                    $ivaRate = \App\Services\EmpresaConfiguracionService::getIvaPorcentaje() / 100;
                    $subtotal = $monto / (1 + $ivaRate);
                    $iva = $monto - $subtotal;
                    $descuento = 0;
                }

                // ✅ FIX: Generar número de gasto
                $numeroGasto = $this->generarNumeroGasto();

                // Crear gasto (es una compra con tipo='gasto')
                $gasto = Compra::create([
                    'numero_compra' => $numeroGasto, // ✅ FIX: Agregar número
                    'tipo' => 'gasto',
                    'categoria_gasto_id' => $validated['categoria_gasto_id'],
                    'proveedor_id' => $validated['proveedor_id'],
                    'almacen_id' => null, // Los gastos no tienen almacén
                    'metodo_pago' => $validated['metodo_pago'],
                    'cuenta_bancaria_id' => $validated['cuenta_bancaria_id'] ?? null,
                    'fecha_compra' => $validated['fecha'] ?? now(),
                    'subtotal' => $subtotal,
                    'descuento_general' => $descuento,
                    'descuento_items' => 0,
                    'iva' => $iva,
                    'total' => $monto,
                    'notas' => $validated['descripcion'] . ($validated['notas'] ? "\n\n" . $validated['notas'] : ''),
                    'estado' => EstadoCompra::Procesada->value,
                    'inventario_procesado' => false, // Gastos no afectan inventario
                    'user_id' => Auth::id(), // ✅ FIX: Agregar user_id
                    'proyecto_id' => $validated['proyecto_id'] ?? null,
                    // Campos CFDI
                    'cfdi_uuid' => $validated['cfdi_uuid'] ?? null,
                    'cfdi_folio' => $validated['cfdi_folio'] ?? null,
                    'cfdi_serie' => $validated['cfdi_serie'] ?? null,
                    'cfdi_tipo_comprobante' => $validated['cfdi_tipo_comprobante'] ?? null,
                    'cfdi_forma_pago' => $validated['cfdi_forma_pago'] ?? null,
                    'cfdi_metodo_pago' => $validated['cfdi_metodo_pago'] ?? null,
                    'cfdi_uso' => $validated['cfdi_uso'] ?? null,
                    'cfdi_fecha' => $validated['cfdi_fecha'] ?? null,
                    'cfdi_emisor_rfc' => $validated['cfdi_emisor_rfc'] ?? null,
                    'cfdi_emisor_nombre' => $validated['cfdi_emisor_nombre'] ?? null,
                ]);

                // Crear cuenta por pagar
                // Usar fecha del CFDI si existe, si no usar fecha del gasto
                $fechaBaseCuenta = $validated['cfdi_fecha'] ?? $validated['fecha'] ?? null;
                $cuentaPorPagar = $this->cuentasPagarService->crearCuentaPorPagar($gasto, $monto, $fechaBaseCuenta);

                // ✅ Si hay cuenta bancaria seleccionada, descontar del banco y marcar como pagado
                if (!empty($validated['cuenta_bancaria_id'])) {
                    $cuentaBancaria = \App\Models\CuentaBancaria::find($validated['cuenta_bancaria_id']);
                    if ($cuentaBancaria) {
                        // Descontar del banco
                        $concepto = "Gasto: {$validated['descripcion']} ({$gasto->numero_compra})";
                        $cuentaBancaria->registrarMovimiento('retiro', $monto, $concepto, 'pago');

                        // Marcar la cuenta por pagar como pagada
                        $cuentaPorPagar->update([
                            'monto_pagado' => $monto,
                            'monto_pendiente' => 0,
                            'estado' => 'pagado',
                            'notas' => 'Pagado de contado al registrar el gasto',
                        ]);

                        \Log::info('Gasto pagado de contado - Banco actualizado', [
                            'gasto_id' => $gasto->id,
                            'cuenta_bancaria_id' => $cuentaBancaria->id,
                            'monto' => $monto
                        ]);
                    }
                }

                \Log::info('Gasto creado exitosamente', [
                    'gasto_id' => $gasto->id,
                    'numero' => $gasto->numero_compra,
                    'monto' => $monto
                ]);
            });

            return redirect()->route('gastos.index')->with('success', 'Gasto registrado exitosamente.');
        } catch (\Exception $e) {
            \Log::error('Error al crear gasto: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Ver detalle de gasto
     */
    public function show($id)
    {
        $gasto = Compra::with(['proveedor', 'categoriaGasto', 'cuentasPorPagar'])
            ->where('tipo', 'gasto')
            ->findOrFail($id);

        return Inertia::render('Gastos/Show', [
            'gasto' => $gasto,
        ]);
    }

    /**
     * Formulario de edición
     */
    public function edit($id)
    {
        $gasto = Compra::where('tipo', 'gasto')->findOrFail($id);

        // ✅ FIX: No permitir editar gastos cancelados
        if ($gasto->estado === EstadoCompra::Cancelada->value) {
            return redirect()->back()->with('error', 'No se pueden editar gastos cancelados.');
        }

        if ($gasto->estado !== EstadoCompra::Procesada->value) {
            return redirect()->back()->with('error', 'Solo se pueden editar gastos procesados.');
        }

        $categorias = CategoriaGasto::activas()->orderBy('nombre')->get();
        // ✅ FIX: Usar campo booleano 'activo' (no 'estado' que es para dirección)
        $proveedores = Proveedor::where('activo', true)->orderBy('nombre_razon_social')->get();
        $proyectos = Proyecto::orderBy('nombre')->get(['id', 'nombre']);

        return Inertia::render('Gastos/Edit', [
            'gasto' => $gasto,
            'categorias' => $categorias,
            'proveedores' => $proveedores,
            'proyectos' => $proyectos,
        ]);
    }

    /**
     * Actualizar gasto
     */
    public function update(Request $request, $id)
    {
        $gasto = Compra::with('cuentasPorPagar')->where('tipo', 'gasto')->findOrFail($id);

        if ($gasto->estado !== EstadoCompra::Procesada->value) {
            return redirect()->back()->with('error', 'Solo se pueden editar gastos procesados.');
        }

        // Validación de integridad si hay pagos
        if ($gasto->cuentasPorPagar && $gasto->cuentasPorPagar->monto_pagado > 0) {
            if (
                $request->filled('monto') && abs($request->monto - $gasto->total) > 0.01 ||
                $request->filled('proveedor_id') && $request->proveedor_id != $gasto->proveedor_id
            ) {
                return redirect()->back()->with('error', 'No se puede modificar el monto ni el proveedor de un gasto que ya tiene pagos registrados.');
            }
        }

        // ✅ FIX: Eliminadas reglas duplicadas
        $validated = $request->validate([
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'categoria_gasto_id' => 'required|exists:categoria_gastos,id,activo,1',
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'required|string|max:500',
            'fecha' => 'nullable|date',
            'metodo_pago' => 'required|string',
            'notas' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($gasto, $validated) {
                // Calcular nuevos montos
                $monto = $validated['monto'];
                $ivaRate = \App\Services\EmpresaConfiguracionService::getIvaPorcentaje() / 100;
                $subtotal = $monto / (1 + $ivaRate);
                $iva = $monto - $subtotal;

                // Actualizar gasto
                $gasto->update([
                    'categoria_gasto_id' => $validated['categoria_gasto_id'],
                    'proveedor_id' => $validated['proveedor_id'],
                    'metodo_pago' => $validated['metodo_pago'],
                    'fecha_compra' => $validated['fecha'] ?? now(),
                    'subtotal' => $subtotal,
                    'iva' => $iva,
                    'total' => $monto,
                    'notas' => $validated['descripcion'] . ($validated['notas'] ? "\n\n" . $validated['notas'] : ''),
                ]);

                // Actualizar cuenta por pagar de forma segura
                $this->cuentasPagarService->actualizarCuentaPorPagar($gasto, $monto);

                \Log::info('Gasto actualizado exitosamente', ['gasto_id' => $gasto->id]);
            });

            return redirect()->route('gastos.index')->with('success', 'Gasto actualizado exitosamente.');
        } catch (\Exception $e) {
            \Log::error('Error al actualizar gasto: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Cancelar gasto
     */
    public function cancel($id)
    {
        try {
            $gasto = Compra::where('tipo', 'gasto')->findOrFail($id);

            if ($gasto->estado !== EstadoCompra::Procesada->value) {
                return redirect()->back()->with('error', 'Solo se pueden cancelar gastos procesados.');
            }

            DB::transaction(function () use ($gasto) {
                // Cancelar cuenta por pagar
                $this->cuentasPagarService->cancelarCuentaPorPagar($gasto);

                // Cambiar estado
                $gasto->update(['estado' => EstadoCompra::Cancelada->value]);
            });

            return redirect()->route('gastos.index')->with('success', 'Gasto cancelado exitosamente.');
        } catch (\Exception $e) {
            \Log::error('Error al cancelar gasto: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Eliminar gasto
     */
    public function destroy($id)
    {
        try {
            $gasto = Compra::with('cuentasPorPagar')->where('tipo', 'gasto')->findOrFail($id);

            // ✅ FIX: Validar que no tenga pagos registrados
            if ($gasto->cuentasPorPagar && $gasto->cuentasPorPagar->monto_pagado > 0) {
                return redirect()->back()->with('error', 'No se puede eliminar un gasto que ya tiene pagos registrados.');
            }

            if ($gasto->estado === EstadoCompra::Procesada->value) {
                DB::transaction(function () use ($gasto) {
                    // Cancelar primero
                    $this->cuentasPagarService->cancelarCuentaPorPagar($gasto);
                    $gasto->update(['estado' => EstadoCompra::Cancelada->value]);
                    $gasto->delete();
                });
            } else {
                $gasto->delete();
            }

            return redirect()->route('gastos.index')->with('success', 'Gasto eliminado correctamente.');
        } catch (\Exception $e) {
            \Log::error('Error al eliminar gasto: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Genera un número de gasto único secuencial.
     */
    private function generarNumeroGasto(): string
    {
        $ultimoGasto = Compra::where('tipo', 'gasto')
            ->where('numero_compra', 'LIKE', 'G%')
            ->lockForUpdate()
            ->orderBy('id', 'desc')
            ->first();

        if (!$ultimoGasto || !$ultimoGasto->numero_compra) {
            return 'G0001';
        }

        $matches = [];
        if (preg_match('/G(\d+)$/', $ultimoGasto->numero_compra, $matches)) {
            $ultimoNumero = (int) $matches[1];
            $siguienteNumero = $ultimoNumero + 1;
            $nuevoNumero = 'G' . str_pad((string) $siguienteNumero, 4, '0', STR_PAD_LEFT);

            // Verificar que no exista ya
            while (Compra::where('numero_compra', $nuevoNumero)->exists()) {
                $siguienteNumero++;
                $nuevoNumero = 'G' . str_pad((string) $siguienteNumero, 4, '0', STR_PAD_LEFT);
            }

            return $nuevoNumero;
        }

        return 'G0001';
    }

    /**
     * Parsear archivo XML de CFDI y retornar datos para crear gasto
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function parseXmlCfdi(Request $request)
    {
        $request->validate([
            'xml_file' => 'required|file|max:5120', // Máximo 5MB
        ]);

        try {
            $file = $request->file('xml_file');

            // Validar que sea XML
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();

            if ($extension !== 'xml' && !str_contains($mimeType, 'xml')) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo debe ser un XML válido.',
                ], 422);
            }

            $xmlContent = file_get_contents($file->path());

            // Parsear XML usando el servicio existente
            $parserService = app(\App\Services\CfdiXmlParserService::class);
            $data = $parserService->parseCfdiXml($xmlContent);

            // ✅ Poblar automáticamente catálogos SAT desde los conceptos del XML
            $satStats = $parserService->poblarCatalogosSatDesdeConceptos($data['conceptos']);
            $data['sat_catalogs_stats'] = $satStats;

            // Buscar proveedor por RFC del emisor
            $proveedor = null;
            if (!empty($data['emisor']['rfc'])) {
                $proveedor = $parserService->findProveedorByRfc($data['emisor']['rfc']);
            }

            $data['proveedor_encontrado'] = $proveedor ? [
                'id' => $proveedor->id,
                'nombre' => $proveedor->nombre_razon_social,
                'rfc' => $proveedor->rfc,
            ] : null;

            // Para gastos, simplificamos: no mapeamos a productos, solo extraemos conceptos como descripción
            $data['descripcion_sugerida'] = $this->generarDescripcionDesdeConceptos($data['conceptos']);

            \Log::info('XML CFDI parseado para gasto', [
                'folio' => $data['serie'] . $data['folio'],
                'emisor_rfc' => $data['emisor']['rfc'] ?? 'N/A',
                'total' => $data['total'],
            ]);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al parsear XML CFDI para gasto', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el XML: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Genera una descripción combinada desde los conceptos del CFDI
     */
    private function generarDescripcionDesdeConceptos(array $conceptos): string
    {
        if (empty($conceptos)) {
            return '';
        }

        // Tomar las descripciones de los conceptos y combinarlas
        $descripciones = array_map(function ($concepto) {
            $desc = $concepto['descripcion'] ?? '';
            // Limitar longitud de cada descripción
            if (strlen($desc) > 100) {
                $desc = substr($desc, 0, 97) . '...';
            }
            return $desc;
        }, $conceptos);

        // Combinar con separador
        $descripcionCombinada = implode('; ', array_filter($descripciones));

        // Limitar longitud total
        if (strlen($descripcionCombinada) > 500) {
            $descripcionCombinada = substr($descripcionCombinada, 0, 497) . '...';
        }

        return $descripcionCombinada;
    }
}
