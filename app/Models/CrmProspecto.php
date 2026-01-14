<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BelongsToEmpresa;

class CrmProspecto extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'crm_prospectos';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'nombre',
        'telefono',
        'email',
        'empresa',
        // Campos fiscales (igual que Cliente)
        'tipo_persona',
        'rfc',
        'curp',
        'regimen_fiscal',
        'uso_cfdi',
        'requiere_factura',
        // Dirección
        'calle',
        'numero_exterior',
        'numero_interior',
        'codigo_postal',
        'colonia',
        'municipio',
        'estado',
        'pais',
        // CRM
        'origen',
        'etapa',
        'vendedor_id',
        'prioridad',
        'valor_estimado',
        'notas',
        'ultima_actividad_at',
        'proxima_actividad_at',
        'cerrado_at',
        'motivo_perdida',
        'created_by',
        'price_list_id',
        'domicilio_fiscal_cp',
    ];

    protected $casts = [
        'valor_estimado' => 'decimal:2',
        'ultima_actividad_at' => 'datetime',
        'proxima_actividad_at' => 'datetime',
        'cerrado_at' => 'datetime',
        'requiere_factura' => 'boolean',
    ];

    // Etapas del pipeline
    public const ETAPAS = [
        'prospecto' => 'Prospecto',
        'contactado' => 'Contactado',
        'interesado' => 'Interesado',
        'cotizado' => 'Cotizado',
        'negociacion' => 'Negociación',
        'cerrado_ganado' => 'Cerrado Ganado',
        'cerrado_perdido' => 'Cerrado Perdido',
    ];

    public const ORIGENES = [
        'referido' => 'Referido',
        'llamada_entrante' => 'Llamada Entrante',
        'web' => 'Página Web',
        'redes_sociales' => 'Redes Sociales',
        'evento' => 'Evento',
        'otro' => 'Otro',
    ];

    public const PRIORIDADES = [
        'alta' => 'Alta',
        'media' => 'Media',
        'baja' => 'Baja',
    ];

    // Relaciones
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(CrmActividad::class, 'prospecto_id')->orderBy('created_at', 'desc');
    }

    public function tareas(): HasMany
    {
        return $this->hasMany(CrmTarea::class, 'prospecto_id');
    }

    // Scopes
    public function scopeDelVendedor($query, $vendedorId)
    {
        return $query->where('vendedor_id', $vendedorId);
    }

    public function scopeEnEtapa($query, $etapa)
    {
        return $query->where('etapa', $etapa);
    }

    public function scopeActivos($query)
    {
        return $query->whereNotIn('etapa', ['cerrado_ganado', 'cerrado_perdido']);
    }

    public function scopeConActividadPendiente($query)
    {
        return $query->whereNotNull('proxima_actividad_at')
            ->where('proxima_actividad_at', '<=', now());
    }

    // Helpers
    public function getEtapaLabelAttribute(): string
    {
        return self::ETAPAS[$this->etapa] ?? $this->etapa;
    }

    public function getOrigenLabelAttribute(): string
    {
        return self::ORIGENES[$this->origen] ?? $this->origen;
    }

    public function getPrioridadLabelAttribute(): string
    {
        return self::PRIORIDADES[$this->prioridad] ?? $this->prioridad;
    }

    public function avanzarEtapa(): bool
    {
        $etapas = array_keys(self::ETAPAS);
        $currentIndex = array_search($this->etapa, $etapas);

        if ($currentIndex !== false && $currentIndex < count($etapas) - 2) {
            $this->etapa = $etapas[$currentIndex + 1];
            $this->save();
            return true;
        }
        return false;
    }

    public function cerrarGanado(): void
    {
        $this->update([
            'etapa' => 'cerrado_ganado',
            'cerrado_at' => now(),
        ]);
    }

    public function cerrarPerdido(string $motivo): void
    {
        $this->update([
            'etapa' => 'cerrado_perdido',
            'cerrado_at' => now(),
            'motivo_perdida' => $motivo,
        ]);
    }

    /**
     * Convertir prospecto a cliente
     * Crea un nuevo cliente con TODOS los datos del prospecto
     */
    public function convertirACliente(): ?Cliente
    {
        // Si ya tiene cliente asociado, devolver ese
        if ($this->cliente_id) {
            return Cliente::find($this->cliente_id);
        }

        // Crear nuevo cliente con todos los datos del prospecto
        // Usamos transacciones para mayor seguridad
        return \Illuminate\Support\Facades\DB::transaction(function () {
            // 1. Buscar si ya existe un cliente con este email o teléfono para EVITAR DUPLICADOS
            $clienteExistente = Cliente::where('empresa_id', $this->empresa_id)
                ->where(function ($query) {
                    if ($this->email) {
                        $query->where('email', $this->email);
                    }
                    if ($this->telefono) {
                        $query->orWhere('telefono', $this->telefono);
                    }
                })
                ->first();

            if ($clienteExistente) {
                // Si existe, vinculamos este prospecto al cliente existente
                $this->update(['cliente_id' => $clienteExistente->id]);

                // Opcional: Agregar nota al cliente de que volvió a cotizar
                $clienteExistente->notas .= "\n\n[Auto] Nuevo lead generado desde Simulador el " . now()->format('d/m/Y');
                $clienteExistente->save();

                return $clienteExistente;
            }

            // 2. Si NO existe, creamos uno nuevo
            $cliente = Cliente::create([
                'empresa_id' => $this->empresa_id,
                'nombre_razon_social' => $this->nombre,
                'telefono' => $this->telefono,
                'email' => $this->email,
                'notas' => "Convertido desde Prospecto CRM el " . now()->format('d/m/Y') . ". " . $this->notas,
                'activo' => true,
                // Campos fiscales
                'tipo_persona' => $this->tipo_persona,
                'rfc' => $this->rfc,
                'curp' => $this->curp,
                'regimen_fiscal' => $this->regimen_fiscal,
                'uso_cfdi' => $this->uso_cfdi ?: 'G03',
                'requiere_factura' => $this->requiere_factura ?? false,
                // Dirección
                'calle' => $this->calle,
                'numero_exterior' => $this->numero_exterior,
                'numero_interior' => $this->numero_interior,
                'codigo_postal' => $this->codigo_postal,
                'domicilio_fiscal_cp' => $this->domicilio_fiscal_cp ?: $this->codigo_postal,
                'colonia' => $this->colonia,
                'municipio' => $this->municipio,
                'estado' => $this->estado,
                'pais' => $this->pais ?: 'MX',
                // Lista de precios
                'price_list_id' => $this->price_list_id,
            ]);

            // Asociar cliente al prospecto
            $this->update(['cliente_id' => $cliente->id]);

            // Si el prospecto tenía actividades, podríamos vincularlas o dejar una traza
            // Manejar el caso de solicitudes públicas sin usuario autenticado
            try {
                $userId = \Illuminate\Support\Facades\Auth::id();

                // Si no hay usuario autenticado, buscar uno del sistema
                if (!$userId) {
                    $userId = User::role('super-admin')->value('id');
                }

                // Solo crear la actividad si tenemos un user_id válido
                if ($userId) {
                    CrmActividad::create([
                        'empresa_id' => $this->empresa_id,
                        'prospecto_id' => $this->id,
                        'user_id' => $userId,
                        'tipo' => 'nota',
                        'resultado' => 'contactado',
                        'notas' => "Prospecto convertido a Cliente ID: {$cliente->id}",
                    ]);
                }
            } catch (\Exception $e) {
                // No fallar si la actividad no se puede crear
                \Illuminate\Support\Facades\Log::warning('No se pudo registrar actividad de conversión', [
                    'prospecto_id' => $this->id,
                    'cliente_id' => $cliente->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return $cliente;
        });
    }

    /**
     * Cerrar como ganado y convertir a cliente
     */
    public function cerrarGanadoYConvertir(): ?Cliente
    {
        $this->cerrarGanado();
        return $this->convertirACliente();
    }
}
