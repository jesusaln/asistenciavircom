<?php

namespace App\Http\Controllers;

use App\Events\UserPresenceChanged;
use Illuminate\Support\Facades\Auth;

class PresenceController extends Controller
{
    public function join()
    {
        \Illuminate\Support\Facades\Log::info('User joining presence:', ['user_id' => Auth::id()]);
        broadcast(new UserPresenceChanged(Auth::user(), 'joined'));

        return response()->json(['status' => 'joined']);
    }

    public function leave()
    {
        broadcast(new UserPresenceChanged(Auth::user(), 'left'));

        return response()->json(['status' => 'left']);
    }
}
