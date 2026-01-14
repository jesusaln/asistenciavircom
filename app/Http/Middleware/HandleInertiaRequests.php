<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Services\EmpresaConfiguracionService;
use Illuminate\Support\Facades\Auth;

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
            $auth['client'] = array_merge($client->toArray(), [
                'tipo' => 'cliente',
                'name' => $client->nombre_razon_social,
                'activo' => (bool) $client->activo,
            ]);
        }

        $shared['auth'] = $auth;

        return array_merge(parent::share($request), $shared);
    }
}
