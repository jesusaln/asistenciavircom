<?php

namespace App\Listeners;

use App\Notifications\LoginCodeNotification;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class GenerateLoginCode
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        if (app()->environment('local') || !config('auth.login_code_required', false)) {
            return;
        }

        $user = $event->user;

        // Si el dispositivo ya es de confianza, no generamos código ni enviamos correo
        $cookie = request()->cookie('trusted_device') ?? $_COOKIE['trusted_device'] ?? null;
        $expectedToken = hash_hmac('sha256', $user->id . '|' . $user->email, config('app.key'));

        if ($cookie === $expectedToken) {
            return;
        }
        
        $code = rand(100000, 999999);
        
        $user->login_code = $code;
        $user->login_code_expires_at = now()->addMinutes(10);
        $user->login_code_verified_at = null;
        $user->save();

        try {
            $user->notify(new LoginCodeNotification($code));
            Log::info("Código de seguridad generado para: " . $user->email);
        } catch (\Exception $e) {
            Log::error("Error enviando código de seguridad: " . $e->getMessage());
        }
    }
}
