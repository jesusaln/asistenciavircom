<?php

namespace App\Http\Controllers;

use App\Models\Credencial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CredencialController extends Controller
{
    /**
     * Display a listing of all credentials.
     */
    public function index(Request $request)
    {
        $query = Credencial::with(['credentialable', 'creador']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->search}%")
                    ->orWhere('usuario', 'like', "%{$request->search}%")
                    ->orWhere('host', 'like', "%{$request->search}%");
            });
        }

        if ($request->cliente_id) {
            $query->where(function ($q) use ($request) {
                // Direct credentials
                $q->where(function ($sub) use ($request) {
                    $sub->where('credentialable_type', 'App\\Models\\Cliente')
                        ->where('credentialable_id', $request->cliente_id);
                })
                    // Credentials via PolizaServicio
                    ->orWhere(function ($sub) use ($request) {
                        $sub->where('credentialable_type', 'App\\Models\\PolizaServicio')
                            ->whereHasMorph('credentialable', ['App\\Models\\PolizaServicio'], function ($poliza) use ($request) {
                                $poliza->where('cliente_id', $request->cliente_id);
                            });
                    });
            });
        }

        // Default sort: Group by Type then ID (keeping same owner together), then by Date
        $query->orderBy('credentialable_type')
            ->orderBy('credentialable_id')
            ->latest();

        $credenciales = $query->paginate(15)->withQueryString();

        return Inertia::render('Credenciales/Index', [
            'credenciales' => $credenciales,
            'clientes' => \App\Models\Cliente::select('id', 'nombre_razon_social')->orderBy('nombre_razon_social')->get(),
            'polizas' => \App\Models\PolizaServicio::select('id', 'nombre', 'folio', 'cliente_id')->get(),
            'filters' => $request->only(['search', 'cliente_id'])
        ]);
    }

    /**
     * Store a newly created credential.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'usuario' => 'required|string|max:255',
            'password' => 'required|string',
            'credentialable_id' => 'required',
            'credentialable_type' => 'required|string',
        ]);

        $credencial = Credencial::create([
            'empresa_id' => auth()->user()->empresa_id,
            'credentialable_id' => $request->credentialable_id,
            'credentialable_type' => $request->credentialable_type,
            'nombre' => $request->nombre,
            'usuario' => $request->usuario,
            'password' => $request->password, // Laravel automatically encrypts this via 'encrypted' cast
            'host' => $request->host,
            'puerto' => $request->puerto,
            'notas' => $request->notas,
            'created_by' => auth()->id(),
        ]);

        $credencial->registrarAcceso('creado');

        return back()->with('success', 'Credencial guardada de forma segura.');
    }

    /**
     * Get decrypted password (logged)
     */
    public function reveal(Credencial $credencial)
    {
        // Only authorized users should reveal
        // You can add a Gate or Role check here

        $credencial->registrarAcceso('revelado');

        return response()->json([
            'password' => $credencial->password // Revealed upon access
        ]);
    }

    /**
     * Update the credential.
     */
    public function update(Request $request, Credencial $credencial)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'usuario' => 'required|string|max:255',
        ]);

        $data = $request->only(['nombre', 'usuario', 'host', 'puerto', 'notas']);

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $credencial->update($data);
        $credencial->registrarAcceso('editado');

        return back()->with('success', 'Credencial actualizada.');
    }

    /**
     * Remove the credential.
     */
    public function destroy(Credencial $credencial)
    {
        $credencial->registrarAcceso('eliminado');
        $credencial->delete();
        return back()->with('success', 'Credencial eliminada.');
    }
}
