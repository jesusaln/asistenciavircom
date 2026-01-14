<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ClienteTienda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Inertia\Inertia;

class SocialAuthController extends Controller
{
    /**
     * Página de login para clientes de la tienda
     */
    public function showLogin()
    {
        // Si ya está autenticado como cliente, redirigir
        if (session()->has('cliente_tienda_id')) {
            return redirect()->route('catalogo.index');
        }

        return Inertia::render('Catalogo/Login', [
            'canLogin' => true,
        ]);
    }

    /**
     * Redirect a Google
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->scopes(['email', 'profile'])
            ->redirect();
    }

    /**
     * Callback de Google
     */
    public function handleGoogleCallback()
    {
        try {
            $socialUser = Socialite::driver('google')->user();

            $cliente = ClienteTienda::findOrCreateFromProvider('google', $socialUser);

            // Guardar en sesión
            session(['cliente_tienda_id' => $cliente->id]);
            session([
                'cliente_tienda' => [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre,
                    'email' => $cliente->email,
                    'avatar' => $cliente->avatar,
                ]
            ]);

            return redirect()->route('catalogo.index')
                ->with('success', '¡Bienvenido, ' . $cliente->nombre . '!');

        } catch (\Exception $e) {
            return redirect()->route('tienda.login')
                ->with('error', 'Error al iniciar sesión con Google: ' . $e->getMessage());
        }
    }

    /**
     * Redirect a Microsoft
     */
    public function redirectToMicrosoft()
    {
        return Socialite::driver('microsoft')
            ->scopes(['User.Read'])
            ->redirect();
    }

    /**
     * Callback de Microsoft
     */
    public function handleMicrosoftCallback()
    {
        try {
            $socialUser = Socialite::driver('microsoft')->user();

            // CASO 1: Usuario Admin/Técnico ya autenticado -> Vincular cuenta
            if (Auth::check()) {
                $user = Auth::user();
                $user->microsoft_token = $socialUser->token;
                $user->microsoft_refresh_token = $socialUser->refreshToken;
                $user->microsoft_token_expires_at = now()->addSeconds($socialUser->expiresIn);
                $user->save();

                return redirect()->route('perfil')
                    ->with('success', 'Cuenta de Microsoft conectada exitosamente.');
            }

            // CASO 2: Login de Cliente Tienda
            $cliente = ClienteTienda::findOrCreateFromProvider('microsoft', $socialUser);

            // Guardar en sesión
            session(['cliente_tienda_id' => $cliente->id]);
            session([
                'cliente_tienda' => [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre,
                    'email' => $cliente->email,
                    'avatar' => $cliente->avatar,
                ]
            ]);

            return redirect()->route('catalogo.index')
                ->with('success', '¡Bienvenido, ' . $cliente->nombre . '!');

        } catch (\Exception $e) {
            // Si estaba intentando vincular (Auth check), redirigir al perfil con error
            if (Auth::check()) {
                return redirect()->route('perfil')
                    ->with('error', 'Error al conectar con Microsoft: ' . $e->getMessage());
            }

            return redirect()->route('tienda.login')
                ->with('error', 'Error al iniciar sesión con Microsoft: ' . $e->getMessage());
        }
    }

    /**
     * Cerrar sesión del cliente
     */
    public function logout(Request $request)
    {
        session()->forget(['cliente_tienda_id', 'cliente_tienda']);

        return redirect()->route('catalogo.index')
            ->with('success', 'Has cerrado sesión correctamente.');
    }

    /**
     * Obtener cliente actual (para API)
     */
    public function me()
    {
        $clienteId = session('cliente_tienda_id');

        if (!$clienteId) {
            return response()->json(['cliente' => null]);
        }

        $cliente = ClienteTienda::find($clienteId);

        return response()->json([
            'cliente' => $cliente ? [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'email' => $cliente->email,
                'avatar' => $cliente->avatar,
                'telefono' => $cliente->telefono,
                'iniciales' => $cliente->iniciales,
            ] : null,
        ]);
    }

    /**
     * Página Mi Cuenta del cliente
     */
    public function miCuenta()
    {
        $clienteId = session('cliente_tienda_id');

        if (!$clienteId) {
            return redirect()->route('tienda.login');
        }

        $cliente = ClienteTienda::with('pedidos')->find($clienteId);

        if (!$cliente) {
            session()->forget(['cliente_tienda_id', 'cliente_tienda']);
            return redirect()->route('tienda.login');
        }

        return Inertia::render('Catalogo/MiCuenta', [
            'cliente' => [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'email' => $cliente->email,
                'avatar' => $cliente->avatar,
                'telefono' => $cliente->telefono,
                'direccion_predeterminada' => $cliente->direccion_predeterminada,
                'iniciales' => $cliente->iniciales,
            ],
            'pedidos' => $cliente->pedidos()
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'numero_pedido' => $p->numero_pedido,
                    'total' => $p->total,
                    'estado' => $p->estado,
                    'estado_label' => $p->estado_label,
                    'estado_color' => $p->estado_color,
                    'items_count' => count($p->items ?? []),
                    'created_at' => $p->created_at->format('d/m/Y H:i'),
                ]),
            'canLogin' => true,
        ]);
    }
}
