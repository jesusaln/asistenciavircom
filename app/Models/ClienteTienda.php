<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;

class ClienteTienda extends Model implements Authenticatable
{
    use AuthenticatableTrait, BelongsToEmpresa;

    protected $table = 'clientes_tienda';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'email',
        'avatar',
        'provider',
        'provider_id',
        'telefono',
        'direccion_predeterminada',
        'email_verified_at',
    ];

    protected $casts = [
        'direccion_predeterminada' => 'array',
        'email_verified_at' => 'datetime',
    ];

    protected $hidden = [
        'remember_token',
    ];

    /**
     * Pedidos del cliente
     */
    public function pedidos(): HasMany
    {
        return $this->hasMany(PedidoOnline::class, 'cliente_tienda_id');
    }

    /**
     * Buscar o crear cliente por proveedor OAuth
     */
    public static function findOrCreateFromProvider(string $provider, object $socialUser): self
    {
        $cliente = self::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($cliente) {
            // Actualizar avatar si cambió
            if ($socialUser->getAvatar() && $cliente->avatar !== $socialUser->getAvatar()) {
                $cliente->update(['avatar' => $socialUser->getAvatar()]);
            }
            return $cliente;
        }

        // Verificar si ya existe un cliente con ese email
        $existingByEmail = self::where('email', $socialUser->getEmail())->first();
        if ($existingByEmail) {
            // Vincular cuenta social a cliente existente
            $existingByEmail->update([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
            ]);
            return $existingByEmail;
        }

        // Crear nuevo cliente
        return self::create([
            'nombre' => $socialUser->getName(),
            'email' => $socialUser->getEmail(),
            'avatar' => $socialUser->getAvatar(),
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Obtener iniciales del nombre
     */
    public function getInicialesAttribute(): string
    {
        $words = explode(' ', $this->nombre);
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        return $initials;
    }
}
