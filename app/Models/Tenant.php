<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Tenant extends Model
{
    protected $connection = 'system';

    protected $fillable = [
        'slug',
        'dominio',
        'nombre',
        'db_database',
        'db_host',
        'db_port',
        'db_username',
        'db_password',
        'plan_id',
        'estado_suscripcion',
        'trial_ends_at',
        'subscribed_at',
        'expires_at',
        'cancelled_at',
        'stripe_id',
        'stripe_subscription_id',
        'monto_mensual',
        'monto_anual',
        'ciclo_facturacion',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscribed_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'monto_mensual' => 'decimal:2',
        'monto_anual' => 'decimal:2',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function centralUsers()
    {
        return $this->belongsToMany(CentralUser::class, 'central_user_tenants');
    }

    public function switchToThis(): void
    {
        \Illuminate\Support\Facades\Config::set('database.connections.pgsql.host', $this->db_host);
        \Illuminate\Support\Facades\Config::set('database.connections.pgsql.port', $this->db_port);
        \Illuminate\Support\Facades\Config::set('database.connections.pgsql.database', $this->db_database);
        \Illuminate\Support\Facades\Config::set('database.connections.pgsql.username', $this->db_username);
        \Illuminate\Support\Facades\Config::set('database.connections.pgsql.password', $this->db_password);

        \Illuminate\Support\Facades\DB::purge('pgsql');
        \Illuminate\Support\Facades\DB::reconnect('pgsql');

        \App\Support\EmpresaResolver::clearCache();
        Cache::forget("empresa_domain_v2_" . request()->getHost());
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('estado_suscripcion', 'active')
              ->orWhere('estado_suscripcion', 'trial');
        })->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->where('estado_suscripcion', 'expired')
            ->orWhere(function ($q) {
                $q->whereNotNull('expires_at')
                  ->where('expires_at', '<', now());
            });
    }

    /**
     * Resuelve un tenant por dominio (hostname).
     * Usa cache para evitar golpear la BD en cada request.
     */
    public static function resolveFromDomain(string $domain): ?self
    {
        try {
            $id = \Illuminate\Support\Facades\Cache::remember(
                "tenant_domain_id_{$domain}",
                now()->addHour(),
                function () use ($domain) {
                    $tenant = static::where('dominio', $domain)->first(['id']);
                    return $tenant?->id;
                }
            );

            if (!$id) {
                return null;
            }

            return static::find($id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::debug('Tenant::resolveFromDomain failed: ' . $e->getMessage());
            return null;
        }
    }

    public static function clearDomainCache(string $domain): void
    {
        \Illuminate\Support\Facades\Cache::forget("tenant_domain_id_{$domain}");
    }
}
