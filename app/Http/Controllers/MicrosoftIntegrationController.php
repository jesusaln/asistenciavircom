<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Exception;

class MicrosoftIntegrationController extends Controller
{
    public function connect()
    {
        return Socialite::driver('microsoft')
            ->scopes(['User.Read', 'Tasks.ReadWrite', 'Calendars.ReadWrite'])
            ->with(['prompt' => 'select_account']) // Force account selection
            ->redirect();
    }

    // Callback se maneja en SocialAuthController::handleMicrosoftCallback


    public function disconnect()
    {
        $user = Auth::user();
        $user->microsoft_token = null;
        $user->microsoft_refresh_token = null;
        $user->microsoft_token_expires_at = null;
        $user->save();

        return redirect()->route('profile.show')
            ->with('success', 'Cuenta de Microsoft desconectada.');
    }
}
