<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CredentialRotation extends Model
{
    protected $fillable = [
        'empresa_id',
        'field_name',
        'provider',
        'rotated_at',
        'expires_at',
        'user_id',
        'metadata'
    ];

    protected $casts = [
        'rotated_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registrar una rotación de credencial
     */
    public static function record(string $field, ?string $provider = null, ?int $empresaId = null)
    {
        return self::create([
            'empresa_id' => $empresaId,
            'field_name' => $field,
            'provider' => $provider,
            'rotated_at' => now(),
            'user_id' => Auth::id(),
        ]);
    }
}
