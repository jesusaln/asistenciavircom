<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('empresa.{empresaId}.whatsapp', function ($user, $empresaId) {
    return (int) $user->empresa_id === (int) $empresaId;
});

// Canal de presencia real para usuarios admin
Broadcast::channel('admin-presence', function ($user) {
    if (!$user) return false;
    
    return [
        'id' => $user->id,
        'name' => $user->name,
        'profile_photo_url' => $user->profile_photo_url,
    ];
});
