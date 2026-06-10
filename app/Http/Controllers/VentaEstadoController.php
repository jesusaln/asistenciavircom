<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Services\Ventas\VentaCancellationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VentaEstadoController extends Controller
{
    public function __construct(
        private readonly VentaCancellationService $ventaCancellationService
    ) {
    }

    /**
     * Cancel sale.
     */
    public function cancel(Request $request, $id)
    {
        try {
            $venta = Venta::findOrFail($id);

            $forceWithPayments = (bool) $request->input('force_with_payments', false);

            /** @var \App\Models\User $user */
            $user = Auth::user();
            if ($forceWithPayments && !$user->hasAnyRole(['admin', 'super-admin'])) {
                throw new \Exception('Solo administradores pueden cancelar ventas con pagos registrados');
            }

            $this->ventaCancellationService->cancelVenta(
                $venta,
                $request->input('motivo'),
                $forceWithPayments
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Venta cancelada exitosamente'
                ]);
            }

            return redirect()->back()->with('success', 'Venta cancelada exitosamente');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
