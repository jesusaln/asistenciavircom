<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\EmpresaConfiguracion;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use App\Models\Cliente;
use App\Models\CrmProspecto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewClientRegisteredNotification;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    private function getEmpresaBranding()
    {
        $empresaId = EmpresaResolver::resolveId();
        $empresaModel = Empresa::find($empresaId);
        $configuracion = EmpresaConfiguracion::getConfig($empresaId);

        return $empresaModel ? array_merge($empresaModel->toArray(), [
            'color_principal' => $configuracion->color_principal,
            'color_secundario' => $configuracion->color_secundario,
            'color_terciario' => $configuracion->color_terciario,
            'logo_url' => $configuracion->logo_url,
            'favicon_url' => $configuracion->favicon_url,
            'nombre_comercial_config' => $configuracion->nombre_empresa,
        ]) : null;
    }

    public function create()
    {
        return Inertia::render('Portal/Auth/Login', [
            'empresa' => $this->getEmpresaBranding(),
        ]);
    }

    public function register()
    {
        return Inertia::render('Portal/Auth/Register', [
            'empresa' => $this->getEmpresaBranding(),
        ]);
    }

    public function storeRegister(Request $request)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'], // Remove 'unique:clientes' to handle manual check
            'telefono' => ['required', 'string', 'digits:10'], // Debe ser 10 dígitos exactos
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()->symbols(), 'confirmed'],
        ]);

        // Verificar unicidad manualmente considerando SoftDeletes
        $existingClient = Cliente::withTrashed()->where('email', $request->email)->first();
        if ($existingClient && !$existingClient->trashed()) {
            return back()->withErrors(['email' => 'El correo electrónico ya está registrado.']);
        }

        $empresaId = EmpresaResolver::resolveId();

        // Forzar mayúsculas en el nombre
        $nombreMayusculas = mb_strtoupper($request->nombre, 'UTF-8');

        DB::beginTransaction();
        try {
            if ($existingClient && $existingClient->trashed()) {
                // Restaurar cliente eliminado logicamente
                $existingClient->restore();
                $existingClient->update([
                    'nombre_razon_social' => $nombreMayusculas,
                    'telefono' => $request->telefono,
                    'password' => $request->password, // Se hashea autm. en modelo
                    'activo' => false,
                ]);
                $cliente = $existingClient;
                $action = 'restored';
            } else {
                // Crear nuevo cliente
                $cliente = Cliente::create([
                    'nombre_razon_social' => $nombreMayusculas,
                    'email' => $request->email,
                    'telefono' => $request->telefono,
                    'password' => $request->password,
                    'empresa_id' => $empresaId,
                    'activo' => false, // Requiere aprobación para el portal
                ]);
                $action = 'created';
            }

            // Crear Prospecto en CRM automáticamente
            // Crear Prospecto en CRM automáticamente (si no tiene uno abierto)
            // Si se restauró, verificamos si ya tenía prospecto activo para no duplicar
            $prospectoExistente = CrmProspecto::where('cliente_id', $cliente->id)->first();

            if (!$prospectoExistente || $action === 'created') {
                CrmProspecto::create([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $cliente->id, // Vinculamos de una vez
                    'nombre' => $nombreMayusculas,
                    'email' => $request->email,
                    'telefono' => $request->telefono,
                    'origen' => 'web', // Origen: Registro Web
                    'etapa' => 'prospecto',
                    'prioridad' => 'media',
                    'notas' => ($action === 'restored' ? 'Cliente reactivado desde portal.' : 'Registro automático desde el Portal de Clientes.'),
                    'created_by' => null, // Creado por sistema
                ]);
            }

            DB::commit();

            // Notificar al Staff (Admins y Ventas)
            // Buscar usuarios con permiso de ver clientes o roles específicos
            $staffToNotify = User::permission('view clientes')->get();
            if ($staffToNotify->count() > 0) {
                Notification::send($staffToNotify, new NewClientRegisteredNotification($cliente));
            }

            Auth::guard('client')->login($cliente);

            // Redirigir a la tienda para "comprar rápido"
            return redirect()->route('catalogo.index')->with('success', '¡Bienvenido! Ya puedes realizar tus compras. Tu acceso al portal de soporte está en revisión.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error registrando cliente/prospecto: ' . $e->getMessage());
            return back()->withErrors(['email' => 'Ocurrió un error al registrar su cuenta. Por favor intente nuevamente.']);
        }
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('client')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('portal.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ]);
    }

    public function destroy(Request $request)
    {
        Auth::guard('client')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
