<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClienteHubController extends Controller
{
    /**
     * Muestra el hub centralizado de información para un cliente específico.
     *
     * @param  \App\Models\Cliente  $cliente
     * @return \Inertia\Response
     */
    public function show(Cliente $cliente)
    {
        // Cargar las relaciones fundamentales del cliente de manera eficiente
        $cliente->load([
            // Pólizas: Solo la activa y sus consumos del mes.
            'polizas' => function ($query) {
                $query->activa()->with([
                    'consumosMesActual',
                    'mantenimientos' => fn($q) => $q->proximos()
                ]);
            },
            // Tickets: Los 15 más recientes con sus relaciones clave.
            'tickets' => function ($query) {
                $query->latest()->take(15)->with(['asignado:id,name', 'categoria:id,nombre']);
            },
            // Citas: Las 10 próximas o más recientes con su técnico asignado.
            'citas' => function ($query) {
                $query->orderBy('fecha_hora', 'desc')->take(10)->with('tecnico:id,name');
            },
            // Ventas: Las últimas 5 ventas para un historial rápido.
            'ventas' => function ($query) {
                $query->latest()->take(5);
            }
        ]);

        // Adicionalmente, podríamos querer cargar el saldo pendiente si es relevante
        // Nota: El accessor 'saldo_pendiente' puede causar N+1 si no se maneja con cuidado.
        // Es mejor calcularlo aquí si es necesario para el Hub.
        $saldoPendiente = $cliente->calcularSaldoPendiente();

        return Inertia::render('Clientes/Hub', [
            'cliente' => $cliente,
            'saldo_pendiente' => $saldoPendiente,
            'poliza_activa' => $cliente->polizas->first(), // La consulta ya filtró por activa
        ]);
    }
}
