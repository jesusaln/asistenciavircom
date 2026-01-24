<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function unsubscribe(Request $request)
    {
        $email = $request->query('email');

        if (!$email) {
            return "Error: No se proporcionó un correo electrónico.";
        }

        $cliente = Cliente::where('email', $email)->first();

        if ($cliente) {
            $cliente->update([
                'recibe_newsletter' => false,
                'newsletter_unsubscribed_at' => now(),
            ]);

            return view('newsletter.unsubscribed_success', ['email' => $email]);
        }

        return "El correo electrónico no se encuentra en nuestra lista de suscripción.";
    }
}
