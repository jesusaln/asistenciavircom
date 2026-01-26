<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\NewsletterTrack;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
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
