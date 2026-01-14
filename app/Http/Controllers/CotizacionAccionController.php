<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCotizacion;
use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class CotizacionAccionController extends Controller
{
    /**
     * Duplicar una cotización.
     */
    public function duplicate(Request $request, $id)
    {
        $original = Cotizacion::with('cliente', 'items.cotizable')->findOrFail($id);

        try {
            return DB::transaction(function () use ($original) {
                $nueva = $original->replicate([
                    'numero_cotizacion',
                    'created_at',
                    'updated_at',
                    'estado',
                ]);

                $nueva->estado = EstadoCotizacion::Borrador;
                $nueva->numero_cotizacion = $this->generarNumeroCotizacionUnico();
                $nueva->created_at = now();
                $nueva->updated_at = now();

                $nueva->save();

                foreach ($original->items as $item) {
                    $nueva->items()->create([
                        'cotizable_id' => $item->cotizable_id,
                        'cotizable_type' => $item->cotizable_type,
                        'cantidad' => $item->cantidad,
                        'precio' => $item->precio,
                        'descuento' => $item->descuento,
                        'subtotal' => $item->subtotal,
                        'descuento_monto' => $item->descuento_monto,
                        'price_list_id' => $item->price_list_id,
                    ]);
                }

                return Redirect::route('cotizaciones.index')
                    ->with('success', 'Cotización duplicada correctamente.');
            });
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error duplicando cotización: ' . $e->getMessage(), ['id' => $id]);
            return Redirect::back()->with('error', 'Error al duplicar la cotización.');
        }
    }

    /**
     * Genera un numero_cotizacion único secuencial evitando colisiones.
     */
    private function generarNumeroCotizacionUnico(): string
    {
        return Cotizacion::generarNumero();
    }
}
