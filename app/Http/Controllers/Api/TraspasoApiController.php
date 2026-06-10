<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Traspaso;
use App\Models\TraspasoItem;
use App\Services\InventarioService;
use App\Http\Controllers\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TraspasoApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly InventarioService $inventarioService,
        private readonly \App\Services\PdfGeneratorService $pdfService
    ) {
    }

    /**
     * Realizar un traspaso de inventario
     */
    public function store(Request $request)
    {
        $request->validate([
            'almacen_origen_id' => 'required|exists:almacenes,id',
            'almacen_destino_id' => 'required|exists:almacenes,id|different:almacen_origen_id',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:productos,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.series_ids' => 'nullable|array',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $almacenOrigenId = $request->almacen_origen_id;
                $almacenDestinoId = $request->almacen_destino_id;
                $items = $request->items;

                $almacenOrigen = Almacen::findOrFail($almacenOrigenId);
                $almacenDestino = Almacen::findOrFail($almacenDestinoId);

                $traspaso = Traspaso::create([
                    'empresa_id' => $request->user()->empresa_id,
                    'almacen_origen_id' => $almacenOrigenId,
                    'almacen_destino_id' => $almacenDestinoId,
                    'estado' => 'completado',
                    'usuario_autoriza' => $request->user()->id,
                    'usuario_envia' => $request->user()->id,
                    'fecha_envio' => now(),
                    'fecha_recepcion' => now(),
                    'observaciones' => $request->observaciones,
                ]);

                foreach ($items as $itemData) {
                    $productoId = $itemData['producto_id'];
                    $producto = Producto::findOrFail($productoId);
                    $cantidad = (int) $itemData['cantidad'];
                    $seriesIds = $itemData['series_ids'] ?? [];

                    // Validar si requiere series y si se proporcionaron
                    $requiereSerie = (bool) ($producto->requiere_serie || $producto->maneja_series);
                    if ($requiereSerie && count($seriesIds) !== $cantidad) {
                        throw new \Exception("Debe seleccionar exactamente {$cantidad} números de serie para {$producto->nombre}.");
                    }

                    // Verificar stock
                    $inventarioOrigen = Inventario::where('producto_id', $productoId)
                        ->where('almacen_id', $almacenOrigenId)
                        ->first();

                    if (!$inventarioOrigen || $inventarioOrigen->cantidad < $cantidad) {
                        throw new \Exception("Stock insuficiente de {$producto->nombre} en origen.");
                    }

                    // Crear item
                    TraspasoItem::create([
                        'empresa_id' => $request->user()->empresa_id,
                        'traspaso_id' => $traspaso->id,
                        'producto_id' => $productoId,
                        'cantidad' => $cantidad,
                        'series_ids' => $seriesIds,
                    ]);

                    // Actualizar almacén de las series
                    if (!empty($seriesIds)) {
                        \App\Models\ProductoSerie::whereIn('id', $seriesIds)
                            ->update(['almacen_id' => $almacenDestinoId]);
                    }

                    // Mover inventario
                    $this->inventarioService->salida($producto, $cantidad, [
                        'almacen_id' => $almacenOrigenId,
                        'motivo' => 'Traspaso a ' . $almacenDestino->nombre,
                        'referencia' => $traspaso,
                        'skip_transaction' => true,
                    ]);

                    $this->inventarioService->entrada($producto, $cantidad, [
                        'almacen_id' => $almacenDestinoId,
                        'motivo' => 'Traspaso desde ' . $almacenOrigen->nombre,
                        'referencia' => $traspaso,
                        'skip_transaction' => true,
                    ]);
                }

                // Cargar relaciones para el PDF/Respuesta
                $traspaso->load(['almacenOrigen', 'almacenDestino', 'items.producto', 'usuarioEnvia']);

                return $this->success([
                    'traspaso' => $traspaso,
                    'pdf_url' => route('api.traspasos.pdf', ['traspaso' => $traspaso->id])
                ], 'Traspaso realizado con éxito');
            });
        } catch (\Exception $e) {
            Log::error('Error en Traspaso API: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Generar PDF del traspaso
     */
    public function downloadPdf(Traspaso $traspaso)
    {
        $traspaso->load(['almacenOrigen', 'almacenDestino', 'items.producto', 'usuarioEnvia', 'usuarioRecibe']);
        
        $pdf = $this->pdfService->loadView('pdf.traspaso', [
            'traspaso' => $traspaso
        ]);

        return $this->pdfService->stream($pdf, "traspaso-{$traspaso->folio}.pdf");
    }
}
