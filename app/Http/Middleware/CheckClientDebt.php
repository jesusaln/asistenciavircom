<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Venta;
use App\Models\CuentasPorCobrar;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckClientDebt
{
    /**
     * Rutas permitidas cuando el cliente tiene deudas vencidas
     */
    protected $allowedRoutes = [
        'portal.dashboard', // Solo para mostrar pagos
        'portal.ventas.pdf',
        'portal.ventas.pagar-credito',
        'portal.pagos.mercadopago.crear',
        'portal.logout',
        'portal.perfil.update',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $cliente = Auth::guard('client')->user();

        if (!$cliente) {
            return $next($request);
        }

        Log::info('CheckClientDebt: Verificando usuario', ['id' => $cliente->id]);

        // Verificar si tiene deudas vencidas más allá del período de gracia
        $hasBlockingDebt = $this->hasBlockingDebt($cliente);

        Log::info('CheckClientDebt: Resultado bloqueo', ['bloqueado' => $hasBlockingDebt]);

        if ($hasBlockingDebt) {
            // Verificar si la ruta actual está permitida
            $currentRoute = $request->route()?->getName();

            if (!in_array($currentRoute, $this->allowedRoutes)) {
                Log::info('CheckClientDebt: Redirigiendo a dashboard (bloqueado)', ['from_route' => $currentRoute]);
                // Redirigir al dashboard con mensaje de bloqueo
                return redirect()
                    ->route('portal.dashboard')
                    ->with('blocked', true)
                    ->with('blocked_message', 'Su acceso al portal está temporalmente restringido debido a pagos vencidos. Por favor, regularice su situación para recuperar el acceso completo.');
            }
        }

        // Pasar la información de bloqueo a la vista
        $request->merge(['portal_blocked' => $hasBlockingDebt]);
        $request->attributes->set('portal_blocked', $hasBlockingDebt);

        return $next($request);
    }


    /**
     * Verificar si el cliente tiene deudas que bloquean el portal
     */
    protected function hasBlockingDebt($cliente): bool
    {
        $config = \App\Models\EmpresaConfiguracion::getConfig($cliente->empresa_id);

        // Prioridad: Cliente > Empresa > Default (15)
        $diasGracia = $cliente->dias_gracia ?? $config->dias_gracia_corte ?? 15;

        $corteLimitDate = now()->subDays($diasGracia);

        Log::info('CheckClientDebt: Debug Deuda', [
            'cliente' => $cliente->id,
            'dias_gracia' => $diasGracia,
            'fecha_limite' => $corteLimitDate->toDateTimeString(),
            'hoy' => now()->toDateTimeString()
        ]);

        // Verificar ventas pendientes antiguas (sin registro en CxC o contado pendiente)
        $queryVentas = Venta::where('cliente_id', $cliente->id)
            ->whereIn('estado', ['pendiente', 'vencida'])
            ->where('pagado', false)
            ->where('fecha', '<', $corteLimitDate);

        $hasOverdueVentas = $queryVentas->exists();

        if ($hasOverdueVentas) {
            Log::info('CheckClientDebt: Bloqueado por Ventas', ['count' => $queryVentas->count()]);
            return true;
        }

        // Verificar CxC vencidas
        $hasOverdueCxC = CuentasPorCobrar::where('cliente_id', $cliente->id)
            ->whereIn('estado', ['pendiente', 'vencido'])
            ->where('monto_pendiente', '>', 0)
            ->where('fecha_vencimiento', '<', $corteLimitDate)
            ->exists();

        Log::info('CheckClientDebt: Bloqueado por CxC?', ['result' => $hasOverdueCxC]);

        return $hasOverdueCxC;
    }
}
