<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Notifications\LoginCodeNotification;

class LoginCodeController extends Controller
{
    public function index()
    {
        return Inertia::render('Auth/VerifyCode');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|numeric|digits:6'
        ]);
        
        $user = auth()->user();

        if ($request->code == $user->login_code) {
            $user->login_code = null;
            $user->login_code_expires_at = null;
            $user->login_code_verified_at = now();
            $user->save();

            // Automático: Generar un token único para este dispositivo y guardarlo por 90 días
            $deviceToken = hash_hmac('sha256', $user->id . '|' . $user->email, config('app.key'));
            \Illuminate\Support\Facades\Cookie::queue('trusted_device', $deviceToken, 60 * 24 * 90);

            return redirect()->intended(route('panel'));
        }

        return back()->withErrors(['code' => 'Código incorrecto.']);
    }

    public function resend()
    {
        $user = auth()->user();
        $code = rand(100000, 999999);
        $user->login_code = $code;
        $user->login_code_expires_at = now()->addMinutes(10);
        $user->save();
        $user->notify(new LoginCodeNotification($code));
        return back()->with('success', 'Código reenviado.');
    }
}
