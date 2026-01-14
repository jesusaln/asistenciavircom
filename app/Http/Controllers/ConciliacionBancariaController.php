<?php

namespace App\Http\Controllers;

use App\Models\MovimientoBancario;
use App\Services\BankStatementParserService;
use App\Services\ConciliacionBancariaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ConciliacionBancariaController extends Controller
{
    public function __construct(
        protected BankStatementParserService $parserService,
        protected ConciliacionBancariaService $conciliacionService
    ) {}

    /**
     * Vista principal de conciliación bancaria
     */
    public function index(Request $request)
    {
        $estado = $request->get('estado', 'pendiente');
        $tipo = $request->get('tipo');
        $banco = $request->get('banco');
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');

        $query = MovimientoBancario::with(['conciliable', 'usuario', 'conciliadoPor'])
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc');

        // Filtros
        if ($estado && $estado !== 'todos') {
            $query->where('estado', $estado);
        }
        if ($tipo) {
            $query->where('tipo', $tipo);
        }
        if ($banco) {
            $query->where('banco', $banco);
        }
        if ($fechaDesde) {
            $query->whereDate('fecha', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->whereDate('fecha', '<=', $fechaHasta);
        }

        $movimientos = $query->paginate(20)->withQueryString();

        // Resumen
        $resumen = $this->conciliacionService->getResumenPendientes();

        // Bancos disponibles
        $bancos = MovimientoBancario::select('banco')
            ->distinct()
            ->pluck('banco')
            ->toArray();

        return Inertia::render('ConciliacionBancaria/Index', [
            'movimientos' => $movimientos,
            'resumen' => $resumen,
            'filtros' => [
                'estado' => $estado,
                'tipo' => $tipo,
                'banco' => $banco,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
            ],
            'bancos' => $bancos,
            'bancos_soportados' => $this->parserService->getBancosSoportados(),
        ]);
    }

    /**
     * Importar archivo CSV o Excel de estado de cuenta
     */
    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt,xls,xlsx|max:10240', // Max 10MB
            'banco' => 'nullable|string|in:BBVA,BANORTE,SANTANDER',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
        ]);

        try {
            $archivo = $request->file('archivo');
            $nombreArchivo = $archivo->getClientOriginalName();
            $extension = strtolower($archivo->getClientOriginalExtension());
            $bancoSeleccionado = $request->get('banco');
            $cuentaBancariaId = $request->get('cuenta_bancaria_id');

            // Parsear el archivo según su extensión
            if (in_array($extension, ['xls', 'xlsx'])) {
                // Archivo Excel
                $resultado = $this->parserService->parsearExcel($archivo->path(), $bancoSeleccionado);
            } else {
                // Archivo CSV/TXT
                $contenido = file_get_contents($archivo->path());
                $resultado = $this->parserService->parsear($contenido, $bancoSeleccionado);
            }

            if (empty($resultado['movimientos'])) {
                return back()->with('error', 'No se encontraron movimientos en el archivo');
            }

            // Guardar movimientos en la base de datos
            $importados = 0;
            $duplicados = 0;

            DB::transaction(function () use ($resultado, $nombreArchivo, $cuentaBancariaId, &$importados, &$duplicados) {
                foreach ($resultado['movimientos'] as $mov) {
                    // Verificar si ya existe (evitar duplicados)
                    $existe = MovimientoBancario::where('fecha', $mov['fecha'])
                        ->where('monto', $mov['monto'])
                        ->where('concepto', $mov['concepto'])
                        ->where('banco', $mov['banco'])
                        ->exists();

                    if ($existe) {
                        $duplicados++;
                        continue;
                    }

                    MovimientoBancario::create([
                        'fecha' => $mov['fecha'],
                        'concepto' => $mov['concepto'],
                        'referencia' => $mov['referencia'],
                        'monto' => $mov['monto'],
                        'saldo' => $mov['saldo'],
                        'tipo' => $mov['tipo'],
                        'banco' => $mov['banco'],
                        'cuenta_bancaria_id' => $cuentaBancariaId,
                        'estado' => 'pendiente',
                        'archivo_origen' => $nombreArchivo,
                        'usuario_id' => Auth::id(),
                    ]);

                    $importados++;
                }
            });

            $mensaje = "Se importaron {$importados} movimientos de {$resultado['banco']}";
            if ($duplicados > 0) {
                $mensaje .= " ({$duplicados} duplicados omitidos)";
            }

            Log::info('Importación de estado de cuenta', [
                'archivo' => $nombreArchivo,
                'extension' => $extension,
                'banco' => $resultado['banco'],
                'importados' => $importados,
                'duplicados' => $duplicados,
                'usuario_id' => Auth::id(),
            ]);

            return back()->with('success', $mensaje);

        } catch (\Exception $e) {
            Log::error('Error al importar estado de cuenta', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }

    /**
     * Obtener sugerencias de conciliación para un movimiento
     */
    public function sugerencias(MovimientoBancario $movimiento)
    {
        $sugerencias = $this->conciliacionService->buscarSugerencias($movimiento);

        return response()->json([
            'movimiento' => [
                'id' => $movimiento->id,
                'fecha' => $movimiento->fecha->format('d/m/Y'),
                'concepto' => $movimiento->concepto,
                'monto' => $movimiento->monto,
                'tipo' => $movimiento->tipo,
            ],
            'sugerencias' => $sugerencias,
        ]);
    }

    /**
     * Conciliar un movimiento con una cuenta
     */
    public function conciliar(Request $request)
    {
        $request->validate([
            'movimiento_id' => 'required|exists:movimientos_bancarios,id',
            'tipo_cuenta' => 'required|in:CXC,CXP',
            'cuenta_id' => 'required|integer',
        ]);

        try {
            $movimiento = MovimientoBancario::findOrFail($request->movimiento_id);

            if ($movimiento->estado !== 'pendiente') {
                return back()->with('error', 'Este movimiento ya fue conciliado o ignorado');
            }

            $this->conciliacionService->conciliar(
                $movimiento,
                $request->tipo_cuenta,
                $request->cuenta_id
            );

            Log::info('Movimiento conciliado', [
                'movimiento_id' => $movimiento->id,
                'tipo_cuenta' => $request->tipo_cuenta,
                'cuenta_id' => $request->cuenta_id,
                'usuario_id' => Auth::id(),
            ]);

            return back()->with('success', 'Movimiento conciliado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al conciliar', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al conciliar: ' . $e->getMessage());
        }
    }

    /**
     * Conciliación automática masiva
     */
    public function conciliacionAutomatica(Request $request)
    {
        $request->validate([
            'score_minimo' => 'nullable|integer|min:50|max:100',
        ]);

        try {
            $scoreMinimo = $request->get('score_minimo', 75);
            $movimientos = MovimientoBancario::pendientes()->get();

            $resultado = $this->conciliacionService->conciliacionAutomatica($movimientos, $scoreMinimo);

            $mensaje = "Conciliación automática: {$resultado['conciliados']} conciliados, {$resultado['sin_match']} sin match";
            
            if (!empty($resultado['errores'])) {
                $mensaje .= " ({$resultado['errores'][0]})";
            }

            return back()->with('success', $mensaje);

        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Ignorar un movimiento
     */
    public function ignorar(MovimientoBancario $movimiento)
    {
        if ($movimiento->estado !== 'pendiente') {
            return back()->with('error', 'Este movimiento ya fue procesado');
        }

        $movimiento->ignorar();

        return back()->with('success', 'Movimiento marcado como ignorado');
    }

    /**
     * Revertir conciliación
     */
    public function revertir(MovimientoBancario $movimiento)
    {
        if ($movimiento->estado !== 'conciliado') {
            return back()->with('error', 'Este movimiento no está conciliado');
        }

        $movimiento->revertirConciliacion();

        Log::info('Conciliación revertida', [
            'movimiento_id' => $movimiento->id,
            'usuario_id' => Auth::id(),
        ]);

        return back()->with('success', 'Conciliación revertida');
    }

    /**
     * Eliminar movimiento
     */
    public function destroy(MovimientoBancario $movimiento)
    {
        if ($movimiento->estado === 'conciliado') {
            return back()->with('error', 'No se puede eliminar un movimiento conciliado. Primero revierta la conciliación.');
        }

        $movimiento->delete();

        return back()->with('success', 'Movimiento eliminado');
    }
}
