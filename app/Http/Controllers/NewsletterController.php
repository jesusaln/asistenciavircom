<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\NewsletterTrack;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255'
        ]);

        $email = $request->input('email');
        $cliente = Cliente::where('email', $email)->first();

        if ($cliente) {
            $cliente->update([
                'recibe_newsletter' => true,
                'newsletter_unsubscribed_at' => null,
            ]);
        } else {
            Cliente::create([
                'email' => $email,
                'nombre_razon_social' => 'Suscriptor Newsletter',
                'tipo_persona' => 'fisica',
                'recibe_newsletter' => true,
                'empresa_id' => \App\Models\Empresa::first()->id ?? 1
            ]);
        }

        return redirect()->back()->with('success', '¡Gracias por suscribirte a nuestro newsletter!');
    }

    public function unsubscribe(Request $request)
    {
        $email = $request->query('email');
        $token = $request->query('token');

        if (!$email) {
            return "Error: No se proporcionó un correo electrónico.";
        }

        $cliente = Cliente::where('email', $email)->first();

        if ($cliente) {
            $cliente->update([
                'recibe_newsletter' => false,
                'newsletter_unsubscribed_at' => now(),
            ]);

            return "Te has dado de baja exitosamente de nuestro boletín informativo ($email). Sentimos verte partir.";
        }

        return "El correo electrónico no se encuentra en nuestra lista de suscripción.";
    }
}
