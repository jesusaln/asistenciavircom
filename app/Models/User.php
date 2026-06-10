<?php

namespace App\Models;
use App\Models\Concerns\BelongsToEmpresa;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Storage;
use App\Helpers\UrlHelper;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;


use \OwenIt\Auditing\Auditable;

class User extends Authenticatable implements \OwenIt\Auditing\Contracts\Auditable
{
    use BelongsToEmpresa;

    use HasApiTokens, Notifiable, HasRoles, HasFactory, HasProfilePhoto, HasTeams, TwoFactorAuthenticatable, Auditable, SoftDeletes {
        HasTeams::teams insteadof HasRoles;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'telefono',
        'fecha_nacimiento',
        'curp',
        'rfc',
        'direccion',
        'nss',
        'puesto',
        'departamento',
        'fecha_contratacion',
        'tipo_contrato',
        'numero_empleado',
        'contacto_emergencia_nombre',
        'contacto_emergencia_telefono',
        'contacto_emergencia_parentesco',
        'banco',
        'numero_cuenta',
        'clabe_interbancaria',
        'observaciones',
        'es_empleado',
        'activo',
        'almacen_venta_id',
        'almacen_compra_id',
        // Campos unificados de Técnico/Vendedor
        'es_tecnico',
        'es_vendedor',
        'comision_instalacion',
        // Campos unificados de Empleado (RRHH)
        'tipo_jornada',
        'horas_jornada',
        'hora_entrada',
        'hora_salida',
        'trabaja_sabado',
        'hora_entrada_sabado',
        'hora_salida_sabado',
        'frecuencia_pago',
        'contrato_adjunto',
        'carro_id',
        // Campos técnicos de Nómina (Timbrado)
        'tipo_regimen',
        'riesgo_puesto',
        'salario_diario_integrado',
        'salario_base_cotizacion',
        'clave_ent_fed',
        'registro_patronal',
        'sindicalizado',
        'empresas_acceso',
        'latitud',
        'longitud',
        'ultima_fecha_gps',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'dias_vacaciones_disponibles',
        'domicilio_actual',
        'cita_actual',
    ];




    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // Serializar como YYYY-MM-DD para inputs HTML date
            'fecha_nacimiento' => 'date:Y-m-d',
            'fecha_contratacion' => 'date:Y-m-d',
            'salario' => 'decimal:2',
            'salario_base' => 'decimal:2',
            'es_empleado' => 'boolean',
            'activo' => 'boolean',
            // Campos unificados
            'es_tecnico' => 'boolean',
            'es_vendedor' => 'boolean',
            'margen_venta_productos' => 'decimal:2',
            'margen_venta_servicios' => 'decimal:2',
            'comision_instalacion' => 'decimal:2',
            'trabaja_sabado' => 'boolean',
            'horas_jornada' => 'integer',
            // Campos técnicos de Nómina
            'salario_diario_integrado' => 'decimal:4',
            'salario_base_cotizacion' => 'decimal:4',
            'sindicalizado' => 'boolean',
            'login_code_expires_at' => 'datetime',
            'login_code_verified_at' => 'datetime',
            'latitud' => 'decimal:8',
            'longitud' => 'decimal:8',
            'ultima_fecha_gps' => 'datetime',
        ];
    }

    /**
     * Enviar la notificación de restablecimiento de contraseña.
     *
     * @param string $token
     * @return void
     */

    /**
     * Get the default profile photo URL if no profile photo has been uploaded.
     *
     * @return string
     */
    protected function defaultProfilePhotoUrl()
    {
        return '/images/default-profile.svg';
    }

    /**
     * Get the URL to the user's profile photo.
     *
     * @return string
     */
    public function getProfilePhotoUrlAttribute()
    {
        if (!$this->profile_photo_path) {
            return $this->defaultProfilePhotoUrl();
        }

        $path = $this->profile_photo_path;

        // Datos antiguos: ruta guardada como URL absoluta (p. ej. dev nip.io) → usar solo el path
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $parsedPath = parse_url($path, PHP_URL_PATH);
            if (is_string($parsedPath) && str_contains($parsedPath, '/storage/')) {
                return $parsedPath;
            }
        }

        $disk = $this->profilePhotoDisk();
        // Disco local public: siempre URL relativa al dominio actual (evita mixed content y APP_URL erróneo)
        if ($disk === 'public') {
            return UrlHelper::storageUrl($path);
        }

        return Storage::disk($disk)->url($path);
    }

    /**
     * Get the disk that profile photos should be stored on.
     *
     * @return string
     */
    protected function profilePhotoDisk()
    {
        return isset($_ENV['VAPOR_ARTIFACT_NAME']) ? 's3' : config('jetstream.profile_photo_disk', 'public');
    }

    /**
     * Generar URL relativa para assets
     */
    private function generateCorrectAssetUrl($path)
    {
        return "/" . ltrim($path, '/');
    }

    /**
     * Generar URL relativa para storage
     */
    private function generateCorrectStorageUrl($path)
    {
        return Storage::url($path);
    }

    // Citas asignadas al usuario como técnico
    public function citasAsignadas()
    {
        return $this->hasMany(Cita::class, 'tecnico_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    // Nóminas del empleado (Relación directa ahora)
    public function nominas()
    {
        return $this->hasMany(Nomina::class, 'empleado_id');
    }

    // Ventas realizadas por este usuario
    public function ventas()
    {
        return $this->morphMany(Venta::class, 'vendedor');
    }

    // Vacaciones del empleado
    public function vacaciones()
    {
        return $this->hasMany(Vacacion::class);
    }

    // Vacaciones aprobadas del empleado
    public function vacacionesAprobadas()
    {
        return $this->hasMany(Vacacion::class)->where('estado', 'aprobada');
    }

    // Registro anual de vacaciones
    public function registroVacaciones()
    {
        return $this->hasMany(RegistroVacaciones::class);
    }

    // Registro de vacaciones del año actual
    public function registroVacacionesActual()
    {
        return $this->hasOne(RegistroVacaciones::class)->where('anio', now()->year);
    }

    // Contratos y adendas legales
    public function contratos()
    {
        return $this->hasMany(Contrato::class);
    }

    // Ganancia total de todas las ventas realizadas por este usuario
    public function getGananciaTotalAttribute()
    {
        return $this->ventas->sum('ganancia_total');
    }

    // Métodos para empleados
    public function getNombreCompletoAttribute()
    {
        return $this->name;
    }

    public function getEdadAttribute()
    {
        if (!$this->fecha_nacimiento) {
            return null;
        }
        return (int) now()->diffInYears($this->fecha_nacimiento);
    }

    public function getAntiguedadAttribute()
    {
        if (!$this->fecha_contratacion) {
            return 0;
        }
        // Asegurar que sea un entero positivo (años cumplidos)
        return (int) abs(now()->diffInYears($this->fecha_contratacion));
    }

    // Días de vacaciones correspondientes del año en curso (según antigüedad)
    public function getDiasVacacionesCorrespondientesAttribute()
    {
        if (!$this->es_empleado || !$this->fecha_contratacion) {
            return 0;
        }

        $registro = $this->registroVacacionesActual()->first();
        // NOTA: Se eliminó la actualización automática aquí para evitar efectos secundarios en accessors.
        // Los registros de vacaciones deben ser generados por el seeder o procesos dedicados.
        return $registro?->dias_correspondientes ?? 0;
    }

    // Días de vacaciones disponibles del año en curso
    public function getDiasVacacionesDisponiblesAttribute()
    {
        if (!$this->es_empleado || !$this->fecha_contratacion) {
            return 0;
        }

        $registro = $this->registroVacacionesActual()->first();
        if (!$registro) {
            return 0;
        }

        return max(0, ($registro->dias_disponibles ?? 0) - ($registro->dias_utilizados ?? 0));
    }

    // Scope para empleados activos
    public function scopeEmpleados($query)
    {
        return $query->where('es_empleado', true);
    }

    // Scope para empleados activos
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Scope para empleados activos
    public function scopeEmpleadosActivos($query)
    {
        return $query->where('es_empleado', true)->where('activo', true);
    }

    /**
     * Scope para buscar empleados por nombre, rfc, curp o número de empleado
     */
    public function scopeBuscar($query, $search)
    {
        if (empty($search)) return $query;

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'ilike', "%{$search}%")
                ->orWhere('rfc', 'ilike', "%{$search}%")
                ->orWhere('curp', 'ilike', "%{$search}%")
                ->orWhere('nss', 'ilike', "%{$search}%")
                ->orWhere('numero_empleado', 'ilike', "%{$search}%")
                ->orWhere('email', 'ilike', "%{$search}%");
        });
    }

    /**
     * Scope para filtrar por departamento
     */
    public function scopeDepartamento($query, $departamento)
    {
        if (empty($departamento)) return $query;
        return $query->where('departamento', $departamento);
    }

    /**
     * Scope para filtrar por tipo de contrato
     */
    public function scopeTipoContrato($query, $tipoContrato)
    {
        if (empty($tipoContrato)) return $query;
        return $query->where('tipo_contrato', $tipoContrato);
    }

    // ==================== Scopes Unificados ====================

    // Scope para técnicos
    public function scopeTecnicos($query)
    {
        return $query->where('es_tecnico', true);
    }

    // Scope para técnicos activos
    public function scopeTecnicosActivos($query)
    {
        return $query->where('es_tecnico', true)->where('activo', true);
    }

    // Scope para vendedores
    public function scopeVendedores($query)
    {
        return $query->where('es_vendedor', true);
    }

    // Scope para vendedores activos
    public function scopeVendedoresActivos($query)
    {
        return $query->where('es_vendedor', true)->where('activo', true);
    }

    // ==================== Relaciones Unificadas (Herramientas) ====================

    /**
     * Herramientas asignadas a este usuario (antes en Tecnico)
     */
    public function herramientas()
    {
        return $this->hasMany(Herramienta::class, 'user_id');
    }

    /**
     * Asignaciones de herramientas
     */
    public function asignacionesHerramientas()
    {
        return $this->hasMany(AsignacionHerramienta::class, 'user_id');
    }

    /**
     * Historial de herramientas
     */
    public function historialHerramientas()
    {
        return $this->hasMany(HistorialHerramienta::class, 'user_id');
    }

    // Relación con almacén de venta predeterminado
    public function almacen_venta()
    {
        return $this->belongsTo(Almacen::class, 'almacen_venta_id');
    }

    public function almacenVenta()
    {
        return $this->almacen_venta();
    }

    // Relación con almacén de compra predeterminado
    public function almacen_compra()
    {
        return $this->belongsTo(Almacen::class, 'almacen_compra_id');
    }

    public function almacenCompra()
    {
        return $this->almacen_compra();
    }

    // Relación con Tickets asignados
    public function ticketsAsignados()
    {
        return $this->hasMany(Ticket::class, 'technician_id');
    }

    public function ticketsReportados()
    {
        return $this->hasMany(Ticket::class, 'reported_by');
    }

    // ===================================
    // PROYECTOS
    // ===================================

    /**
     * Proyectos creados por el usuario
     */
    public function ownedProjects()
    {
        return $this->hasMany(Proyecto::class, 'owner_id');
    }

    /**
     * Proyectos compartidos con el usuario
     */
    public function joinedProjects()
    {
        return $this->belongsToMany(Proyecto::class, 'proyecto_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Relación con el carro asignado
     */
    public function carro(): BelongsTo
    {
        return $this->belongsTo(Carro::class);
    }

    /**
     * Verificar si el usuario está de vacaciones o tiene un día bloqueado (descanso)
     * en una fecha específica.
     */
    public function estaDeVacaciones($fecha): bool
    {
        $fecha = \Carbon\Carbon::parse($fecha)->toDateString();

        // 1. Verificar en tabla vacaciones (aprobadas)
        $deVacaciones = $this->vacacionesAprobadas()
            ->whereDate('fecha_inicio', '<=', $fecha)
            ->whereDate('fecha_fin', '>=', $fecha)
            ->exists();

        if ($deVacaciones) {
            return true;
        }

        // 2. Verificar en tabla dias_bloqueados (específicos para este usuario o globales)
        return DiaBloqueado::where('empresa_id', $this->empresa_id)
            ->whereDate('fecha', $fecha)
            ->where(function ($q) {
                $q->whereNull('tecnico_id')
                    ->orWhere('tecnico_id', $this->id);
            })
            ->exists();
    }

    /**
     * Relación con los movimientos de caja chica
     */
    public function cajaChica()
    {
        return $this->hasMany(CajaChica::class, 'user_id');
    }

    /**
     * Evaluaciones NOM-035 del empleado
     */
    public function nom035Evaluations()
    {
        return $this->hasMany(Nom035Respondent::class, 'empleado_id');
    }

    /**
     * Última evaluación NOM-035 completada
     */
    public function ultimoNom035()
    {
        return $this->hasOne(Nom035Respondent::class, 'empleado_id')
            ->where('status', 'completed')
            ->latest('completed_at');
    }

    /**
     * Obtener la cita actual o en tránsito para el técnico
     */
    public function getCitaActualAttribute()
    {
        if (!$this->es_tecnico) return null;
        
        $citaEnProceso = \App\Models\Cita::where('tecnico_id', $this->id)
            ->where('estado', \App\Models\Cita::ESTADO_EN_PROCESO)
            ->with('cliente')
            ->first();

        if ($citaEnProceso) {
            return [
                'id' => $citaEnProceso->id,
                'folio' => $citaEnProceso->folio,
                'estado' => $citaEnProceso->estado,
                'direccion' => trim(($citaEnProceso->direccion_calle ?: ($citaEnProceso->cliente?->direccion_completa ?: 'En domicilio del cliente')) . ($citaEnProceso->direccion_colonia ? " Col. {$citaEnProceso->direccion_colonia}" : "")),
                'fecha_hora' => $citaEnProceso->fecha_hora?->format('Y-m-d H:i'),
                'cliente_nombre' => $citaEnProceso->cliente?->nombre_razon_social ?: 'Cliente',
            ];
        }

        // Si no hay en proceso, buscar la siguiente programada para hoy
        $citaProgramada = \App\Models\Cita::where('tecnico_id', $this->id)
            ->where('estado', \App\Models\Cita::ESTADO_PROGRAMADO)
            ->whereDate('fecha_hora', today())
            ->orderBy('fecha_hora')
            ->with('cliente')
            ->first();

        if ($citaProgramada) {
            return [
                'id' => $citaProgramada->id,
                'folio' => $citaProgramada->folio,
                'estado' => $citaProgramada->estado,
                'direccion' => trim(($citaProgramada->direccion_calle ?: ($citaProgramada->cliente?->direccion_completa ?: 'En domicilio del cliente')) . ($citaProgramada->direccion_colonia ? " Col. {$citaProgramada->direccion_colonia}" : "")),
                'fecha_hora' => $citaProgramada->fecha_hora?->format('Y-m-d H:i'),
                'cliente_nombre' => $citaProgramada->cliente?->nombre_razon_social ?: 'Cliente',
            ];
        }

        return null;
    }

    /**
     * Obtener un resumen legible del domicilio actual del técnico
     */
    public function getDomicilioActualAttribute(): string
    {
        $cita = $this->cita_actual;
        if ($cita) {
            $prefijo = $cita['estado'] === 'en_proceso' ? '📍 En Servicio: ' : '🚗 En Tránsito (' . Carbon::parse($cita['fecha_hora'])->format('H:i') . '): ';
            return $prefijo . $cita['direccion'] . ' (' . $cita['cliente_nombre'] . ')';
        }

        if ($this->ultima_fecha_gps) {
            return "⏱️ Sin cita activa (Último reporte GPS: " . Carbon::parse($this->ultima_fecha_gps)->diffForHumans() . ")";
        }

        return "❓ Ubicación desconocida";
    }
}

