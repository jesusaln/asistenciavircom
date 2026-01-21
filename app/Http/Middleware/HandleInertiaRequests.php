<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Services\EmpresaConfiguracionService;
use Illuminate\Support\Facades\Auth;
use App\Models\Venta;
use App\Models\CuentasPorCobrar;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return \App\Support\VersionHelper::getVersion();
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $shared = [
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'warning' => fn() => $request->session()->get('warning'),
                'status' => fn() => $request->session()->get('status'),
                'created_poliza_id' => fn() => $request->session()->get('created_poliza_id'),
                'metodo_pago' => fn() => $request->session()->get('metodo_pago'),
                'stamping_error' => fn() => $request->session()->get('stamping_error'),
            ],
            'empresa_config' => fn() => EmpresaConfiguracionService::getConfiguracion(),
            'app_version' => fn() => \App\Support\VersionHelper::getVersion(),
        ];

        // 1. Staff Auth (Guard: web)
        $user = Auth::user();
        $auth = [
            'user' => null,
            'client' => null,
        ];

        if ($user instanceof \App\Models\User) {
            $user->loadMissing(['roles', 'almacen_venta']);
            $auth['user'] = array_merge($user->toArray(), [
                'tipo' => 'empleado',
                'is_admin' => $user->hasRole('admin') || $user->hasRole('super-admin'),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'roles' => $user->roles,
            ]);
        }

        // 2. Client Auth (Guard: client)
        $client = Auth::guard('client')->user();
        if ($client instanceof \App\Models\Cliente) {
            $blocked = $this->checkBlockingDebt($client) || session('blocked');
            \Illuminate\Support\Facades\Log::info('HandleInertiaRequests: Sharing Props (Checked)', [
                'client_id' => $client->id,
                'final_blocked' => $blocked
            ]);

            $auth['client'] = array_merge($client->toArray(), [
                'tipo' => 'cliente',
                'name' => $client->nombre_razon_social,
                'portal_blocked' => $blocked,
                'activo' => (bool) $client->activo,
            ]);
        }

        $shared['auth'] = $auth;

        return array_merge(parent::share($request), $shared);
    }
    /**
     * Verificar si el cliente tiene deudas bloqueantes (Lógica duplicada para Inertia Shared Props debido al orden de middleware)
     */
    protected function checkBlockingDebt($cliente): bool
    {
        $config = \App\Models\EmpresaConfiguracion::getConfig($cliente->empresa_id);
        $diasGracia = $cliente->dias_gracia ?? $config->dias_gracia_corte ?? 15;
        $corteLimitDate = now()->subDays($diasGracia);

        // Contar ventas vencidas
        $countVentas = Venta::where('cliente_id', $cliente->id)
            ->whereIn('estado', ['pendiente', 'vencida'])
            ->where('pagado', false)
            ->where('fecha', '<', $corteLimitDate)
            ->count();

        // Contar CxC vencidas
        $countCxC = CuentasPorCobrar::where('cliente_id', $cliente->id)
            ->whereIn('estado', ['pendiente', 'vencido'])
            ->where('monto_pendiente', '>', 0)
            ->where('fecha_vencimiento', '<', $corteLimitDate)
            ->count();

        $totalVencidos = $countVentas + $countCxC;

        // REGLA DE NEGOCIO (20-01-2026):
        // Solo bloquear si hay 2 o más documentos vencidos (ej. 2 meses de renta).
        // Se permite 1 mes de atraso sin bloquear el portal.
        return $totalVencidos >= 2;
    }
}
