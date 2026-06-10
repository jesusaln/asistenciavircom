<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $producto_id
 * @property int $almacen_id
 * @property int $user_id
 * @property string $tipo
 * @property int $cantidad_anterior
 * @property int $cantidad_ajuste
 * @property int $cantidad_nueva
 * @property string|null $motivo
 * @property string|null $observaciones
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \App\Models\Almacen|null $almacen
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\Producto|null $producto
 * @property-read \App\Models\User|null $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteInventario newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteInventario newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteInventario query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteInventario whereAlmacenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteInventario whereCantidadAjuste($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteInventario whereCantidadAnterior($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteInventario whereCantidadNueva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteInventario whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteInventario whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteInventario whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteInventario whereMotivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteInventario whereObservaciones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteInventario whereProductoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteInventario whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteInventario whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteInventario whereUserId($value)
 */
	class AjusteInventario extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $anio
 * @property int $dias
 * @property string|null $motivo
 * @property int $creado_por
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $creador
 * @property-read \App\Models\User|null $empleado
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteVacaciones newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteVacaciones newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteVacaciones query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteVacaciones whereAnio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteVacaciones whereCreadoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteVacaciones whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteVacaciones whereDias($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteVacaciones whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteVacaciones whereMotivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteVacaciones whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AjusteVacaciones whereUserId($value)
 */
	class AjusteVacaciones extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $producto_id
 * @property int $almacen_id
 * @property string $tipo
 * @property int $stock_actual
 * @property int $stock_minimo
 * @property string|null $mensaje
 * @property bool $leida
 * @property \Illuminate\Support\Carbon|null $leida_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \App\Models\Almacen|null $almacen
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\Producto|null $producto
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertaStock agotadas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertaStock criticas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertaStock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertaStock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertaStock noLeidas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertaStock query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertaStock whereAlmacenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertaStock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertaStock whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertaStock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertaStock whereLeida($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertaStock whereLeidaAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertaStock whereMensaje($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertaStock whereProductoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertaStock whereStockActual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertaStock whereStockMinimo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertaStock whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlertaStock whereUpdatedAt($value)
 */
	class AlertaStock extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property string|null $ubicacion
 * @property string|null $direccion
 * @property string|null $telefono
 * @property \App\Models\User|null $responsable
 * @property string $estado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property numeric|null $latitud
 * @property numeric|null $longitud
 * @property int $geocerca_radio
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventario> $inventarios
 * @property-read int|null $inventarios_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Producto> $productos
 * @property-read int|null $productos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen activos()
 * @method static \Database\Factories\AlmacenFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen inactivos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen whereDireccion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen whereGeocercaRadio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen whereLatitud($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen whereLongitud($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen whereResponsable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen whereTelefono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen whereUbicacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Almacen whereUpdatedAt($value)
 */
	class Almacen extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Herramienta|null $herramienta
 * @property-read \App\Models\User|null $tecnico
 * @property-read \App\Models\User|null $usuarioEntrega
 * @property-read \App\Models\User|null $usuarioRecepcion
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionHerramienta activas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionHerramienta entregas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionHerramienta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionHerramienta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionHerramienta query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionHerramienta recepciones()
 */
	class AsignacionHerramienta extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $codigo_asignacion
 * @property int $tecnico_id
 * @property int $asignado_por
 * @property \Illuminate\Support\Carbon $fecha_asignacion
 * @property \Illuminate\Support\Carbon|null $fecha_devolucion_programada
 * @property \Illuminate\Support\Carbon|null $fecha_devolucion_real
 * @property int|null $recibido_por
 * @property string $estado
 * @property string|null $observaciones_asignacion
 * @property string|null $observaciones_devolucion
 * @property array<array-key, mixed> $herramientas_ids
 * @property int $total_herramientas
 * @property int $herramientas_devueltas
 * @property string|null $proyecto_trabajo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $asignadoPor
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DetalleAsignacionMasiva> $detalles
 * @property-read int|null $detalles_count
 * @property-read mixed $duracion_en_dias
 * @property-read mixed $estadisticas
 * @property-read mixed $estado_color
 * @property-read mixed $estado_label
 * @property-read mixed $herramientas_pendientes
 * @property-read mixed $porcentaje_completado
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Herramienta> $herramientas
 * @property-read int|null $herramientas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HistorialHerramienta> $historialHerramientas
 * @property-read int|null $historial_herramientas_count
 * @property-read \App\Models\User|null $recibidoPor
 * @property-read \App\Models\Tecnico|null $tecnico
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva activas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva completadas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva pendientes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva porTecnico($tecnicoId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva whereAsignadoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva whereCodigoAsignacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva whereFechaAsignacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva whereFechaDevolucionProgramada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva whereFechaDevolucionReal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva whereHerramientasDevueltas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva whereHerramientasIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva whereObservacionesAsignacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva whereObservacionesDevolucion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva whereProyectoTrabajo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva whereRecibidoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva whereTecnicoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva whereTotalHerramientas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionMasiva whereUpdatedAt($value)
 */
	class AsignacionMasiva extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $empresa_id
 * @property int $user_id
 * @property int|null $almacen_id
 * @property string $tipo
 * @property \Illuminate\Support\Carbon $registrado_at
 * @property string $origen
 * @property numeric|null $latitud
 * @property numeric|null $longitud
 * @property int|null $precision_metros
 * @property string|null $direccion
 * @property string|null $selfie_path
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $notas
 * @property bool $es_incidencia
 * @property string|null $motivo_incidencia
 * @property bool $consentimiento_biometrico
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $face_verified
 * @property numeric|null $face_match_score
 * @property numeric|null $face_liveness_score
 * @property string $face_verification_status
 * @property string|null $face_provider
 * @property string|null $face_verification_notes
 * @property int|null $face_detected_count
 * @property bool $face_capture_quality_passed
 * @property numeric|null $face_quality_brightness
 * @property numeric|null $face_quality_sharpness
 * @property numeric|null $face_quality_area_ratio
 * @property numeric|null $face_quality_center_offset
 * @property string|null $face_quality_message
 * @property-read \App\Models\Almacen|null $almacen
 * @property-read \App\Models\Empresa $empresa
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereAlmacenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereConsentimientoBiometrico($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereDireccion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereEsIncidencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereFaceCaptureQualityPassed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereFaceDetectedCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereFaceLivenessScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereFaceMatchScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereFaceProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereFaceQualityAreaRatio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereFaceQualityBrightness($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereFaceQualityCenterOffset($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereFaceQualityMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereFaceQualitySharpness($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereFaceVerificationNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereFaceVerificationStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereFaceVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereLatitud($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereLongitud($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereMotivoIncidencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereOrigen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro wherePrecisionMetros($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereRegistradoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereSelfiePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsistenciaRegistro whereUserId($value)
 */
	class AsistenciaRegistro extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $filename
 * @property string $path
 * @property int|null $size
 * @property string $type
 * @property string|null $method
 * @property string $status
 * @property string|null $message
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $checksum
 * @property bool $is_encrypted
 * @property bool $integrity_verified
 * @property array<array-key, mixed>|null $security_warnings
 * @property int|null $user_id
 * @property-read mixed $size_human
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog whereChecksum($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog whereIntegrityVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog whereIsEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog whereSecurityWarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackupLog withoutTrashed()
 */
	class BackupLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int|null $cliente_id
 * @property string $titulo
 * @property string|null $descripcion
 * @property \Illuminate\Support\Carbon $fecha
 * @property string|null $hora
 * @property \Illuminate\Support\Carbon|null $inicio_at
 * @property \Illuminate\Support\Carbon|null $fin_at
 * @property string $tipo
 * @property string $estado
 * @property int $prioridad
 * @property string|null $ubicacion
 * @property array<array-key, mixed>|null $adjuntos
 * @property bool $es_facturable
 * @property numeric|null $costo_mxn
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $asignado_id
 * @property-read \App\Models\User|null $asignado
 * @property-read \App\Models\Cliente|null $cliente
 * @property-read int|null $duracion_minutos
 * @property-read string|null $fecha_fmt
 * @property-read string|null $hora_fmt
 * @property-read \App\Models\User|null $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad asignadasA($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad buscar(?string $term)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad deCliente($clienteId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad deUsuario($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad pendientesParaUsuario($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad rangoFechas($desde, $hasta)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad sinCancelados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad sinCompletados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad soloHoyOMantenerEnProceso(string $tz = 'America/Hermosillo')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad whereAdjuntos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad whereAsignadoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad whereCostoMxn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad whereEsFacturable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad whereFecha($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad whereFinAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad whereHora($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad whereInicioAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad wherePrioridad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad whereTitulo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad whereUbicacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BitacoraActividad whereUserId($value)
 */
	class BitacoraActividad extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $empresa_id
 * @property string $titulo
 * @property string $slug
 * @property string|null $resumen
 * @property string $contenido
 * @property string|null $imagen_portada
 * @property string|null $categoria
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $publicado_at
 * @property int $visitas
 * @property string|null $meta_titulo
 * @property string|null $meta_descripcion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $newsletter_enviado_at
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read mixed $imagen_portada_url
 * @property-read mixed $tiempo_lectura
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost publicado()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost published()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereCategoria($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereContenido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereImagenPortada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereMetaDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereMetaTitulo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereNewsletterEnviadoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost wherePublicadoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereResumen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereTitulo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereVisitas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost withoutTrashed()
 */
	class BlogPost extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $concepto
 * @property numeric $monto
 * @property string $tipo
 * @property \Illuminate\Support\Carbon $fecha
 * @property string|null $comprobante_path
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $categoria
 * @property string|null $nota
 * @property int|null $empresa_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CajaChicaAdjunto> $adjuntos
 * @property-read int|null $adjuntos_count
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read mixed $comprobante_url
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChica newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChica newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChica query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChica whereCategoria($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChica whereComprobantePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChica whereConcepto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChica whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChica whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChica whereFecha($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChica whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChica whereMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChica whereNota($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChica whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChica whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChica whereUserId($value)
 */
	class CajaChica extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $caja_chica_id
 * @property string $path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CajaChica|null $cajaChica
 * @property-read mixed $url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChicaAdjunto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChicaAdjunto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChicaAdjunto query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChicaAdjunto whereCajaChicaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChicaAdjunto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChicaAdjunto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChicaAdjunto wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CajaChicaAdjunto whereUpdatedAt($value)
 */
	class CajaChicaAdjunto extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $marca
 * @property string $modelo
 * @property int $anio
 * @property string $color
 * @property numeric $precio
 * @property string $numero_serie
 * @property string $combustible
 * @property int $kilometraje
 * @property string|null $placa
 * @property string|null $foto
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $activo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Mantenimiento> $mantenimientos
 * @property-read int|null $mantenimientos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carro newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carro newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carro query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carro whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carro whereAnio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carro whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carro whereCombustible($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carro whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carro whereFoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carro whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carro whereKilometraje($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carro whereMarca($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carro whereModelo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carro whereNumeroSerie($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carro wherePlaca($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carro wherePrecio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carro whereUpdatedAt($value)
 */
	class Carro extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $tipo
 * @property string $clave
 * @property string $nombre
 * @property string|null $clave_sat
 * @property string|null $descripcion
 * @property numeric|null $porcentaje_default
 * @property bool $es_gravable
 * @property bool $es_automatico
 * @property bool $activo
 * @property int $orden
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina activos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina deducciones()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina ordenado()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina percepciones()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina whereClave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina whereClaveSat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina whereEsAutomatico($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina whereEsGravable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina whereOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina wherePorcentajeDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogoConceptoNomina whereUpdatedAt($value)
 */
	class CatalogoConceptoNomina extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $estado
 * @property int|null $empresa_id
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Producto> $productos
 * @property-read int|null $productos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria activas()
 * @method static \Database\Factories\CategoriaFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria inactivas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria whereUpdatedAt($value)
 */
	class Categoria extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property string|null $codigo
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Compra> $compras
 * @property-read int|null $compras_count
 * @property-read \App\Models\Empresa|null $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaGasto activas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaGasto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaGasto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaGasto query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaGasto whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaGasto whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaGasto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaGasto whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaGasto whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaGasto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaGasto whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaGasto whereUpdatedAt($value)
 */
	class CategoriaGasto extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string $slug
 * @property string|null $descripcion
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read mixed $nombre_formatted
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Herramienta> $herramientas
 * @property-read int|null $herramientas_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaHerramienta activas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaHerramienta inactivas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaHerramienta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaHerramienta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaHerramienta query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaHerramienta whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaHerramienta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaHerramienta whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaHerramienta whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaHerramienta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaHerramienta whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaHerramienta whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaHerramienta whereUpdatedAt($value)
 */
	class CategoriaHerramienta extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $cliente_id
 * @property int|null $empresa_id
 * @property int|null $venta_id
 * @property int|null $cfdi_relacionado_id
 * @property string $tipo_comprobante
 * @property string|null $serie
 * @property string|null $folio
 * @property string|null $uuid
 * @property \Illuminate\Support\Carbon|null $fecha_timbrado
 * @property string|null $no_certificado_sat
 * @property string|null $no_certificado_cfdi
 * @property string|null $sello_sat
 * @property string|null $sello_cfdi
 * @property string|null $cadena_original
 * @property string $estatus
 * @property \Illuminate\Support\Carbon $fecha_emision
 * @property \Illuminate\Support\Carbon|null $fecha_cancelacion
 * @property string $moneda
 * @property numeric|null $tipo_cambio
 * @property numeric $subtotal
 * @property numeric $descuento
 * @property numeric $total_impuestos_trasladados
 * @property numeric $total_impuestos_retenidos
 * @property numeric $total
 * @property string|null $metodo_pago
 * @property string|null $forma_pago
 * @property string|null $condiciones_pago
 * @property string|null $uso_cfdi
 * @property array<array-key, mixed>|null $complementos
 * @property string|null $pac_rfc
 * @property string|null $pac_nombre
 * @property string|null $xml_url
 * @property string|null $pdf_url
 * @property string|null $observaciones
 * @property array<array-key, mixed>|null $datos_adicionales
 * @property string|null $creado_por
 * @property string|null $actualizado_por
 * @property string|null $cancelado_por
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $motivo_cancelacion
 * @property string|null $folio_sustitucion
 * @property string|null $acuse_cancelacion
 * @property string $direccion
 * @property string|null $rfc_emisor
 * @property string|null $nombre_emisor
 * @property string|null $regimen_fiscal_emisor
 * @property string|null $rfc_receptor
 * @property string|null $nombre_receptor
 * @property string|null $cfdiable_type
 * @property int|null $cfdiable_id
 * @property int|null $factura_id
 * @property-read Cfdi|null $cfdiRelacionado
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Cfdi> $cfdisRelacionados
 * @property-read int|null $cfdis_relacionados_count
 * @property-read \App\Models\Cliente|null $cliente
 * @property-read \App\Models\Compra|null $compra
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CfdiConcepto> $conceptos
 * @property-read int|null $conceptos_count
 * @property-read \App\Models\CuentasPorCobrar|null $cuentaPorCobrar
 * @property-read \App\Models\CuentasPorPagar|null $cuentaPorPagar
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read bool $esta_cancelado
 * @property-read bool $esta_timbrado
 * @property-read string $estatus_nombre
 * @property-read bool $puede_cancelarse
 * @property-read string $serie_folio
 * @property-read string $tipo_comprobante_nombre
 * @property-read \App\Models\Venta|null $venta
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi cancelados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi direccion($direccion)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi emitidos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi estatus($estatus)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi porCliente($clienteId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi porPeriodo($fechaInicio, $fechaFin)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi recibidos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi timbrados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi tipoComprobante($tipo)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereActualizadoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereAcuseCancelacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereCadenaOriginal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereCanceladoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereCfdiRelacionadoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereCfdiableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereCfdiableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereComplementos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereCondicionesPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereCreadoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereDatosAdicionales($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereDescuento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereDireccion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereEstatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereFacturaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereFechaCancelacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereFechaEmision($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereFechaTimbrado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereFolio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereFolioSustitucion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereFormaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereMetodoPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereMoneda($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereMotivoCancelacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereNoCertificadoCfdi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereNoCertificadoSat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereNombreEmisor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereNombreReceptor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereObservaciones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi wherePacNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi wherePacRfc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi wherePdfUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereRegimenFiscalEmisor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereRfcEmisor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereRfcReceptor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereSelloCfdi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereSelloSat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereSerie($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereTipoCambio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereTipoComprobante($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereTotalImpuestosRetenidos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereTotalImpuestosTrasladados($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereUsoCfdi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereVentaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi whereXmlUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cfdi withoutTrashed()
 */
	class Cfdi extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cfdi_id
 * @property string $clave_prod_serv
 * @property string|null $no_identificacion
 * @property numeric $cantidad
 * @property string $clave_unidad
 * @property string|null $unidad
 * @property string $descripcion
 * @property numeric $valor_unitario
 * @property numeric $importe
 * @property numeric $descuento
 * @property array<array-key, mixed>|null $impuestos
 * @property string|null $numero_pedimento
 * @property string|null $cuenta_predial
 * @property array<array-key, mixed>|null $complemento
 * @property int|null $producto_id
 * @property int|null $servicio_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $objeto_imp
 * @property-read \App\Models\Cfdi|null $cfdi
 * @property-read float $importe_neto
 * @property-read array $impuestos_retenidos
 * @property-read array $impuestos_trasladados
 * @property-read bool $tiene_impuestos
 * @property-read \App\Models\Producto|null $producto
 * @property-read \App\Models\Servicio|null $servicio
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereCantidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereCfdiId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereClaveProdServ($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereClaveUnidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereComplemento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereCuentaPredial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereDescuento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereImporte($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereImpuestos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereNoIdentificacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereNumeroPedimento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereObjetoImp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereProductoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereServicioId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereUnidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CfdiConcepto whereValorUnitario($value)
 */
	class CfdiConcepto extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $tecnico_id
 * @property int $cliente_id
 * @property string $tipo_servicio
 * @property \Illuminate\Support\Carbon|null $fecha_hora
 * @property string|null $descripcion
 * @property string|null $tipo_equipo
 * @property string|null $marca_equipo
 * @property string|null $modelo_equipo
 * @property string|null $problema_reportado
 * @property string|null $prioridad
 * @property string $estado
 * @property string|null $evidencias
 * @property string|null $foto_equipo
 * @property string|null $foto_hoja_servicio
 * @property string|null $foto_identificacion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property bool $activo
 * @property string|null $notas
 * @property numeric $subtotal
 * @property numeric $descuento_general
 * @property numeric $descuento_items
 * @property numeric $iva
 * @property numeric $total
 * @property \Illuminate\Support\Carbon|null $inicio_servicio
 * @property \Illuminate\Support\Carbon|null $fin_servicio
 * @property int|null $tiempo_servicio
 * @property int|null $user_id
 * @property int|null $empresa_id
 * @property string|null $folio
 * @property bool $es_publica
 * @property string|null $origen_tienda Liverpool, Coppel, Elektra, Sears, etc.
 * @property string|null $numero_ticket_tienda Número de factura/ticket de la tienda
 * @property string|null $horario_preferido mañana, mediodia, tarde, noche
 * @property array<array-key, mixed>|null $dias_preferidos Array de fechas preferidas por el cliente
 * @property \Illuminate\Support\Carbon|null $fecha_confirmada
 * @property string|null $hora_confirmada
 * @property string|null $direccion_calle
 * @property string|null $direccion_colonia
 * @property string|null $direccion_cp
 * @property string|null $direccion_referencias Referencias para llegar: entre calles, color casa, etc.
 * @property string|null $link_seguimiento UUID para página pública de seguimiento
 * @property bool $whatsapp_recepcion_enviado
 * @property bool $whatsapp_confirmacion_enviado
 * @property \Illuminate\Support\Carbon|null $whatsapp_recepcion_at
 * @property \Illuminate\Support\Carbon|null $whatsapp_confirmacion_at
 * @property string|null $trabajo_realizado
 * @property array<array-key, mixed>|null $fotos_finales
 * @property string|null $microsoft_task_id
 * @property int|null $ticket_id
 * @property string|null $firma_cliente
 * @property string|null $nombre_firmante
 * @property \Illuminate\Support\Carbon|null $fecha_firma
 * @property string|null $firma_tecnico
 * @property int|null $poliza_id
 * @property-read \App\Models\Cliente|null $cliente
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read string $direccion_completa
 * @property-read mixed $es_hoy
 * @property-read mixed $es_pasada
 * @property-read bool $esta_confirmada
 * @property-read mixed $estado_color
 * @property-read string|null $hora_confirmada_rango
 * @property-read array|null $horario_preferido_info
 * @property-read string|null $nombre_tienda
 * @property-read mixed $prioridad_color
 * @property-read mixed $tiempo_servicio_formateado
 * @property-read string|null $url_seguimiento
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CitaItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\PolizaServicio|null $poliza
 * @property-read \App\Models\ProductoSerie|null $productoSerieGarantia
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Producto> $productos
 * @property-read int|null $productos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Servicio> $servicios
 * @property-read int|null $servicios_count
 * @property-read \App\Models\User|null $tecnico
 * @property-read \App\Models\Ticket|null $ticket
 * @property-read \App\Models\Venta|null $venta
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita canceladas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita completadas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita enProceso()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita estaSemana()
 * @method static \Database\Factories\CitaFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita hoy()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita pendientes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita pendientesAsignacion()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita porCliente($clienteId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita porTecnico($tecnicoId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita proximas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita publicas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereDescuentoGeneral($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereDescuentoItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereDiasPreferidos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereDireccionCalle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereDireccionColonia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereDireccionCp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereDireccionReferencias($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereEsPublica($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereEvidencias($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereFechaConfirmada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereFechaFirma($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereFechaHora($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereFinServicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereFirmaCliente($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereFirmaTecnico($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereFolio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereFotoEquipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereFotoHojaServicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereFotoIdentificacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereFotosFinales($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereHoraConfirmada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereHorarioPreferido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereInicioServicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereIva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereLinkSeguimiento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereMarcaEquipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereMicrosoftTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereModeloEquipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereNombreFirmante($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereNumeroTicketTienda($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereOrigenTienda($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita wherePolizaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita wherePrioridad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereProblemaReportado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereTecnicoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereTiempoServicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereTipoEquipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereTipoServicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereTrabajoRealizado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereWhatsappConfirmacionAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereWhatsappConfirmacionEnviado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereWhatsappRecepcionAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita whereWhatsappRecepcionEnviado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita withoutTrashed()
 */
	class Cita extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cita_id
 * @property int $citable_id
 * @property string $citable_type
 * @property int $cantidad
 * @property numeric $precio
 * @property numeric $descuento
 * @property numeric $subtotal
 * @property numeric $descuento_monto
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \App\Models\Cita|null $cita
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $citable
 * @property-read \App\Models\Empresa|null $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitaItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitaItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitaItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitaItem whereCantidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitaItem whereCitaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitaItem whereCitableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitaItem whereCitableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitaItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitaItem whereDescuento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitaItem whereDescuentoMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitaItem whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitaItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitaItem whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitaItem wherePrecio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitaItem whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CitaItem whereUpdatedAt($value)
 */
	class CitaItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre_razon_social
 * @property string|null $tipo_persona
 * @property string|null $tipo_identificacion
 * @property string|null $identificacion
 * @property string|null $curp
 * @property string|null $rfc
 * @property string|null $regimen_fiscal
 * @property string|null $uso_cfdi
 * @property string|null $email
 * @property string|null $telefono
 * @property string|null $calle
 * @property string|null $numero_exterior
 * @property string|null $numero_interior
 * @property string|null $colonia
 * @property string|null $codigo_postal
 * @property string|null $municipio
 * @property string|null $estado
 * @property string $pais
 * @property string|null $notas
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $cfdi_default_use
 * @property string|null $payment_form_default
 * @property string|null $domicilio_fiscal_cp
 * @property string|null $residencia_fiscal
 * @property string|null $num_reg_id_trib
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property bool $requiere_factura
 * @property int|null $price_list_id
 * @property bool $whatsapp_optin
 * @property \Illuminate\Support\Carbon|null $whatsapp_consent_date
 * @property string|null $whatsapp_consent_method
 * @property string|null $whatsapp_consent_source
 * @property bool $credito_activo
 * @property numeric $limite_credito
 * @property string|null $forma_pago_default
 * @property int $dias_credito
 * @property int|null $empresa_id
 * @property string|null $codigo
 * @property string|null $password
 * @property string|null $remember_token
 * @property string $estado_credito
 * @property string $uuid
 * @property int|null $dias_gracia Días de gracia específicos para este cliente antes del bloqueo
 * @property string|null $credito_firma
 * @property \Illuminate\Support\Carbon|null $credito_firmado_at
 * @property string|null $credito_firmado_ip
 * @property string|null $credito_firmado_nombre
 * @property string|null $credito_firma_hash
 * @property numeric $credito_solicitado_monto
 * @property int $credito_solicitado_dias
 * @property string|null $domicilio_fiscal_calle
 * @property string|null $domicilio_fiscal_numero
 * @property string|null $domicilio_fiscal_colonia
 * @property string|null $domicilio_fiscal_municipio
 * @property string|null $domicilio_fiscal_estado
 * @property bool $misma_direccion_fiscal
 * @property string|null $razon_social
 * @property bool $recibe_newsletter
 * @property string|null $newsletter_unsubscribed_at
 * @property string|null $rustdesk_id
 * @property string|null $rustdesk_alias
 * @property bool $whatsapp_opt_in
 * @property \Illuminate\Support\Carbon|null $whatsapp_opt_in_at
 * @property string|null $whatsapp_opt_in_ip
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \OwenIt\Auditing\Models\Audit> $audits
 * @property-read int|null $audits_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cita> $citas
 * @property-read int|null $citas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cotizacion> $cotizaciones
 * @property-read int|null $cotizaciones_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Credencial> $credenciales
 * @property-read int|null $credenciales_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CuentasPorCobrar> $cuentasPorCobrar
 * @property-read int|null $cuentas_por_cobrar_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ClienteDocumento> $documentos
 * @property-read int|null $documentos_count
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\SatEstado|null $estadoSat
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Factura> $facturas
 * @property-read int|null $facturas_count
 * @property-read float $credito_disponible
 * @property-read string $direccion_completa
 * @property-read bool $es_extranjero
 * @property-read string|null $estado_nombre
 * @property-read string $nombre_fiscal
 * @property-read string|null $regimen_descripcion
 * @property-read float $saldo_pendiente
 * @property-read string|null $uso_cfdi_descripcion
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pedido> $pedidos
 * @property-read int|null $pedidos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PolizaServicio> $polizas
 * @property-read int|null $polizas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Prestamo> $prestamos
 * @property-read int|null $prestamos_count
 * @property-read \App\Models\PriceList|null $priceList
 * @property-read \App\Models\SatRegimenFiscal|null $regimen
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Renta> $rentas
 * @property-read int|null $rentas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 * @property-read int|null $tickets_count
 * @property-read \App\Models\SatUsoCfdi|null $uso
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Venta> $ventas
 * @property-read int|null $ventas_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente activos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente buscar(?string $q)
 * @method static \Database\Factories\ClienteFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente inactivos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereCalle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereCfdiDefaultUse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereCodigoPostal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereColonia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereCreditoActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereCreditoFirma($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereCreditoFirmaHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereCreditoFirmadoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereCreditoFirmadoIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereCreditoFirmadoNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereCreditoSolicitadoDias($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereCreditoSolicitadoMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereCurp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereDiasCredito($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereDiasGracia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereDomicilioFiscalCalle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereDomicilioFiscalColonia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereDomicilioFiscalCp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereDomicilioFiscalEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereDomicilioFiscalMunicipio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereDomicilioFiscalNumero($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereEstadoCredito($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereFormaPagoDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereIdentificacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereLimiteCredito($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereMismaDireccionFiscal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereMunicipio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereNewsletterUnsubscribedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereNombreRazonSocial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereNumRegIdTrib($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereNumeroExterior($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereNumeroInterior($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente wherePais($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente wherePaymentFormDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente wherePriceListId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereRazonSocial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereRecibeNewsletter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereRegimenFiscal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereRequiereFactura($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereResidenciaFiscal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereRfc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereRustdeskAlias($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereRustdeskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereTelefono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereTipoIdentificacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereTipoPersona($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereUsoCfdi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereWhatsappConsentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereWhatsappConsentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereWhatsappConsentSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereWhatsappOptInAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereWhatsappOptInIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereWhatsappOptin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente withoutTrashed()
 */
	class Cliente extends \Eloquent implements \OwenIt\Auditing\Contracts\Auditable {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cliente_id
 * @property string|null $nombre Nombre de la sucursal o ubicación, e.g. "Matriz", "Almacén"
 * @property string $calle
 * @property string $numero_exterior
 * @property string|null $numero_interior
 * @property string $colonia
 * @property string $codigo_postal
 * @property string|null $ciudad
 * @property string $municipio
 * @property string $estado
 * @property string $pais
 * @property string|null $referencias
 * @property string|null $contacto_nombre
 * @property string|null $contacto_telefono
 * @property bool $es_principal
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Cliente $cliente
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion activa()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereCalle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereCiudad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereCodigoPostal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereColonia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereContactoNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereContactoTelefono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereEsPrincipal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereMunicipio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereNumeroExterior($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereNumeroInterior($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion wherePais($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereReferencias($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDireccion withoutTrashed()
 */
	class ClienteDireccion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cliente_id
 * @property string $tipo
 * @property string $nombre_archivo
 * @property string $ruta
 * @property string|null $extension
 * @property int|null $tamano
 * @property string|null $mime_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Cliente $cliente
 * @property-read mixed $url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDocumento newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDocumento newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDocumento query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDocumento whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDocumento whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDocumento whereExtension($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDocumento whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDocumento whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDocumento whereNombreArchivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDocumento whereRuta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDocumento whereTamano($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDocumento whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteDocumento whereUpdatedAt($value)
 */
	class ClienteDocumento extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string $email
 * @property string|null $avatar
 * @property string $provider
 * @property string|null $provider_id
 * @property string|null $telefono
 * @property array<array-key, mixed>|null $direccion_predeterminada
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read string $iniciales
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PedidoOnline> $pedidos
 * @property-read int|null $pedidos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteTienda newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteTienda newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteTienda query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteTienda whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteTienda whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteTienda whereDireccionPredeterminada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteTienda whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteTienda whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteTienda whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteTienda whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteTienda whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteTienda whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteTienda whereProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteTienda whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteTienda whereTelefono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteTienda whereUpdatedAt($value)
 */
	class ClienteTienda extends \Eloquent implements \Illuminate\Contracts\Auth\Authenticatable {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $renta_id
 * @property \Illuminate\Support\Carbon $fecha_cobro
 * @property numeric $monto_cobrado
 * @property string $concepto
 * @property string $estado
 * @property \Illuminate\Support\Carbon|null $fecha_pago
 * @property numeric $monto_pagado
 * @property string|null $metodo_pago
 * @property string|null $referencia_pago
 * @property string|null $notas
 * @property int|null $responsable_cobro
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $recibido_por
 * @property string|null $notas_pago
 * @property int|null $empresa_id
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\Renta|null $renta
 * @property-read \App\Models\User|null $responsableCobro
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza delMes($mes = null, $anio = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza pagadas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza pendientes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza vencidas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza whereConcepto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza whereFechaCobro($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza whereFechaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza whereMetodoPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza whereMontoCobrado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza whereMontoPagado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza whereNotasPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza whereRecibidoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza whereReferenciaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza whereRentaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza whereResponsableCobro($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cobranza whereUpdatedAt($value)
 */
	class Cobranza extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $almacen_id
 * @property int|null $proveedor_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property string $numero_compra
 * @property numeric|null $subtotal
 * @property numeric $descuento_general
 * @property numeric $descuento_items
 * @property numeric|null $iva
 * @property numeric $total
 * @property \App\Enums\EstadoCompra $estado
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $fecha_compra
 * @property int|null $orden_compra_id
 * @property bool $inventario_procesado
 * @property string|null $metodo_pago
 * @property string $tipo
 * @property int|null $categoria_gasto_id
 * @property string|null $cfdi_uuid
 * @property string|null $cfdi_folio
 * @property string|null $cfdi_serie
 * @property string|null $cfdi_tipo_comprobante
 * @property string|null $cfdi_forma_pago
 * @property string|null $cfdi_metodo_pago
 * @property string|null $cfdi_uso
 * @property \Illuminate\Support\Carbon|null $cfdi_fecha
 * @property string|null $cfdi_emisor_rfc
 * @property string|null $cfdi_emisor_nombre
 * @property int|null $cuenta_bancaria_id
 * @property numeric $isr
 * @property numeric $retencion_iva
 * @property numeric $retencion_isr
 * @property bool $aplicar_retencion_iva
 * @property bool $aplicar_retencion_isr
 * @property string|null $origen_importacion
 * @property int|null $empresa_id
 * @property numeric|null $cfdi_total
 * @property string $moneda
 * @property numeric $tipo_cambio
 * @property string|null $folio
 * @property int|null $proyecto_id
 * @property-read \App\Models\Almacen|null $almacen
 * @property-read \App\Models\CategoriaGasto|null $categoriaGasto
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CompraItem> $compraItems
 * @property-read int|null $compra_items_count
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\CuentaBancaria|null $cuentaBancaria
 * @property-read \App\Models\CuentasPorPagar|null $cuentasPorPagar
 * @property-read \App\Models\User|null $deletedBy
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InventarioMovimiento> $movimientos
 * @property-read int|null $movimientos_count
 * @property-read \App\Models\OrdenCompra|null $ordenCompra
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Producto> $productos
 * @property-read int|null $productos_count
 * @property-read \App\Models\Proveedor|null $proveedor
 * @property-read \App\Models\Proyecto|null $proyecto
 * @property-read \App\Models\User|null $updatedBy
 * @method static \Database\Factories\CompraFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereAlmacenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereAplicarRetencionIsr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereAplicarRetencionIva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereCategoriaGastoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereCfdiEmisorNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereCfdiEmisorRfc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereCfdiFecha($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereCfdiFolio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereCfdiFormaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereCfdiMetodoPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereCfdiSerie($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereCfdiTipoComprobante($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereCfdiTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereCfdiUso($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereCfdiUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereCuentaBancariaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereDescuentoGeneral($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereDescuentoItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereFechaCompra($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereFolio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereInventarioProcesado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereIsr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereIva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereMetodoPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereMoneda($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereNumeroCompra($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereOrdenCompraId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereOrigenImportacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereProveedorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereProyectoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereRetencionIsr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereRetencionIva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereTipoCambio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Compra withoutTrashed()
 */
	class Compra extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $compra_id
 * @property int|null $comprable_id
 * @property string|null $comprable_type
 * @property int $cantidad
 * @property numeric $precio
 * @property numeric $descuento
 * @property numeric $subtotal
 * @property numeric $descuento_monto
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $descripcion
 * @property string|null $unidad_medida
 * @property int|null $empresa_id
 * @property string|null $deleted_at
 * @property-read \App\Models\Compra|null $compra
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $comprable
 * @property-read \App\Models\Empresa|null $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompraItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompraItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompraItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompraItem whereCantidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompraItem whereCompraId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompraItem whereComprableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompraItem whereComprableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompraItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompraItem whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompraItem whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompraItem whereDescuento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompraItem whereDescuentoMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompraItem whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompraItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompraItem wherePrecio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompraItem whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompraItem whereUnidadMedida($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompraItem whereUpdatedAt($value)
 */
	class CompraItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cliente_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property string $numero_cotizacion
 * @property numeric|null $subtotal
 * @property numeric $descuento_general
 * @property numeric $descuento_items
 * @property numeric|null $iva
 * @property numeric $total
 * @property \App\Enums\EstadoCotizacion $estado
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $fecha_cotizacion
 * @property bool $email_enviado
 * @property \Illuminate\Support\Carbon|null $email_enviado_fecha
 * @property int|null $email_enviado_por
 * @property int|null $almacen_id
 * @property numeric $isr
 * @property numeric $retencion_iva
 * @property numeric $retencion_isr
 * @property int|null $empresa_id
 * @property string|null $folio
 * @property-read \App\Models\Cliente|null $cliente
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\User|null $deletedBy
 * @property-read \App\Models\User|null $emailEnviadoPor
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CotizacionItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\Pedido|null $pedido
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pedido> $pedidos
 * @property-read int|null $pedidos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Producto> $productos
 * @property-read int|null $productos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Servicio> $servicios
 * @property-read int|null $servicios_count
 * @property-read \App\Models\User|null $updatedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Venta> $ventas
 * @property-read int|null $ventas_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereAlmacenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereDescuentoGeneral($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereDescuentoItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereEmailEnviado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereEmailEnviadoFecha($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereEmailEnviadoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereFechaCotizacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereFolio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereIsr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereIva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereNumeroCotizacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereRetencionIsr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereRetencionIva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion withoutTrashed()
 */
	class Cotizacion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cotizacion_id
 * @property int $cotizable_id
 * @property string $cotizable_type
 * @property int $cantidad
 * @property numeric $precio
 * @property numeric $descuento
 * @property numeric $subtotal
 * @property numeric $descuento_monto
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $price_list_id
 * @property int|null $empresa_id
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $cotizable
 * @property-read \App\Models\Cotizacion|null $cotizacion
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\PriceList|null $priceList
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionItem whereCantidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionItem whereCotizableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionItem whereCotizableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionItem whereCotizacionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionItem whereDescuento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionItem whereDescuentoMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionItem whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionItem wherePrecio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionItem wherePriceListId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionItem whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionItem whereUpdatedAt($value)
 */
	class CotizacionItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $empresa_id
 * @property string $credentialable_type
 * @property int $credentialable_id
 * @property string $nombre
 * @property string $usuario
 * @property string $password
 * @property string|null $host
 * @property string|null $puerto
 * @property string|null $notas
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $categoria
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $last_revealed_at
 * @property-read \App\Models\User|null $creador
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $credentialable
 * @property-read \App\Models\Empresa $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CredencialAccesoLog> $logs
 * @property-read int|null $logs_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial whereCategoria($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial whereCredentialableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial whereCredentialableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial whereHost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial whereLastRevealedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial wherePuerto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial whereUsuario($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Credencial withoutTrashed()
 */
	class Credencial extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $credencial_id
 * @property int $user_id
 * @property string $accion
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Credencial $credencial
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CredencialAccesoLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CredencialAccesoLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CredencialAccesoLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CredencialAccesoLog whereAccion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CredencialAccesoLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CredencialAccesoLog whereCredencialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CredencialAccesoLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CredencialAccesoLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CredencialAccesoLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CredencialAccesoLog whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CredencialAccesoLog whereUserId($value)
 */
	class CredencialAccesoLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $prospecto_id
 * @property int $user_id
 * @property string $tipo
 * @property string|null $resultado
 * @property string|null $notas
 * @property int|null $duracion_minutos
 * @property \Illuminate\Support\Carbon|null $proxima_actividad_at
 * @property string|null $proxima_actividad_tipo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read string $resultado_color
 * @property-read string $resultado_label
 * @property-read string $tipo_icon
 * @property-read string $tipo_label
 * @property-read \App\Models\CrmProspecto|null $prospecto
 * @property-read \App\Models\User|null $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmActividad newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmActividad newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmActividad query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmActividad whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmActividad whereDuracionMinutos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmActividad whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmActividad whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmActividad whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmActividad whereProspectoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmActividad whereProximaActividadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmActividad whereProximaActividadTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmActividad whereResultado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmActividad whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmActividad whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmActividad whereUserId($value)
 */
	class CrmActividad extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property int|null $producto_id
 * @property string|null $descripcion
 * @property string|null $objetivo
 * @property \Illuminate\Support\Carbon $fecha_inicio
 * @property \Illuminate\Support\Carbon $fecha_fin
 * @property int $meta_actividades_dia
 * @property bool $activa
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \App\Models\User|null $creador
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CrmMeta> $metas
 * @property-read int|null $metas_count
 * @property-read \App\Models\Producto|null $producto
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CrmScript> $scripts
 * @property-read int|null $scripts_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmCampania activas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmCampania newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmCampania newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmCampania query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmCampania vigentes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmCampania whereActiva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmCampania whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmCampania whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmCampania whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmCampania whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmCampania whereFechaFin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmCampania whereFechaInicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmCampania whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmCampania whereMetaActividadesDia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmCampania whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmCampania whereObjetivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmCampania whereProductoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmCampania whereUpdatedAt($value)
 */
	class CrmCampania extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $tipo
 * @property int $meta_diaria
 * @property \Illuminate\Support\Carbon|null $fecha_inicio
 * @property \Illuminate\Support\Carbon|null $fecha_fin
 * @property bool $activa
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $campania_id
 * @property int|null $empresa_id
 * @property-read \App\Models\CrmCampania|null $campania
 * @property-read \App\Models\User|null $creador
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta activas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta delTipo($tipo)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta delUsuario($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta vigentes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta whereActiva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta whereCampaniaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta whereFechaFin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta whereFechaInicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta whereMetaDiaria($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmMeta whereUserId($value)
 */
	class CrmMeta extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $cliente_id
 * @property string $nombre
 * @property string|null $telefono
 * @property string|null $email
 * @property \App\Models\Empresa|null $empresa
 * @property string $origen
 * @property string $etapa
 * @property int|null $vendedor_id
 * @property string $prioridad
 * @property numeric|null $valor_estimado
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon|null $ultima_actividad_at
 * @property \Illuminate\Support\Carbon|null $proxima_actividad_at
 * @property \Illuminate\Support\Carbon|null $cerrado_at
 * @property string|null $motivo_perdida
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $tipo_persona
 * @property string|null $rfc
 * @property string|null $curp
 * @property string|null $regimen_fiscal
 * @property string|null $uso_cfdi
 * @property bool $requiere_factura
 * @property string|null $calle
 * @property string|null $numero_exterior
 * @property string|null $numero_interior
 * @property string|null $codigo_postal
 * @property string|null $colonia
 * @property string|null $municipio
 * @property string|null $estado
 * @property string|null $pais
 * @property int|null $price_list_id
 * @property int|null $empresa_id
 * @property string|null $domicilio_fiscal_cp
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property string|null $utm_term
 * @property string|null $utm_content
 * @property string|null $gclid
 * @property string|null $fbclid
 * @property string|null $referer
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CrmActividad> $actividades
 * @property-read int|null $actividades_count
 * @property-read \App\Models\Cliente|null $cliente
 * @property-read \App\Models\User|null $creador
 * @property-read string $etapa_label
 * @property-read string $origen_label
 * @property-read string $prioridad_label
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CrmTarea> $tareas
 * @property-read int|null $tareas_count
 * @property-read \App\Models\User|null $vendedor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto activos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto conActividadPendiente()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto delVendedor($vendedorId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto enEtapa($etapa)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereCalle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereCerradoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereCodigoPostal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereColonia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereCurp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereDomicilioFiscalCp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereEmpresa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereEtapa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereFbclid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereGclid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereMotivoPerdida($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereMunicipio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereNumeroExterior($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereNumeroInterior($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereOrigen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto wherePais($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto wherePriceListId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto wherePrioridad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereProximaActividadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereReferer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereRegimenFiscal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereRequiereFactura($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereRfc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereTelefono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereTipoPersona($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereUltimaActividadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereUsoCfdi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereUtmCampaign($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereUtmContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereUtmMedium($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereUtmSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereUtmTerm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereValorEstimado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto whereVendedorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmProspecto withoutTrashed()
 */
	class CrmProspecto extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string $tipo
 * @property string $etapa
 * @property string $contenido
 * @property string|null $tips
 * @property bool $activo
 * @property int $orden
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $campania_id
 * @property int|null $empresa_id
 * @property-read \App\Models\CrmCampania|null $campania
 * @property-read \App\Models\User|null $creador
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read string $etapa_label
 * @property-read string $tipo_icon
 * @property-read string $tipo_label
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript activos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript porEtapa($etapa)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript porTipo($tipo)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript whereCampaniaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript whereContenido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript whereEtapa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript whereOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript whereTips($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmScript whereUpdatedAt($value)
 */
	class CrmScript extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int|null $prospecto_id
 * @property string $titulo
 * @property string|null $descripcion
 * @property string $tipo
 * @property string $prioridad
 * @property \Illuminate\Support\Carbon $fecha_limite
 * @property \Illuminate\Support\Carbon|null $completada_at
 * @property string|null $notas_resultado
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \App\Models\User|null $creador
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read bool $esta_completada
 * @property-read bool $esta_vencida
 * @property-read string $prioridad_color
 * @property-read string $prioridad_label
 * @property-read string $tipo_icon
 * @property-read string $tipo_label
 * @property-read \App\Models\CrmProspecto|null $prospecto
 * @property-read \App\Models\User|null $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea completadas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea delUsuario($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea paraHoy()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea pendientes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea vencidas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea whereCompletadaAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea whereFechaLimite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea whereNotasResultado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea wherePrioridad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea whereProspectoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea whereTitulo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CrmTarea whereUserId($value)
 */
	class CrmTarea extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string $banco
 * @property string|null $numero_cuenta
 * @property string|null $clabe
 * @property numeric $saldo_inicial
 * @property numeric $saldo_actual
 * @property string $moneda
 * @property string $tipo
 * @property bool $activa
 * @property string|null $notas
 * @property string|null $color
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read string $numero_cuenta_mascarado
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MovimientoBancario> $movimientos
 * @property-read int|null $movimientos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria activas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria banco(string $banco)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria whereActiva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria whereBanco($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria whereClabe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria whereMoneda($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria whereNumeroCuenta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria whereSaldoActual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria whereSaldoInicial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentaBancaria whereUpdatedAt($value)
 */
	class CuentaBancaria extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $venta_id
 * @property numeric $monto_total
 * @property numeric $monto_pagado
 * @property numeric $monto_pendiente
 * @property \Illuminate\Support\Carbon|null $fecha_vencimiento
 * @property string $estado
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property int|null $cuenta_bancaria_id
 * @property int|null $cobrable_id
 * @property string|null $cobrable_type
 * @property int|null $cfdi_id
 * @property int|null $cliente_id
 * @property int|null $empresa_id
 * @property-read \App\Models\Cfdi|null $cfdi
 * @property-read \App\Models\Cliente|null $cliente
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $cobrable
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\CuentaBancaria|null $cuentaBancaria
 * @property-read \App\Models\User|null $deletedBy
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MovimientoBancario> $movimientosBancarios
 * @property-read int|null $movimientos_bancarios_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RecordatorioCobranza> $recordatorios
 * @property-read int|null $recordatorios_count
 * @property-read \App\Models\User|null $updatedBy
 * @property-read \App\Models\Venta|null $venta
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar pendientes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar vencidas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereCfdiId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereCobrableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereCobrableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereCuentaBancariaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereFechaVencimiento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereMontoPagado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereMontoPendiente($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereMontoTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar whereVentaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorCobrar withoutTrashed()
 */
	class CuentasPorCobrar extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $compra_id
 * @property numeric $monto_total
 * @property numeric $monto_pagado
 * @property numeric $monto_pendiente
 * @property \Illuminate\Support\Carbon|null $fecha_vencimiento
 * @property string $estado
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $pagado
 * @property string|null $metodo_pago
 * @property \Illuminate\Support\Carbon|null $fecha_pago
 * @property int|null $pagado_por
 * @property string|null $notas_pago
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property int|null $cuenta_bancaria_id
 * @property int|null $cfdi_id
 * @property int|null $proveedor_id
 * @property bool $pagado_con_rep
 * @property bool $pue_pagado
 * @property int|null $empresa_id
 * @property-read \App\Models\Cfdi|null $cfdi
 * @property-read \App\Models\Compra|null $compra
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\CuentaBancaria|null $cuentaBancaria
 * @property-read \App\Models\User|null $deletedBy
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\User|null $pagadoPor
 * @property-read \App\Models\Proveedor|null $proveedor
 * @property-read \App\Models\User|null $updatedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar pendientes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar vencidas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereCfdiId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereCompraId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereCuentaBancariaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereFechaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereFechaVencimiento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereMetodoPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereMontoPagado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereMontoPendiente($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereMontoTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereNotasPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar wherePagado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar wherePagadoConRep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar wherePagadoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereProveedorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar wherePuePagado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuentasPorPagar withoutTrashed()
 */
	class CuentasPorPagar extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $asignacion_masiva_id
 * @property int $herramienta_id
 * @property string $estado_individual
 * @property \Illuminate\Support\Carbon $fecha_asignacion_individual
 * @property \Illuminate\Support\Carbon|null $fecha_devolucion_individual
 * @property string|null $observaciones_asignacion
 * @property string|null $observaciones_devolucion
 * @property string|null $estado_herramienta_entrega
 * @property string|null $estado_herramienta_devolucion
 * @property string|null $foto_estado_entrega
 * @property string|null $foto_estado_devolucion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\AsignacionMasiva|null $asignacionMasiva
 * @property-read mixed $duracion_en_dias
 * @property-read mixed $estado_color
 * @property-read mixed $estado_label
 * @property-read \App\Models\Herramienta|null $herramienta
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva asignadas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva dañadas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva devueltas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva perdidas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva whereAsignacionMasivaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva whereEstadoHerramientaDevolucion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva whereEstadoHerramientaEntrega($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva whereEstadoIndividual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva whereFechaAsignacionIndividual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva whereFechaDevolucionIndividual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva whereFotoEstadoDevolucion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva whereFotoEstadoEntrega($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva whereHerramientaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva whereObservacionesAsignacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva whereObservacionesDevolucion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleAsignacionMasiva whereUpdatedAt($value)
 */
	class DetalleAsignacionMasiva extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $empresa_id
 * @property int|null $tecnico_id
 * @property \Illuminate\Support\Carbon $fecha
 * @property string|null $motivo Vacaciones, día festivo, capacitación, etc.
 * @property \Illuminate\Support\Carbon|null $hora_inicio Null = todo el día
 * @property \Illuminate\Support\Carbon|null $hora_fin
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Empresa $empresa
 * @property-read bool $es_todo_dia
 * @property-read string $rango_bloqueado
 * @property-read \App\Models\User|null $tecnico
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaBloqueado delMes(int $mes, int $año)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaBloqueado fecha($fecha)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaBloqueado futuros()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaBloqueado newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaBloqueado newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaBloqueado porTecnico(?int $tecnicoId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaBloqueado query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaBloqueado whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaBloqueado whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaBloqueado whereFecha($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaBloqueado whereHoraFin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaBloqueado whereHoraInicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaBloqueado whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaBloqueado whereMotivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaBloqueado whereTecnicoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaBloqueado whereUpdatedAt($value)
 */
	class DiaBloqueado extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $empresa_id
 * @property int $tecnico_id
 * @property int $dia_semana 0=Dom, 1=Lun, 2=Mar, 3=Mie, 4=Jue, 5=Vie, 6=Sab
 * @property \Illuminate\Support\Carbon $hora_inicio
 * @property \Illuminate\Support\Carbon $hora_fin
 * @property int $max_citas_dia Máximo de citas que puede tener este día
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Empresa $empresa
 * @property-read string $nombre_dia
 * @property-read string $rango_horario
 * @property-read \App\Models\User $tecnico
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadTecnico activo()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadTecnico dia(int $dia)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadTecnico newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadTecnico newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadTecnico porTecnico(int $tecnicoId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadTecnico query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadTecnico whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadTecnico whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadTecnico whereDiaSemana($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadTecnico whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadTecnico whereHoraFin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadTecnico whereHoraInicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadTecnico whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadTecnico whereMaxCitasDia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadTecnico whereTecnicoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadTecnico whereUpdatedAt($value)
 */
	class DisponibilidadTecnico extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre_razon_social
 * @property string $tipo_persona
 * @property string|null $tipo_identificacion
 * @property string|null $identificacion
 * @property string|null $curp
 * @property string $rfc
 * @property string $regimen_fiscal
 * @property string $uso_cfdi
 * @property string $email
 * @property string|null $telefono
 * @property string $calle
 * @property string $numero_exterior
 * @property string|null $numero_interior
 * @property string $colonia
 * @property string $codigo_postal
 * @property string $municipio
 * @property string $estado
 * @property string $pais
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $whatsapp_enabled
 * @property string|null $whatsapp_business_account_id
 * @property string|null $whatsapp_phone_number_id
 * @property string|null $whatsapp_sender_phone
 * @property string|null $whatsapp_access_token
 * @property string|null $whatsapp_app_secret
 * @property string|null $whatsapp_webhook_verify_token
 * @property string $whatsapp_default_language
 * @property string|null $whatsapp_template_payment_reminder
 * @property string|null $whatsapp_template_maintenance
 * @property-read string $ciudad
 * @property-read string $direccion_completa
 * @property-read string $nombre_empresa
 * @method static \Database\Factories\EmpresaFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereCalle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereCodigoPostal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereColonia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereCurp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereIdentificacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereMunicipio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereNombreRazonSocial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereNumeroExterior($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereNumeroInterior($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa wherePais($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereRegimenFiscal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereRfc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereTelefono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereTipoIdentificacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereTipoPersona($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereUsoCfdi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereWhatsappAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereWhatsappAppSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereWhatsappBusinessAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereWhatsappDefaultLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereWhatsappEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereWhatsappPhoneNumberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereWhatsappSenderPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereWhatsappTemplateMaintenance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereWhatsappTemplatePaymentReminder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereWhatsappWebhookVerifyToken($value)
 */
	class Empresa extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre_empresa
 * @property string|null $rfc
 * @property string|null $razon_social
 * @property string|null $telefono
 * @property string|null $email
 * @property string|null $sitio_web
 * @property string|null $codigo_postal
 * @property string|null $ciudad
 * @property string|null $estado
 * @property string $pais
 * @property string|null $logo_path
 * @property string|null $favicon_path
 * @property string|null $descripcion_empresa
 * @property string $color_principal
 * @property string $color_secundario
 * @property string|null $pie_pagina_facturas
 * @property string|null $pie_pagina_cotizaciones
 * @property string|null $terminos_condiciones
 * @property string|null $politica_privacidad
 * @property numeric $iva_porcentaje
 * @property string $moneda
 * @property string $formato_numeros
 * @property bool $mantenimiento
 * @property string|null $mensaje_mantenimiento
 * @property bool $registro_usuarios
 * @property bool $notificaciones_email
 * @property string|null $logo_reportes
 * @property string $formato_fecha
 * @property string $formato_hora
 * @property bool $backup_automatico
 * @property int $frecuencia_backup
 * @property int $retencion_backups
 * @property int $intentos_login
 * @property int $tiempo_bloqueo
 * @property bool $requerir_2fa
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $calle
 * @property string|null $numero_exterior
 * @property string|null $numero_interior
 * @property string|null $banco
 * @property string|null $sucursal
 * @property string|null $cuenta
 * @property string|null $clabe
 * @property string|null $titular
 * @property string|null $numero_cuenta
 * @property string|null $numero_tarjeta
 * @property string|null $nombre_titular
 * @property string|null $informacion_adicional_bancaria
 * @property string|null $smtp_host
 * @property int|null $smtp_port
 * @property string|null $smtp_username
 * @property string|null $smtp_password
 * @property string|null $smtp_encryption
 * @property string|null $email_from_address
 * @property string|null $email_from_name
 * @property string|null $email_reply_to
 * @property string|null $dkim_selector
 * @property string|null $dkim_domain
 * @property string|null $dkim_public_key
 * @property bool $dkim_enabled
 * @property bool $dark_mode_enabled
 * @property string $dark_mode_primary_color
 * @property string $dark_mode_secondary_color
 * @property string $dark_mode_background_color
 * @property string $dark_mode_surface_color
 * @property string|null $colonia
 * @property numeric $isr_porcentaje
 * @property string|null $pie_pagina_ventas
 * @property bool $backup_cloud_enabled
 * @property string $backup_tipo
 * @property string $backup_hora_completo
 * @property string|null $email_cobros
 * @property string $cobros_hora_reporte
 * @property bool $cobros_reporte_automatico
 * @property int $cobros_dias_anticipacion
 * @property string|null $email_pagos
 * @property string $pagos_hora_reporte
 * @property bool $pagos_reporte_automatico
 * @property int $pagos_dias_anticipacion
 * @property bool $enable_isr
 * @property bool $enable_retencion_iva
 * @property bool $enable_retencion_isr
 * @property numeric $retencion_iva
 * @property numeric $retencion_isr
 * @property string|null $regimen_fiscal
 * @property string|null $fiel_cer_path
 * @property string|null $fiel_key_path
 * @property string|null $fiel_password
 * @property \Illuminate\Support\Carbon|null $fiel_valid_from
 * @property \Illuminate\Support\Carbon|null $fiel_valid_to
 * @property string|null $fiel_serial
 * @property string|null $fiel_rfc
 * @property string|null $csd_cer_path
 * @property string|null $csd_key_path
 * @property string|null $csd_password
 * @property \Illuminate\Support\Carbon|null $csd_valid_from
 * @property \Illuminate\Support\Carbon|null $csd_valid_to
 * @property string|null $csd_serial
 * @property string|null $csd_rfc
 * @property string|null $pac_nombre
 * @property string|null $pac_base_url
 * @property string|null $pac_apikey
 * @property string|null $dominio_principal
 * @property string|null $dominio_secundario
 * @property string|null $servidor_ipv4
 * @property string|null $servidor_ipv6
 * @property bool $ssl_enabled
 * @property string|null $ssl_certificado_path
 * @property string|null $ssl_key_path
 * @property string|null $ssl_ca_bundle_path
 * @property \Illuminate\Support\Carbon|null $ssl_fecha_expiracion
 * @property string|null $ssl_proveedor
 * @property string|null $app_url
 * @property bool $force_https
 * @property bool $zerotier_enabled
 * @property string|null $zerotier_network_id
 * @property string|null $zerotier_ip
 * @property string|null $zerotier_node_id
 * @property string|null $zerotier_notas
 * @property bool $pac_produccion
 * @property string|null $google_client_id
 * @property string|null $google_client_secret
 * @property string|null $microsoft_client_id
 * @property string|null $microsoft_client_secret
 * @property string|null $microsoft_tenant_id
 * @property string|null $mercadopago_access_token
 * @property string|null $mercadopago_public_key
 * @property bool $mercadopago_sandbox
 * @property string|null $paypal_client_id
 * @property string|null $paypal_client_secret
 * @property bool $paypal_sandbox
 * @property bool $tienda_online_activa
 * @property int|null $empresa_id
 * @property bool $mega_enabled Habilitar respaldos en MEGA
 * @property string|null $mega_email Email de cuenta MEGA
 * @property string|null $mega_password Password encriptado de MEGA
 * @property string $mega_folder Carpeta destino en MEGA
 * @property bool $mega_auto_backup Subir respaldos automáticamente
 * @property int $mega_retention_days Días de retención de respaldos en MEGA
 * @property string|null $mega_last_sync Última sincronización con MEGA
 * @property bool $gdrive_enabled Habilitar respaldos en Google Drive
 * @property string|null $gdrive_client_id Client ID de Google OAuth
 * @property string|null $gdrive_client_secret Client Secret de Google OAuth
 * @property string|null $gdrive_access_token Access token encriptado
 * @property string|null $gdrive_refresh_token Refresh token encriptado
 * @property string|null $gdrive_folder_id ID de carpeta en Google Drive
 * @property string $gdrive_folder_name Nombre de carpeta en Google Drive
 * @property bool $gdrive_auto_backup Subir respaldos automáticamente
 * @property \Illuminate\Support\Carbon|null $gdrive_token_expires_at Fecha de expiración del token
 * @property \Illuminate\Support\Carbon|null $gdrive_last_sync Última sincronización
 * @property string $cloud_provider Proveedor: none, mega, gdrive
 * @property bool $contpaqi_enabled
 * @property string|null $contpaqi_bridge_url
 * @property string|null $contpaqi_ruta_empresa
 * @property string|null $contpaqi_codigo_concepto
 * @property string|null $color_terciario
 * @property string|null $stripe_public_key
 * @property string|null $stripe_secret_key
 * @property string|null $stripe_webhook_secret
 * @property bool $stripe_sandbox
 * @property string|null $facebook_url
 * @property string|null $instagram_url
 * @property string|null $twitter_url
 * @property string|null $tiktok_url
 * @property string|null $youtube_url
 * @property string|null $linkedin_url
 * @property int|null $cuenta_id_paypal
 * @property int|null $cuenta_id_mercadopago
 * @property int|null $cuenta_id_stripe
 * @property string|null $hero_titulo
 * @property string|null $hero_subtitulo
 * @property string|null $hero_descripcion
 * @property string|null $hero_cta_primario
 * @property string|null $hero_cta_primario_url
 * @property string|null $hero_cta_secundario
 * @property string|null $hero_cta_secundario_url
 * @property string|null $hero_imagen_url
 * @property string|null $hero_badge_texto
 * @property bool $cva_active
 * @property string|null $cva_user
 * @property string|null $cva_password
 * @property numeric $cva_utility_percentage
 * @property int|null $cva_codigo_sucursal
 * @property int|null $cva_paqueteria_envio
 * @property array<array-key, mixed>|null $cva_utility_tiers
 * @property string|null $shipping_local_cp_prefix Prefijo de CP para envío local
 * @property numeric $shipping_local_cost Costo de envío local en MXN
 * @property string|null $n8n_webhook_blog
 * @property string|null $groq_api_key
 * @property string|null $ai_provider
 * @property string|null $groq_model
 * @property string|null $ollama_base_url
 * @property string|null $ollama_model
 * @property numeric|null $chatbot_temperature
 * @property string|null $chatbot_system_prompt
 * @property int $dias_gracia_corte Días de gracia después del vencimiento antes de bloquear el portal
 * @property numeric $costo_promedio_hora_tecnico
 * @property string|null $whatsapp
 * @property numeric $cva_tipo_cambio
 * @property numeric $cva_tipo_cambio_buffer
 * @property bool $cva_tipo_cambio_auto
 * @property \Illuminate\Support\Carbon|null $cva_tipo_cambio_last_update
 * @property bool $cva_auto_pago
 * @property numeric $cva_monedero_balance
 * @property \Illuminate\Support\Carbon|null $cva_monedero_last_update
 * @property string|null $blog_robot_token
 * @property bool $blog_robot_enabled
 * @property int|null $ticket_default_assignee_id
 * @property bool $biometrics_strict_match
 * @property numeric $biometrics_local_match_threshold
 * @property numeric $biometrics_local_liveness_threshold
 * @property int $biometrics_geofence_soft_margin_meters
 * @property numeric $biometrics_nearby_match_relax
 * @property numeric $biometrics_nearby_liveness_relax
 * @property numeric $biometrics_far_match_penalty
 * @property numeric $biometrics_far_liveness_penalty
 * @property int $minutos_tolerancia_retardo
 * @property string|null $rustdesk_server_address
 * @property string|null $rustdesk_relay_server
 * @property string|null $rustdesk_public_key
 * @property string|null $rustdesk_api_url
 * @property string|null $rustdesk_api_token
 * @property bool $images_webp_enabled
 * @property int $images_webp_quality
 * @property bool $bloqueo_portal_por_deuda Si es falso, no se bloqueará el portal a pesar de tener deudas vencidas
 * @property string|null $gemini_api_key
 * @property string|null $gemini_model
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read mixed $direccion_completa
 * @property-read mixed $favicon_url
 * @property-read mixed $logo_reportes_url
 * @property-read mixed $logo_url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereAiProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereAppUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereBackupAutomatico($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereBackupCloudEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereBackupHoraCompleto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereBackupTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereBanco($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereBiometricsFarLivenessPenalty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereBiometricsFarMatchPenalty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereBiometricsGeofenceSoftMarginMeters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereBiometricsLocalLivenessThreshold($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereBiometricsLocalMatchThreshold($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereBiometricsNearbyLivenessRelax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereBiometricsNearbyMatchRelax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereBiometricsStrictMatch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereBlogRobotEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereBlogRobotToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereBloqueoPortalPorDeuda($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCalle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereChatbotSystemPrompt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereChatbotTemperature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCiudad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereClabe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCloudProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCobrosDiasAnticipacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCobrosHoraReporte($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCobrosReporteAutomatico($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCodigoPostal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereColonia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereColorPrincipal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereColorSecundario($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereColorTerciario($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereContpaqiBridgeUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereContpaqiCodigoConcepto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereContpaqiEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereContpaqiRutaEmpresa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCostoPromedioHoraTecnico($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCsdCerPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCsdKeyPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCsdPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCsdRfc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCsdSerial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCsdValidFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCsdValidTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCuenta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCuentaIdMercadopago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCuentaIdPaypal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCuentaIdStripe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCvaActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCvaAutoPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCvaCodigoSucursal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCvaMonederoBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCvaMonederoLastUpdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCvaPaqueteriaEnvio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCvaPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCvaTipoCambio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCvaTipoCambioAuto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCvaTipoCambioBuffer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCvaTipoCambioLastUpdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCvaUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCvaUtilityPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereCvaUtilityTiers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereDarkModeBackgroundColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereDarkModeEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereDarkModePrimaryColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereDarkModeSecondaryColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereDarkModeSurfaceColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereDescripcionEmpresa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereDiasGraciaCorte($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereDkimDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereDkimEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereDkimPublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereDkimSelector($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereDominioPrincipal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereDominioSecundario($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereEmailCobros($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereEmailFromAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereEmailFromName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereEmailPagos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereEmailReplyTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereEnableIsr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereEnableRetencionIsr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereEnableRetencionIva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereFacebookUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereFaviconPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereFielCerPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereFielKeyPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereFielPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereFielRfc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereFielSerial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereFielValidFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereFielValidTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereForceHttps($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereFormatoFecha($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereFormatoHora($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereFormatoNumeros($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereFrecuenciaBackup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereGdriveAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereGdriveAutoBackup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereGdriveClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereGdriveClientSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereGdriveEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereGdriveFolderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereGdriveFolderName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereGdriveLastSync($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereGdriveRefreshToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereGdriveTokenExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereGeminiApiKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereGeminiModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereGoogleClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereGoogleClientSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereGroqApiKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereGroqModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereHeroBadgeTexto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereHeroCtaPrimario($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereHeroCtaPrimarioUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereHeroCtaSecundario($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereHeroCtaSecundarioUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereHeroDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereHeroImagenUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereHeroSubtitulo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereHeroTitulo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereImagesWebpEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereImagesWebpQuality($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereInformacionAdicionalBancaria($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereInstagramUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereIntentosLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereIsrPorcentaje($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereIvaPorcentaje($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereLinkedinUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereLogoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereLogoReportes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereMantenimiento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereMegaAutoBackup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereMegaEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereMegaEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereMegaFolder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereMegaLastSync($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereMegaPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereMegaRetentionDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereMensajeMantenimiento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereMercadopagoAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereMercadopagoPublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereMercadopagoSandbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereMicrosoftClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereMicrosoftClientSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereMicrosoftTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereMinutosToleranciaRetardo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereMoneda($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereN8nWebhookBlog($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereNombreEmpresa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereNombreTitular($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereNotificacionesEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereNumeroCuenta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereNumeroExterior($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereNumeroInterior($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereNumeroTarjeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereOllamaBaseUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereOllamaModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion wherePacApikey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion wherePacBaseUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion wherePacNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion wherePacProduccion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion wherePagosDiasAnticipacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion wherePagosHoraReporte($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion wherePagosReporteAutomatico($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion wherePais($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion wherePaypalClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion wherePaypalClientSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion wherePaypalSandbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion wherePiePaginaCotizaciones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion wherePiePaginaFacturas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion wherePiePaginaVentas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion wherePoliticaPrivacidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereRazonSocial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereRegimenFiscal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereRegistroUsuarios($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereRequerir2fa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereRetencionBackups($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereRetencionIsr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereRetencionIva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereRfc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereRustdeskApiToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereRustdeskApiUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereRustdeskPublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereRustdeskRelayServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereRustdeskServerAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereServidorIpv4($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereServidorIpv6($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereShippingLocalCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereShippingLocalCpPrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereSitioWeb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereSmtpEncryption($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereSmtpHost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereSmtpPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereSmtpPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereSmtpUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereSslCaBundlePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereSslCertificadoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereSslEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereSslFechaExpiracion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereSslKeyPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereSslProveedor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereStripePublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereStripeSandbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereStripeSecretKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereStripeWebhookSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereSucursal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereTelefono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereTerminosCondiciones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereTicketDefaultAssigneeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereTiempoBloqueo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereTiendaOnlineActiva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereTiktokUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereTitular($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereTwitterUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereWhatsapp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereYoutubeUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereZerotierEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereZerotierIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereZerotierNetworkId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereZerotierNodeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaConfiguracion whereZerotierNotas($value)
 */
	class EmpresaConfiguracion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $fecha_entrega
 * @property numeric $monto_efectivo
 * @property numeric $monto_cheques
 * @property numeric $monto_tarjetas
 * @property numeric $total
 * @property string $estado
 * @property string|null $notas
 * @property int|null $recibido_por
 * @property \Illuminate\Support\Carbon|null $fecha_recibido
 * @property string|null $notas_recibido
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $tipo_origen
 * @property int|null $id_origen
 * @property bool $entregado_responsable
 * @property \Illuminate\Support\Carbon|null $fecha_entregado_responsable
 * @property string|null $responsable_organizacion
 * @property string|null $notas_entrega_responsable
 * @property numeric $monto_transferencia
 * @property numeric $monto_otros
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property int|null $cuenta_bancaria_id
 * @property int|null $empresa_id
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\CuentaBancaria|null $cuentaBancaria
 * @property-read \App\Models\User|null $deletedBy
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $origen
 * @property-read \App\Models\User|null $recibidoPor
 * @property-read \App\Models\User|null $updatedBy
 * @property-read \App\Models\User|null $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero entregadasResponsable()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero pendientes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero pendientesResponsable()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero recibidas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereCuentaBancariaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereEntregadoResponsable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereFechaEntrega($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereFechaEntregadoResponsable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereFechaRecibido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereIdOrigen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereMontoCheques($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereMontoEfectivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereMontoOtros($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereMontoTarjetas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereMontoTransferencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereNotasEntregaResponsable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereNotasRecibido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereRecibidoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereResponsableOrganizacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereTipoOrigen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EntregaDinero withoutTrashed()
 */
	class EntregaDinero extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property string|null $marca
 * @property string|null $modelo
 * @property string|null $numero_serie
 * @property string|null $descripcion
 * @property array<array-key, mixed>|null $especificaciones
 * @property numeric $precio_renta_mensual
 * @property numeric|null $precio_compra
 * @property \Illuminate\Support\Carbon|null $fecha_adquisicion
 * @property string $estado
 * @property string $condicion
 * @property string|null $ubicacion_fisica
 * @property string|null $notas
 * @property array<array-key, mixed>|null $accesorios
 * @property \Illuminate\Support\Carbon|null $fecha_garantia
 * @property string|null $proveedor
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $imagen
 * @property int|null $empresa_id
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read string|null $serie
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Renta> $rentas
 * @property-read int|null $rentas_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereAccesorios($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereCondicion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereEspecificaciones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereFechaAdquisicion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereFechaGarantia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereImagen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereMarca($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereModelo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereNumeroSerie($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo wherePrecioCompra($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo wherePrecioRentaMensual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereProveedor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereUbicacionFisica($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo withoutTrashed()
 */
	class Equipo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read mixed $color_condicion
 * @property-read mixed $color_prioridad
 * @property-read mixed $dias_desde_inspeccion
 * @property-read mixed $label_condicion
 * @property-read mixed $label_prioridad
 * @property-read \App\Models\Herramienta|null $herramienta
 * @property-read \App\Models\User|null $inspeccionadoPor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoHerramienta desgasteMayorA($porcentaje)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoHerramienta necesitanMantenimiento()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoHerramienta necesitanReemplazo()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoHerramienta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoHerramienta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoHerramienta porCondicion($condicion)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoHerramienta porPrioridad($prioridad)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoHerramienta query()
 */
	class EstadoHerramienta extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $numero_factura
 * @property int $cliente_id
 * @property numeric $subtotal
 * @property numeric $iva
 * @property numeric $total
 * @property \Illuminate\Support\Carbon $fecha_emision
 * @property string $estado
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $empresa_id
 * @property string|null $folio
 * @property string|null $fecha_vencimiento
 * @property-read \App\Models\Cfdi|null $cfdi
 * @property-read \App\Models\Cliente|null $cliente
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VentaItem> $itemsViaVentas
 * @property-read int|null $items_via_ventas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Venta> $ventas
 * @property-read int|null $ventas_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura estado($estado)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereFechaEmision($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereFechaVencimiento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereFolio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereIva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereNumeroFactura($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura withoutTrashed()
 */
	class Factura extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $document_type
 * @property string|null $prefix
 * @property int $current_number
 * @property int $padding
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \App\Models\Empresa|null $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FolioConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FolioConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FolioConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FolioConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FolioConfig whereCurrentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FolioConfig whereDocumentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FolioConfig whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FolioConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FolioConfig wherePadding($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FolioConfig wherePrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FolioConfig whereUpdatedAt($value)
 */
	class FolioConfig extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string|null $route_name Nombre de la ruta Vue o URL
 * @property string|null $descripcion
 * @property array<array-key, mixed>|null $checklist_default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuiaTecnica newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuiaTecnica newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuiaTecnica query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuiaTecnica whereChecklistDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuiaTecnica whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuiaTecnica whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuiaTecnica whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuiaTecnica whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuiaTecnica whereRouteName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuiaTecnica whereUpdatedAt($value)
 */
	class GuiaTecnica extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string $numero_serie
 * @property string|null $foto
 * @property int|null $tecnico_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $estado
 * @property int|null $vida_util_meses
 * @property \Illuminate\Support\Carbon|null $fecha_ultimo_mantenimiento
 * @property numeric|null $costo_reemplazo
 * @property string|null $categoria
 * @property string|null $descripcion
 * @property bool $requiere_mantenimiento
 * @property int|null $dias_para_mantenimiento
 * @property \Illuminate\Support\Carbon|null $fecha_asignacion
 * @property \Illuminate\Support\Carbon|null $fecha_recepcion
 * @property int|null $categoria_id
 * @property int|null $user_id
 * @property int|null $empresa_id
 * @property string|null $codigo_inventario
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AsignacionMasiva> $asignacionesMasivas
 * @property-read int|null $asignaciones_masivas_count
 * @property-read \App\Models\CategoriaHerramienta|null $categoriaHerramienta
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DetalleAsignacionMasiva> $detallesAsignacionesMasivas
 * @property-read int|null $detalles_asignaciones_masivas_count
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EstadoHerramienta> $estados
 * @property-read int|null $estados_count
 * @property-read mixed $asignacion_activa
 * @property-read mixed $asignacion_masiva_activa
 * @property-read mixed $categoria_label
 * @property-read mixed $dias_desde_ultimo_mantenimiento
 * @property-read mixed $dias_para_proximo_mantenimiento
 * @property-read mixed $esta_en_asignacion_masiva
 * @property-read mixed $estadisticas
 * @property-read mixed $estado_color
 * @property-read mixed $estado_label
 * @property-read mixed $historial_completo
 * @property-read mixed $info_asignacion_completa
 * @property-read mixed $porcentaje_vida_util
 * @property-read mixed $ultimo_estado
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HistorialHerramienta> $historial
 * @property-read int|null $historial_count
 * @property-read \App\Models\User|null $tecnico
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta asignadas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta disponibles()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta enMantenimiento()
 * @method static \Database\Factories\HerramientaFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta mantenimientoProximo()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta porCategoria($categoria)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta requierenMantenimiento()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta requierenMantenimientoUrgente()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta vidaUtilProximaAVencer()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta vidaUtilVencida()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereCategoria($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereCategoriaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereCodigoInventario($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereCostoReemplazo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereDiasParaMantenimiento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereFechaAsignacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereFechaRecepcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereFechaUltimoMantenimiento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereFoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereNumeroSerie($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereRequiereMantenimiento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereTecnicoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Herramienta whereVidaUtilMeses($value)
 */
	class Herramienta extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $herramienta_id
 * @property int $tecnico_id
 * @property \Illuminate\Support\Carbon $fecha_asignacion
 * @property \Illuminate\Support\Carbon|null $fecha_devolucion
 * @property int|null $asignado_por
 * @property int|null $recibido_por
 * @property string|null $observaciones_asignacion
 * @property string|null $observaciones_devolucion
 * @property string|null $motivo_devolucion
 * @property string|null $estado_herramienta_asignacion
 * @property string|null $estado_herramienta_devolucion
 * @property int|null $duracion_dias
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $asignacion_masiva_id
 * @property string|null $codigo_asignacion
 * @property string|null $proyecto_trabajo
 * @property string $tipo_asignacion
 * @property int|null $user_id
 * @property int|null $empresa_id
 * @property-read \App\Models\User|null $asignadoPor
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read mixed $color_estado
 * @property-read mixed $color_motivo_devolucion
 * @property-read mixed $duracion_en_dias
 * @property-read mixed $label_estado
 * @property-read mixed $label_motivo_devolucion
 * @property-read \App\Models\Herramienta|null $herramienta
 * @property-read \App\Models\User|null $recibidoPor
 * @property-read \App\Models\User|null $tecnico
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta activos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta completados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta porEstado($estado)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta porHerramienta($herramientaId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta porMotivoDevolucion($motivo)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta porTecnico($tecnicoId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereAsignacionMasivaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereAsignadoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereCodigoAsignacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereDuracionDias($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereEstadoHerramientaAsignacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereEstadoHerramientaDevolucion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereFechaAsignacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereFechaDevolucion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereHerramientaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereMotivoDevolucion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereObservacionesAsignacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereObservacionesDevolucion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereProyectoTrabajo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereRecibidoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereTecnicoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereTipoAsignacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialHerramienta whereUserId($value)
 */
	class HistorialHerramienta extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $pago_prestamo_id
 * @property int $prestamo_id
 * @property numeric $monto_pagado
 * @property \Illuminate\Support\Carbon $fecha_pago
 * @property \Illuminate\Support\Carbon $fecha_registro
 * @property string|null $metodo_pago
 * @property string|null $referencia
 * @property string|null $notas
 * @property bool $confirmado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $cuenta_bancaria_id
 * @property int|null $empresa_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \OwenIt\Auditing\Models\Audit> $audits
 * @property-read int|null $audits_count
 * @property-read \App\Models\CuentaBancaria|null $cuentaBancaria
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read string $metodo_pago_texto
 * @property-read \App\Models\PagoPrestamo|null $pagoPrestamo
 * @property-read \App\Models\Prestamo|null $prestamo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo confirmados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo ordenCronologico()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo porPago($pagoId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo porPrestamo($prestamoId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo whereConfirmado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo whereCuentaBancariaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo whereFechaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo whereFechaRegistro($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo whereMetodoPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo whereMontoPagado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo wherePagoPrestamoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo wherePrestamoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo whereReferencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialPagoPrestamo withoutTrashed()
 */
	class HistorialPagoPrestamo extends \Eloquent implements \OwenIt\Auditing\Contracts\Auditable {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $producto_id
 * @property int $cantidad
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $almacen_id
 * @property int $stock_minimo
 * @property int|null $empresa_id
 * @property-read \App\Models\Almacen|null $almacen
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\Producto|null $producto
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventario newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventario newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventario query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventario whereAlmacenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventario whereCantidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventario whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventario whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventario whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventario whereProductoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventario whereStockMinimo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventario whereUpdatedAt($value)
 */
	class Inventario extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $producto_id
 * @property string $tipo
 * @property int $cantidad
 * @property string $motivo
 * @property \Illuminate\Database\Eloquent\Model|null $referencia
 * @property int $user_id
 * @property string|null $metadatos
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $stock_anterior
 * @property int|null $stock_posterior
 * @property string|null $referencia_type
 * @property int|null $referencia_id
 * @property array<array-key, mixed>|null $detalles
 * @property int|null $almacen_id
 * @property int|null $lote_id
 * @property string|null $producto_nombre
 * @property string|null $almacen_nombre
 * @property string|null $usuario_nombre
 * @property int|null $empresa_id
 * @property-read \App\Models\Almacen|null $almacen
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\Lote|null $lote
 * @property-read \App\Models\Producto|null $producto
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereAlmacenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereAlmacenNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereCantidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereDetalles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereLoteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereMetadatos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereMotivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereProductoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereProductoNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereReferencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereReferenciaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereReferenciaType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereStockAnterior($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereStockPosterior($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventarioMovimiento whereUsuarioNombre($value)
 */
	class InventarioMovimiento extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $kit_id
 * @property int $cantidad
 * @property numeric|null $precio_unitario
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string $item_type
 * @property int $item_id
 * @property-read float $subtotal
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $item
 * @property-read \App\Models\Producto|null $kit
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KitItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KitItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KitItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KitItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KitItem whereCantidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KitItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KitItem whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KitItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KitItem whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KitItem whereItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KitItem whereKitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KitItem wherePrecioUnitario($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KitItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KitItem withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KitItem withoutTrashed()
 */
	class KitItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $empresa_id
 * @property int|null $categoria_id
 * @property int $user_id
 * @property string $titulo
 * @property string $slug
 * @property string $contenido
 * @property string|null $resumen
 * @property array<array-key, mixed>|null $tags
 * @property int $vistas
 * @property int $util_si
 * @property int $util_no
 * @property bool $destacado
 * @property bool $publicado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $autor
 * @property-read \App\Models\TicketCategory|null $categoria
 * @property-read \App\Models\Empresa $empresa
 * @property-read int $porcentaje_util
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle buscar($termino)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle destacados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle populares()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle publicados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle whereCategoriaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle whereContenido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle whereDestacado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle wherePublicado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle whereResumen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle whereTitulo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle whereUtilNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle whereUtilSi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseArticle whereVistas($value)
 */
	class KnowledgeBaseArticle extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $empresa_id
 * @property string $pregunta
 * @property string $respuesta
 * @property int $orden
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Empresa $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingFaq activo()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingFaq newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingFaq newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingFaq ordenado()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingFaq query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingFaq whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingFaq whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingFaq whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingFaq whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingFaq whereOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingFaq wherePregunta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingFaq whereRespuesta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingFaq whereUpdatedAt($value)
 */
	class LandingFaq extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $empresa_id
 * @property string $nombre_empresa
 * @property string $logo
 * @property string|null $url
 * @property int $orden
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Empresa $empresa
 * @property-read mixed $logo_url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingLogoCliente activo()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingLogoCliente newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingLogoCliente newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingLogoCliente ordenado()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingLogoCliente query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingLogoCliente whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingLogoCliente whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingLogoCliente whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingLogoCliente whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingLogoCliente whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingLogoCliente whereNombreEmpresa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingLogoCliente whereOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingLogoCliente whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingLogoCliente whereUrl($value)
 */
	class LandingLogoCliente extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string|null $logo
 * @property string $tipo
 * @property string|null $texto_auxiliar
 * @property string|null $url
 * @property int $orden
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read mixed $logo_url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingMarcaAutorizada activo()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingMarcaAutorizada newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingMarcaAutorizada newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingMarcaAutorizada ordenado()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingMarcaAutorizada query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingMarcaAutorizada whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingMarcaAutorizada whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingMarcaAutorizada whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingMarcaAutorizada whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingMarcaAutorizada whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingMarcaAutorizada whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingMarcaAutorizada whereOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingMarcaAutorizada whereTextoAuxiliar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingMarcaAutorizada whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingMarcaAutorizada whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingMarcaAutorizada whereUrl($value)
 */
	class LandingMarcaAutorizada extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $empresa_id
 * @property string $titulo
 * @property string $subtitulo
 * @property string|null $descripcion
 * @property int $descuento_porcentaje
 * @property numeric $precio_original
 * @property numeric|null $precio_oferta
 * @property string|null $caracteristica_1
 * @property string|null $caracteristica_2
 * @property string|null $caracteristica_3
 * @property \Illuminate\Support\Carbon|null $fecha_inicio
 * @property \Illuminate\Support\Carbon|null $fecha_fin
 * @property bool $activo
 * @property int $orden
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Empresa $empresa
 * @property-read mixed $caracteristicas
 * @property-read mixed $tiempo_restante
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta activo()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta ordenado()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta vigente()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta whereCaracteristica1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta whereCaracteristica2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta whereCaracteristica3($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta whereDescuentoPorcentaje($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta whereFechaFin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta whereFechaInicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta whereOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta wherePrecioOferta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta wherePrecioOriginal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta whereSubtitulo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta whereTitulo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingOferta whereUpdatedAt($value)
 */
	class LandingOferta extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $titulo
 * @property string|null $descripcion
 * @property string|null $icono
 * @property string $tipo
 * @property int $orden
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \App\Models\Empresa|null $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingProceso activo()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingProceso newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingProceso newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingProceso ordenado()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingProceso query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingProceso whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingProceso whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingProceso whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingProceso whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingProceso whereIcono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingProceso whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingProceso whereOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingProceso whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingProceso whereTitulo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingProceso whereUpdatedAt($value)
 */
	class LandingProceso extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $empresa_id
 * @property string $nombre
 * @property string|null $cargo
 * @property string|null $empresa_cliente
 * @property string $comentario
 * @property int $calificacion
 * @property string|null $foto
 * @property int $orden
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Empresa $empresa
 * @property-read mixed $contenido
 * @property-read mixed $entidad
 * @property-read mixed $foto_url
 * @property-read mixed $iniciales
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingTestimonio activo()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingTestimonio newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingTestimonio newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingTestimonio ordenado()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingTestimonio query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingTestimonio whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingTestimonio whereCalificacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingTestimonio whereCargo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingTestimonio whereComentario($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingTestimonio whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingTestimonio whereEmpresaCliente($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingTestimonio whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingTestimonio whereFoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingTestimonio whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingTestimonio whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingTestimonio whereOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingTestimonio whereUpdatedAt($value)
 */
	class LandingTestimonio extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $producto_id
 * @property string $numero_lote
 * @property \Illuminate\Support\Carbon|null $fecha_caducidad
 * @property int $cantidad_inicial
 * @property int $cantidad_actual
 * @property numeric|null $costo_unitario
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $almacen_id
 * @property string $fecha_entrada
 * @property int $cantidad_disponible
 * @property int|null $empresa_id
 * @property-read \App\Models\Almacen|null $almacen
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InventarioMovimiento> $movimientos
 * @property-read int|null $movimientos_count
 * @property-read \App\Models\Producto|null $producto
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lote activos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lote noCaducados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lote whereAlmacenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lote whereCantidadActual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lote whereCantidadDisponible($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lote whereCantidadInicial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lote whereCostoUnitario($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lote whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lote whereFechaCaducidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lote whereFechaEntrada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lote whereNumeroLote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lote whereProductoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lote whereUpdatedAt($value)
 */
	class Lote extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $carro_id
 * @property string $tipo
 * @property \Illuminate\Support\Carbon $fecha
 * @property \Illuminate\Support\Carbon|null $proximo_mantenimiento
 * @property string|null $descripcion
 * @property string|null $notas
 * @property numeric $costo
 * @property string $estado
 * @property int|null $kilometraje_actual
 * @property int|null $proximo_kilometraje
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $prioridad
 * @property bool $alerta_enviada
 * @property string|null $alerta_enviada_at
 * @property int $dias_anticipacion_alerta
 * @property string|null $observaciones_alerta
 * @property bool $requiere_aprobacion
 * @property string $tipo_alerta
 * @property string|null $recordatorios_enviados
 * @property int $frecuencia_recordatorio_dias
 * @property int $km_anticipacion_alerta
 * @property string|null $folio
 * @property-read \App\Models\Carro|null $carro
 * @property-read mixed $costo_formateado
 * @property-read mixed $dias_restantes
 * @property-read mixed $estado_formateado
 * @property-read mixed $prioridad_formateada
 * @property-read mixed $requiere_alerta
 * @property-read \App\Models\User|null $tecnico
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento activos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento carro($carroId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento conAlertasPendientes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento estado($estado)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento proximosAVencer($dias = 30)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento tipo($tipo)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento vencidos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereAlertaEnviada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereAlertaEnviadaAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereCarroId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereCosto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereDiasAnticipacionAlerta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereFecha($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereFolio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereFrecuenciaRecordatorioDias($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereKilometrajeActual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereKmAnticipacionAlerta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereObservacionesAlerta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento wherePrioridad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereProximoKilometraje($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereProximoMantenimiento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereRecordatoriosEnviados($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereRequiereAprobacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereTipoAlerta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento whereUpdatedAt($value)
 */
	class Mantenimiento extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $estado
 * @property int|null $empresa_id
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Producto> $productos
 * @property-read int|null $productos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Marca activas()
 * @method static \Database\Factories\MarcaFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Marca inactivas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Marca newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Marca newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Marca query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Marca whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Marca whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Marca whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Marca whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Marca whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Marca whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Marca whereUpdatedAt($value)
 */
	class Marca extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property string|null $role
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereUserId($value)
 */
	class Membership extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property \Illuminate\Support\Carbon $fecha
 * @property string|null $concepto
 * @property string|null $referencia
 * @property numeric $monto
 * @property numeric|null $saldo
 * @property string $tipo
 * @property string $banco
 * @property string|null $cuenta_bancaria
 * @property string $estado
 * @property string|null $conciliable_type
 * @property int|null $conciliable_id
 * @property string|null $archivo_origen
 * @property int|null $usuario_id
 * @property int|null $conciliado_por
 * @property \Illuminate\Support\Carbon|null $conciliado_at
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $cuenta_bancaria_id
 * @property string|null $origen_tipo
 * @property int|null $empresa_id
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $conciliable
 * @property-read \App\Models\User|null $conciliadoPor
 * @property-read \App\Models\CuentaBancaria|null $cuentaBancaria
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read float $monto_absoluto
 * @property-read \App\Models\User|null $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario banco(string $banco)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario conciliados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario depositos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario ignorados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario pendientes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario retiros()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereArchivoOrigen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereBanco($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereConcepto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereConciliableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereConciliableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereConciliadoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereConciliadoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereCuentaBancaria($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereCuentaBancariaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereFecha($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereOrigenTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereReferencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereSaldo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoBancario whereUsuarioId($value)
 */
	class MovimientoBancario extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $producto_id
 * @property int $almacen_id
 * @property int $user_id
 * @property string $tipo
 * @property int $cantidad
 * @property numeric|null $costo_unitario
 * @property numeric|null $total
 * @property string|null $categoria
 * @property string|null $motivo
 * @property string|null $observaciones
 * @property string|null $referencia
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \App\Models\Almacen|null $almacen
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\Producto|null $producto
 * @property-read \App\Models\User|null $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual entradas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual salidas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual whereAlmacenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual whereCantidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual whereCategoria($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual whereCostoUnitario($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual whereMotivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual whereObservaciones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual whereProductoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual whereReferencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MovimientoManual whereUserId($value)
 */
	class MovimientoManual extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $blog_post_id
 * @property int $cliente_id
 * @property string $token
 * @property string|null $abierto_at
 * @property string|null $clic_at
 * @property string|null $enviado_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $interes_at
 * @property-read \App\Models\BlogPost $blogPost
 * @property-read \App\Models\Cliente $cliente
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterTrack newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterTrack newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterTrack query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterTrack whereAbiertoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterTrack whereBlogPostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterTrack whereClicAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterTrack whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterTrack whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterTrack whereEnviadoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterTrack whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterTrack whereInteresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterTrack whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterTrack whereUpdatedAt($value)
 */
	class NewsletterTrack extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $empleado_id
 * @property \Illuminate\Support\Carbon $periodo_inicio
 * @property \Illuminate\Support\Carbon $periodo_fin
 * @property string $tipo_periodo
 * @property int|null $numero_periodo
 * @property int $anio
 * @property numeric $salario_base
 * @property numeric $dias_trabajados
 * @property numeric $horas_extra
 * @property numeric $monto_horas_extra
 * @property numeric $total_percepciones
 * @property numeric $total_deducciones
 * @property numeric $total_neto
 * @property string $estado
 * @property \Illuminate\Support\Carbon|null $fecha_pago
 * @property string|null $metodo_pago
 * @property string|null $referencia_pago
 * @property int|null $creado_por
 * @property int|null $procesado_por
 * @property \Illuminate\Support\Carbon|null $procesado_at
 * @property int|null $pagado_por
 * @property \Illuminate\Support\Carbon|null $pagado_at
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $user_id
 * @property string|null $folio
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NominaConcepto> $conceptos
 * @property-read int|null $conceptos_count
 * @property-read \App\Models\User|null $creadoPor
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NominaConcepto> $deducciones
 * @property-read int|null $deducciones_count
 * @property-read \App\Models\User $empleado
 * @property-read int $dias_periodo
 * @property-read mixed $es_editable
 * @property-read mixed $estado_info
 * @property-read mixed $nombre_empleado
 * @property-read mixed $periodo_formateado
 * @property-read mixed $tipo_periodo_formateado
 * @property-read \App\Models\User|null $pagadoPor
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NominaConcepto> $percepciones
 * @property-read int|null $percepciones_count
 * @property-read \App\Models\User|null $procesadoPor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina borradores()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina delAnio($anio)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina delEmpleado($empleadoId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina delPeriodo($tipoPeriodo)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina entreFechas($inicio, $fin)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina estado($estado)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina pagadas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina procesadas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereAnio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereCreadoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereDiasTrabajados($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereEmpleadoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereFechaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereFolio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereHorasExtra($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereMetodoPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereMontoHorasExtra($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereNumeroPeriodo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina wherePagadoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina wherePagadoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina wherePeriodoFin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina wherePeriodoInicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereProcesadoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereProcesadoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereReferenciaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereSalarioBase($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereTipoPeriodo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereTotalDeducciones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereTotalNeto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereTotalPercepciones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nomina withoutTrashed()
 */
	class Nomina extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $nomina_id
 * @property string $tipo
 * @property string $concepto
 * @property string|null $clave
 * @property string|null $clave_sat
 * @property numeric $monto
 * @property numeric|null $porcentaje
 * @property numeric|null $base_calculo
 * @property bool $es_automatico
 * @property bool $es_gravable
 * @property bool $es_exento
 * @property int|null $prestamo_id
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $es_deduccion
 * @property-read mixed $es_percepcion
 * @property-read mixed $tipo_formateado
 * @property-read \App\Models\Nomina|null $nomina
 * @property-read \App\Models\Prestamo|null $prestamo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto automaticos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto deducciones()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto manuales()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto percepciones()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto whereBaseCalculo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto whereClave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto whereClaveSat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto whereConcepto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto whereEsAutomatico($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto whereEsExento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto whereEsGravable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto whereMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto whereNominaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto wherePorcentaje($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto wherePrestamoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NominaConcepto whereUpdatedAt($value)
 */
	class NominaConcepto extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $type
 * @property array<array-key, mixed> $data
 * @property bool $read
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $user_id
 * @property string $title
 * @property string $message
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property string|null $action_url
 * @property string|null $icon
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification forUser($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification read()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification unread()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereActionUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereUserId($value)
 */
	class Notification extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $proveedor_id
 * @property string|null $numero_orden
 * @property \Illuminate\Support\Carbon|null $fecha_orden
 * @property \Illuminate\Support\Carbon|null $fecha_entrega_esperada
 * @property string $prioridad
 * @property string|null $direccion_entrega
 * @property string $terminos_pago
 * @property string $metodo_pago
 * @property numeric $subtotal
 * @property numeric $descuento_items
 * @property numeric $descuento_general
 * @property numeric $iva
 * @property numeric $total
 * @property string $estado
 * @property \Illuminate\Support\Carbon|null $fecha_recepcion
 * @property string|null $observaciones
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $almacen_id
 * @property bool $email_enviado
 * @property \Illuminate\Support\Carbon|null $email_enviado_fecha
 * @property int|null $email_enviado_por
 * @property int|null $pedido_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property numeric $isr
 * @property numeric $retencion_iva
 * @property numeric $retencion_isr
 * @property int|null $empresa_id
 * @property-read \App\Models\Almacen|null $almacen
 * @property-read \App\Models\Compra|null $compra
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Compra> $compras
 * @property-read int|null $compras_count
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\User|null $deletedBy
 * @property-read \App\Models\User|null $emailEnviadoPor
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\Pedido|null $pedido
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Producto> $productos
 * @property-read int|null $productos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Producto> $productosActivos
 * @property-read int|null $productos_activos_count
 * @property-read \App\Models\Proveedor|null $proveedor
 * @property-read \App\Models\User|null $updatedBy
 * @method static \Database\Factories\OrdenCompraFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereAlmacenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereDescuentoGeneral($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereDescuentoItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereDireccionEntrega($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereEmailEnviado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereEmailEnviadoFecha($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereEmailEnviadoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereFechaEntregaEsperada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereFechaOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereFechaRecepcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereIsr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereIva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereMetodoPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereNumeroOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereObservaciones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra wherePedidoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra wherePrioridad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereProveedorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereRetencionIsr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereRetencionIva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereTerminosPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdenCompra withoutTrashed()
 */
	class OrdenCompra extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $vendedor_type
 * @property int $vendedor_id
 * @property \Illuminate\Support\Carbon $periodo_inicio
 * @property \Illuminate\Support\Carbon $periodo_fin
 * @property numeric $monto_comision
 * @property numeric $monto_pagado
 * @property string $estado
 * @property \Illuminate\Support\Carbon|null $fecha_pago
 * @property string|null $metodo_pago
 * @property string|null $referencia_pago
 * @property int|null $cuenta_bancaria_id
 * @property array<array-key, mixed>|null $detalles_ventas
 * @property int $num_ventas
 * @property numeric $total_ventas
 * @property string|null $notas
 * @property int|null $pagado_por
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $empresa_id
 * @property-read \App\Models\User|null $createdByUser
 * @property-read \App\Models\CuentaBancaria|null $cuentaBancaria
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read mixed $esta_completa
 * @property-read mixed $monto_pendiente
 * @property-read mixed $nombre_vendedor
 * @property-read \App\Models\User|null $pagadoPorUser
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $vendedor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision deVendedor($type, $id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision delPeriodo($inicio, $fin)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision pagados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision pendientes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereCuentaBancariaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereDetallesVentas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereFechaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereMetodoPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereMontoComision($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereMontoPagado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereNumVentas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision wherePagadoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision wherePeriodoFin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision wherePeriodoInicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereReferenciaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereTotalVentas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereVendedorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision whereVendedorType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoComision withoutTrashed()
 */
	class PagoComision extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $prestamo_id
 * @property int $numero_pago
 * @property numeric $monto_programado
 * @property float $monto_pagado
 * @property \Illuminate\Support\Carbon $fecha_programada
 * @property \Illuminate\Support\Carbon|null $fecha_pago
 * @property \Illuminate\Support\Carbon $fecha_registro
 * @property string $estado
 * @property int $dias_atraso
 * @property string|null $notas
 * @property string|null $metodo_pago
 * @property string|null $referencia
 * @property bool $confirmado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $empresa_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \OwenIt\Auditing\Models\Audit> $audits
 * @property-read int|null $audits_count
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read bool $esta_vencido
 * @property-read string $estado_texto
 * @property-read float $monto_pendiente
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HistorialPagoPrestamo> $historialPagos
 * @property-read int|null $historial_pagos_count
 * @property-read \App\Models\Prestamo|null $prestamo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo atrasados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo pagados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo parciales()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo pendientes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo porPrestamo($prestamoId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo vencidos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo whereConfirmado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo whereDiasAtraso($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo whereFechaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo whereFechaProgramada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo whereFechaRegistro($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo whereMetodoPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo whereMontoPagado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo whereMontoProgramado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo whereNumeroPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo wherePrestamoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo whereReferencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PagoPrestamo withoutTrashed()
 */
	class PagoPrestamo extends \Eloquent implements \OwenIt\Auditing\Contracts\Auditable {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cliente_id
 * @property int|null $cotizacion_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property string $numero_pedido
 * @property numeric|null $subtotal
 * @property numeric $descuento_general
 * @property numeric $descuento_items
 * @property numeric|null $iva
 * @property numeric $total
 * @property \App\Enums\EstadoPedido $estado
 * @property string|null $fecha_pedido
 * @property string|null $fecha_entrega_estimada
 * @property string|null $fecha_entregado_at
 * @property string|null $tipo_entrega
 * @property string|null $direccion_entrega
 * @property string|null $empresa_envio
 * @property string|null $numero_guia
 * @property numeric $costo_envio
 * @property string|null $metodo_pago
 * @property string|null $referencia_pago
 * @property string|null $pagado_at
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $email_enviado
 * @property \Illuminate\Support\Carbon|null $email_enviado_fecha
 * @property int|null $email_enviado_por
 * @property numeric $isr
 * @property numeric $retencion_iva
 * @property numeric $retencion_isr
 * @property int|null $empresa_id
 * @property string|null $folio
 * @property-read \App\Models\Cliente|null $cliente
 * @property-read \App\Models\Cotizacion|null $cotizacion
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\User|null $deletedBy
 * @property-read \App\Models\User|null $emailEnviadoPor
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PedidoItem> $items
 * @property-read int|null $items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrdenCompra> $ordenesCompra
 * @property-read int|null $ordenes_compra_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Producto> $productos
 * @property-read int|null $productos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Servicio> $servicios
 * @property-read int|null $servicios_count
 * @property-read \App\Models\User|null $updatedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereCostoEnvio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereCotizacionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereDescuentoGeneral($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereDescuentoItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereDireccionEntrega($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereEmailEnviado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereEmailEnviadoFecha($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereEmailEnviadoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereEmpresaEnvio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereFechaEntregaEstimada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereFechaEntregadoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereFechaPedido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereFolio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereIsr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereIva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereMetodoPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereNumeroGuia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereNumeroPedido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido wherePagadoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereReferenciaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereRetencionIsr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereRetencionIva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereTipoEntrega($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido withoutTrashed()
 */
	class Pedido extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $pedido_online_id
 * @property string $accion
 * @property string $descripcion
 * @property int|null $usuario_id
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\PedidoOnline|null $pedido
 * @property-read \App\Models\User|null $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoBitacora newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoBitacora newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoBitacora query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoBitacora whereAccion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoBitacora whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoBitacora whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoBitacora whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoBitacora whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoBitacora wherePedidoOnlineId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoBitacora whereUsuarioId($value)
 */
	class PedidoBitacora extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $pedido_id
 * @property int $pedible_id
 * @property string $pedible_type
 * @property numeric $precio
 * @property int $cantidad
 * @property numeric $descuento
 * @property numeric $subtotal
 * @property numeric $descuento_monto
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $price_list_id
 * @property string|null $nombre
 * @property string|null $tipo_item
 * @property int|null $empresa_id
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $pedible
 * @property-read \App\Models\Pedido|null $pedido
 * @property-read \App\Models\PriceList|null $priceList
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoItem whereCantidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoItem whereDescuento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoItem whereDescuentoMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoItem whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoItem whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoItem wherePedibleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoItem wherePedibleType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoItem wherePedidoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoItem wherePrecio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoItem wherePriceListId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoItem whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoItem whereTipoItem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoItem whereUpdatedAt($value)
 */
	class PedidoItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $numero_pedido
 * @property int|null $cliente_tienda_id
 * @property string $email
 * @property string $nombre
 * @property string|null $telefono
 * @property array<array-key, mixed>|null $direccion_envio
 * @property array<array-key, mixed> $items
 * @property numeric $subtotal
 * @property numeric $costo_envio
 * @property numeric $total
 * @property string|null $metodo_pago
 * @property string $estado
 * @property string|null $payment_id
 * @property string|null $payment_status
 * @property array<array-key, mixed>|null $payment_details
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon|null $pagado_at
 * @property \Illuminate\Support\Carbon|null $enviado_at
 * @property \Illuminate\Support\Carbon|null $entregado_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property string|null $cva_pedido_id
 * @property string|null $guia_envio
 * @property string|null $paqueteria
 * @property int|null $cliente_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PedidoBitacora> $bitacora
 * @property-read int|null $bitacora_count
 * @property-read \App\Models\ClienteTienda|null $cliente
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read string $estado_color
 * @property-read string $estado_label
 * @property-read string|null $tracking_url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline pagados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline pendientes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereClienteTiendaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereCostoEnvio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereCvaPedidoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereDireccionEnvio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereEntregadoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereEnviadoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereGuiaEnvio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereMetodoPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereNumeroPedido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline wherePagadoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline wherePaqueteria($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline wherePaymentDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereTelefono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PedidoOnline whereUpdatedAt($value)
 */
	class PedidoOnline extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $empresa_id
 * @property string $nombre
 * @property string $slug
 * @property string|null $descripcion
 * @property string|null $descripcion_corta
 * @property string $tipo
 * @property string|null $icono
 * @property string|null $color
 * @property numeric $precio_mensual
 * @property numeric|null $precio_anual
 * @property numeric $precio_instalacion
 * @property int|null $horas_incluidas
 * @property int|null $tickets_incluidos
 * @property int|null $sla_horas_respuesta
 * @property numeric|null $costo_hora_extra
 * @property array<array-key, mixed>|null $beneficios
 * @property array<array-key, mixed>|null $incluye_servicios
 * @property bool $activo
 * @property bool $destacado
 * @property bool $visible_catalogo
 * @property int $orden
 * @property int|null $min_equipos
 * @property int|null $max_equipos
 * @property string|null $imagen
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $mantenimiento_frecuencia_meses Frecuencia en meses sugerida para mantenimientos preventivos
 * @property bool $generar_cita_automatica Sugerencia de si se debe generar cita automática
 * @property int $mantenimiento_dias_anticipacion
 * @property int|null $visitas_sitio_mensuales
 * @property numeric|null $costo_visita_sitio_extra
 * @property string|null $clausulas Cláusulas legales y términos del servicio
 * @property string|null $terminos_pago Detalles sobre cómo y cuándo se realizan los pagos
 * @property numeric $costo_ticket_extra
 * @property int|null $sla_horas_resolucion
 * @property int $mantenimientos_anuales
 * @property string $moneda
 * @property numeric|null $precio_trimestral
 * @property numeric|null $precio_semestral
 * @property numeric $iva_tasa
 * @property bool $iva_incluido
 * @property int $limit_dia_pago
 * @property int $dias_gracia_cobranza
 * @property numeric $recargo_pago_tardio
 * @property string $tipo_recargo
 * @property int|null $limite_usuarios_soporte
 * @property int $limite_ubicaciones
 * @property bool $soporte_remoto_ilimitado
 * @property bool $soporte_presencial_incluido
 * @property bool $requiere_orden_compra
 * @property string|null $metodo_pago_sugerido
 * @property-read \App\Models\Empresa $empresa
 * @property-read mixed $ahorro_anual
 * @property-read mixed $beneficios_array
 * @property-read mixed $icono_display
 * @property-read mixed $porcentaje_descuento_anual
 * @property-read array $servicios_elegibles_ids
 * @property-read mixed $tipo_label
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Servicio> $serviciosElegibles
 * @property-read int|null $servicios_elegibles_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza activos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza destacados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza ordenado()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza publicos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereBeneficios($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereClausulas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereCostoHoraExtra($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereCostoTicketExtra($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereCostoVisitaSitioExtra($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereDescripcionCorta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereDestacado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereDiasGraciaCobranza($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereGenerarCitaAutomatica($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereHorasIncluidas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereIcono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereImagen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereIncluyeServicios($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereIvaIncluido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereIvaTasa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereLimitDiaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereLimiteUbicaciones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereLimiteUsuariosSoporte($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereMantenimientoDiasAnticipacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereMantenimientoFrecuenciaMeses($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereMantenimientosAnuales($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereMaxEquipos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereMetodoPagoSugerido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereMinEquipos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereMoneda($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza wherePrecioAnual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza wherePrecioInstalacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza wherePrecioMensual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza wherePrecioSemestral($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza wherePrecioTrimestral($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereRecargoPagoTardio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereRequiereOrdenCompra($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereSlaHorasResolucion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereSlaHorasRespuesta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereSoportePresencialIncluido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereSoporteRemotoIlimitado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereTerminosPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereTicketsIncluidos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereTipoRecargo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereVisibleCatalogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza whereVisitasSitioMensuales($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPoliza withoutTrashed()
 */
	class PlanPoliza extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $empresa_id
 * @property string $nombre
 * @property string $slug
 * @property string|null $descripcion
 * @property string|null $descripcion_corta
 * @property string $tipo
 * @property string|null $icono
 * @property string $color
 * @property numeric $precio_mensual
 * @property numeric $deposito_garantia
 * @property int $meses_minimos
 * @property array<array-key, mixed>|null $beneficios
 * @property array<array-key, mixed>|null $equipamiento_incluido
 * @property bool $activo
 * @property bool $destacado
 * @property bool $visible_catalogo
 * @property int $orden
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property numeric|null $precio_venta Precio de venta total si el cliente desea comprar
 * @property bool $disponible_venta
 * @property-read \App\Models\Empresa $empresa
 * @property-read mixed $icono_display
 * @property-read mixed $tipo_label
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta destacados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta ordenado()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta publicos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereBeneficios($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereDepositoGarantia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereDescripcionCorta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereDestacado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereDisponibleVenta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereEquipamientoIncluido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereIcono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereMesesMinimos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta wherePrecioMensual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta wherePrecioVenta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta whereVisibleCatalogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRenta withoutTrashed()
 */
	class PlanRenta extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $poliza_id
 * @property int|null $user_id
 * @property string $event
 * @property array<array-key, mixed>|null $old_values
 * @property array<array-key, mixed>|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $cliente_id
 * @property bool $system_event
 * @property-read \App\Models\Cliente|null $cliente
 * @property-read \App\Models\PolizaServicio $poliza
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaAuditLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaAuditLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaAuditLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaAuditLog whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaAuditLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaAuditLog whereEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaAuditLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaAuditLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaAuditLog whereNewValues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaAuditLog whereOldValues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaAuditLog wherePolizaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaAuditLog whereSystemEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaAuditLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaAuditLog whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaAuditLog whereUserId($value)
 */
	class PolizaAuditLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $poliza_id
 * @property numeric $subtotal
 * @property numeric $iva
 * @property numeric $total
 * @property string $moneda
 * @property string $concepto
 * @property string $tipo_ciclo
 * @property \Illuminate\Support\Carbon $fecha_emision
 * @property \Illuminate\Support\Carbon $fecha_vencimiento
 * @property string $estado
 * @property string|null $referencia_pago
 * @property string|null $metodo_pago
 * @property \Illuminate\Support\Carbon|null $fecha_pago
 * @property string|null $notas
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\PolizaServicio $poliza
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo pendientes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo vencidos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo whereConcepto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo whereFechaEmision($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo whereFechaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo whereFechaVencimiento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo whereIva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo whereMetodoPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo whereMoneda($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo wherePolizaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo whereReferenciaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo whereTipoCiclo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaCargo withoutTrashed()
 */
	class PolizaCargo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $poliza_id
 * @property string $tipo
 * @property string $consumible_type
 * @property int $consumible_id
 * @property int $cantidad
 * @property numeric $valor_unitario
 * @property numeric $ahorro
 * @property string|null $descripcion
 * @property int|null $registrado_por
 * @property \Illuminate\Support\Carbon $fecha_consumo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property numeric $costo_interno
 * @property int|null $tecnico_id
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $consumible
 * @property-read string $icono
 * @property-read string $tipo_label
 * @property-read \App\Models\PolizaServicio $poliza
 * @property-read \App\Models\User|null $registrador
 * @property-read \App\Models\User|null $tecnico
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo dePoliza(int $polizaId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo mesActual()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo tipo(string $tipo)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo whereAhorro($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo whereCantidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo whereConsumibleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo whereConsumibleType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo whereCostoInterno($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo whereFechaConsumo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo wherePolizaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo whereRegistradoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo whereTecnicoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaConsumo whereValorUnitario($value)
 */
	class PolizaConsumo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $poliza_id
 * @property string $tipo
 * @property string $nombre
 * @property string|null $descripcion
 * @property string $frecuencia
 * @property int $dia_preferido
 * @property bool $requiere_visita
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $ultima_generacion_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property array<array-key, mixed>|null $checklist
 * @property int|null $guia_tecnica_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PolizaMantenimientoEjecucion> $ejecuciones
 * @property-read int|null $ejecuciones_count
 * @property-read string $frecuencia_info
 * @property-read \App\Models\GuiaTecnica|null $guiaTecnica
 * @property-read \App\Models\PolizaServicio $poliza
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimiento newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimiento newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimiento query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimiento whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimiento whereChecklist($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimiento whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimiento whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimiento whereDiaPreferido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimiento whereFrecuencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimiento whereGuiaTecnicaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimiento whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimiento whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimiento wherePolizaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimiento whereRequiereVisita($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimiento whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimiento whereUltimaGeneracionAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimiento whereUpdatedAt($value)
 */
	class PolizaMantenimiento extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $mantenimiento_id
 * @property int|null $tecnico_id
 * @property \Illuminate\Support\Carbon $fecha_programada
 * @property \Illuminate\Support\Carbon $fecha_original
 * @property int $reprogramado_count
 * @property string|null $notas_reprogramacion
 * @property \Illuminate\Support\Carbon|null $fecha_ejecucion
 * @property string $estado
 * @property string|null $resultado
 * @property string|null $notas_tecnico
 * @property array<array-key, mixed>|null $evidencia
 * @property bool $notificado_cliente
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property array<array-key, mixed>|null $checklist
 * @property-read bool $esta_vencido
 * @property-read \App\Models\PolizaMantenimiento $mantenimiento
 * @property-read \App\Models\User|null $tecnico
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion paraHoy()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion pendientes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion whereChecklist($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion whereEvidencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion whereFechaEjecucion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion whereFechaOriginal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion whereFechaProgramada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion whereMantenimientoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion whereNotasReprogramacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion whereNotasTecnico($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion whereNotificadoCliente($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion whereReprogramadoCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion whereResultado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion whereTecnicoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaMantenimientoEjecucion whereUpdatedAt($value)
 */
	class PolizaMantenimientoEjecucion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $empresa_id
 * @property string $folio
 * @property int $cliente_id
 * @property string $nombre
 * @property string|null $descripcion
 * @property \Illuminate\Support\Carbon $fecha_inicio
 * @property \Illuminate\Support\Carbon|null $fecha_fin
 * @property numeric $monto_mensual
 * @property int $dia_cobro
 * @property string $estado
 * @property int|null $limite_mensual_tickets
 * @property bool $notificar_exceso_limite
 * @property bool $renovacion_automatica
 * @property array<array-key, mixed>|null $condiciones_especiales
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $ultimo_cobro_generado_at
 * @property int|null $sla_horas_respuesta
 * @property int|null $horas_incluidas_mensual Horas de servicio incluidas por mes
 * @property numeric $horas_consumidas_mes Horas consumidas en el mes actual
 * @property numeric|null $costo_hora_excedente Costo por hora adicional cuando se exceden las incluidas
 * @property int $dias_alerta_vencimiento Días antes del vencimiento para enviar alerta
 * @property bool $alerta_vencimiento_enviada Indica si ya se envió la alerta de vencimiento
 * @property \Illuminate\Support\Carbon|null $ultima_alerta_exceso_at Última vez que se envió alerta de exceso de límite
 * @property \Illuminate\Support\Carbon|null $ultimo_reset_consumo_at Última vez que se reseteó el consumo mensual
 * @property int|null $mantenimiento_frecuencia_meses Frecuencia en meses para generar mantenimientos preventivos
 * @property \Illuminate\Support\Carbon|null $proximo_mantenimiento_at Fecha programada para el próximo mantenimiento preventivo
 * @property bool $generar_cita_automatica Indica si se debe generar una cita automática al llegar la fecha
 * @property int $mantenimiento_dias_anticipacion
 * @property \Illuminate\Support\Carbon|null $ultimo_aviso_vencimiento_at
 * @property int|null $visitas_sitio_mensuales Visitas en sitio incluidas por mes
 * @property int $visitas_sitio_consumidas_mes Visitas en sitio consumidas en el mes actual
 * @property numeric|null $costo_visita_sitio_extra Costo por visita en sitio adicional
 * @property int $tickets_soporte_consumidos_mes
 * @property string|null $clausulas_especiales Cláusulas personalizadas solo para este contrato
 * @property int $dias_gracia
 * @property \Illuminate\Support\Carbon|null $pausada_at
 * @property \Illuminate\Support\Carbon|null $reanudada_at
 * @property string|null $motivo_pausa
 * @property int $total_dias_pausa
 * @property numeric $costo_ticket_extra
 * @property bool $alerta_horas_20_enviada
 * @property int|null $sla_horas_resolucion
 * @property int $mantenimientos_anuales
 * @property string|null $firma_cliente
 * @property \Illuminate\Support\Carbon|null $firmado_at
 * @property string|null $firmado_ip
 * @property string|null $firma_hash
 * @property string|null $firmado_nombre
 * @property string|null $firma_empresa
 * @property \Illuminate\Support\Carbon|null $firma_empresa_at
 * @property int|null $firma_empresa_usuario_id
 * @property numeric $ingreso_devengado
 * @property numeric $ingreso_diferido
 * @property numeric $costo_acumulado_tecnico
 * @property \Illuminate\Support\Carbon|null $ultima_emision_fac_at
 * @property int|null $direccion_id
 * @property int|null $plan_poliza_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PolizaCargo> $cargos
 * @property-read int|null $cargos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cita> $citas
 * @property-read int|null $citas_count
 * @property-read \App\Models\Cliente $cliente
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PolizaConsumo> $consumos
 * @property-read int|null $consumos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Credencial> $credenciales
 * @property-read int|null $credenciales_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CuentasPorCobrar> $cuentasPorCobrar
 * @property-read int|null $cuentas_por_cobrar_count
 * @property-read \App\Models\ClienteDireccion|null $direccion
 * @property-read \App\Models\Empresa $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Equipo> $equipos
 * @property-read int|null $equipos_count
 * @property-read float $ahorro_mes_actual
 * @property-read int|null $dias_gracia_restantes
 * @property-read int|null $dias_para_vencer
 * @property-read bool $excede_horas
 * @property-read bool $excede_limite
 * @property-read bool $excede_limite_visitas
 * @property-read float $horas_disponibles
 * @property-read float|null $porcentaje_horas
 * @property-read float|null $porcentaje_horas_restantes
 * @property-read float|null $porcentaje_tickets
 * @property-read mixed $tickets_asesoria_mes_count
 * @property-read mixed $tickets_mes_actual_count
 * @property-read mixed $tickets_soporte_mes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PolizaMantenimiento> $mantenimientos
 * @property-read int|null $mantenimientos_count
 * @property-read \App\Models\PlanPoliza|null $planPoliza
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Servicio> $servicios
 * @property-read int|null $servicios_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 * @property-read int|null $tickets_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio activa()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio proximasAVencer(int $dias = 30)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereAlertaHoras20Enviada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereAlertaVencimientoEnviada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereClausulasEspeciales($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereCondicionesEspeciales($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereCostoAcumuladoTecnico($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereCostoHoraExcedente($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereCostoTicketExtra($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereCostoVisitaSitioExtra($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereDiaCobro($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereDiasAlertaVencimiento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereDiasGracia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereDireccionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereFechaFin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereFechaInicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereFirmaCliente($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereFirmaEmpresa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereFirmaEmpresaAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereFirmaEmpresaUsuarioId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereFirmaHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereFirmadoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereFirmadoIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereFirmadoNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereFolio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereGenerarCitaAutomatica($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereHorasConsumidasMes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereHorasIncluidasMensual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereIngresoDevengado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereIngresoDiferido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereLimiteMensualTickets($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereMantenimientoDiasAnticipacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereMantenimientoFrecuenciaMeses($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereMantenimientosAnuales($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereMontoMensual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereMotivoPausa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereNotificarExcesoLimite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio wherePausadaAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio wherePlanPolizaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereProximoMantenimientoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereReanudadaAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereRenovacionAutomatica($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereSlaHorasResolucion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereSlaHorasRespuesta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereTicketsSoporteConsumidosMes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereTotalDiasPausa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereUltimaAlertaExcesoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereUltimaEmisionFacAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereUltimoAvisoVencimientoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereUltimoCobroGeneradoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereUltimoResetConsumoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereVisitasSitioConsumidasMes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio whereVisitasSitioMensuales($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolizaServicio withoutTrashed()
 */
	class PolizaServicio extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cliente_id
 * @property numeric $monto_prestado
 * @property numeric|null $tasa_interes
 * @property int $numero_pagos
 * @property string $frecuencia_pago
 * @property \Illuminate\Support\Carbon $fecha_inicio
 * @property \Illuminate\Support\Carbon|null $fecha_primer_pago
 * @property numeric $monto_interes_total
 * @property numeric $monto_total_pagar
 * @property numeric $pago_periodico
 * @property string $estado
 * @property int $pagos_realizados
 * @property int $pagos_pendientes
 * @property numeric $monto_pagado
 * @property numeric $monto_pendiente
 * @property string|null $descripcion
 * @property string|null $notas
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property numeric $tasa_interes_mensual
 * @property int|null $empresa_id
 * @property string|null $folio
 * @property int|null $empleado_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \OwenIt\Auditing\Models\Audit> $audits
 * @property-read int|null $audits_count
 * @property-read \App\Models\Cliente|null $cliente
 * @property-read \App\Models\User|null $empleado
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read string $estado_texto
 * @property-read string $frecuencia_texto
 * @property-read float $progreso
 * @property-read \App\Models\PagoPrestamo|null $proximo_pago
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PagoPrestamo> $pagos
 * @property-read int|null $pagos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo activos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo cancelados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo completados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo porCliente($clienteId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereEmpleadoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereFechaInicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereFechaPrimerPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereFolio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereFrecuenciaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereMontoInteresTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereMontoPagado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereMontoPendiente($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereMontoPrestado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereMontoTotalPagar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereNumeroPagos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo wherePagoPeriodico($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo wherePagosPendientes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo wherePagosRealizados($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereTasaInteres($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereTasaInteresMensual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo withoutTrashed()
 */
	class Prestamo extends \Eloquent implements \OwenIt\Auditing\Contracts\Auditable {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string $clave
 * @property string|null $descripcion
 * @property bool $activa
 * @property int $orden
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cliente> $clientes
 * @property-read int|null $clientes_count
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductPrice> $productPrices
 * @property-read int|null $product_prices_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList activas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereActiva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereClave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereUpdatedAt($value)
 */
	class PriceList extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $producto_id
 * @property int $price_list_id
 * @property numeric $precio
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\PriceList|null $priceList
 * @property-read \App\Models\Producto|null $producto
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice wherePrecio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice wherePriceListId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereProductoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereUpdatedAt($value)
 */
	class ProductPrice extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string $descripcion
 * @property string $codigo
 * @property string $codigo_barras
 * @property string|null $numero_serie
 * @property int $categoria_id
 * @property int $marca_id
 * @property int|null $proveedor_id
 * @property int|null $almacen_id
 * @property int $stock
 * @property int $stock_minimo
 * @property numeric $precio_compra
 * @property numeric $precio_venta
 * @property string $unidad_medida
 * @property string|null $fecha_vencimiento
 * @property string|null $imagen
 * @property string $estado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $reservado
 * @property numeric $margen_ganancia
 * @property numeric $comision_vendedor
 * @property bool $expires
 * @property bool $requiere_serie
 * @property int $dias_garantia
 * @property string|null $cobertura_garantia
 * @property bool $maneja_series
 * @property string $tipo_producto
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property string|null $sat_clave_prod_serv
 * @property string|null $sat_clave_unidad
 * @property string $sat_objeto_imp
 * @property int|null $empresa_id
 * @property string $origen
 * @property string|null $cva_clave
 * @property int $stock_cedis
 * @property \Illuminate\Support\Carbon|null $cva_last_sync
 * @property bool|null $destacado
 * @property-read \App\Models\Almacen|null $almacen
 * @property-read \App\Models\Categoria|null $categoria
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CitaItem> $citaItems
 * @property-read int|null $cita_items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cita> $citasComoUtilizado
 * @property-read int|null $citas_como_utilizado_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cita> $citasComoVendido
 * @property-read int|null $citas_como_vendido_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Compra> $compras
 * @property-read int|null $compras_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CotizacionItem> $cotizacionItems
 * @property-read int|null $cotizacion_items_count
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\User|null $deletedBy
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read mixed $ganancia
 * @property-read mixed $ganancia_margen
 * @property-read mixed $precio_con_iva
 * @property-read mixed $stock_disponible
 * @property-read mixed $stock_total
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventario> $inventarios
 * @property-read int|null $inventarios_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\KitItem> $kitItems
 * @property-read int|null $kit_items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\KitItem> $kits
 * @property-read int|null $kits_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Lote> $lotes
 * @property-read int|null $lotes_count
 * @property-read \App\Models\Marca|null $marca
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InventarioMovimiento> $movimientos
 * @property-read int|null $movimientos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrdenCompra> $ordenesCompra
 * @property-read int|null $ordenes_compra_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PedidoItem> $pedidoItems
 * @property-read int|null $pedido_items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductoPrecioHistorial> $precioHistorial
 * @property-read int|null $precio_historial_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductPrice> $precios
 * @property-read int|null $precios_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductPrice> $productPrices
 * @property-read int|null $product_prices_count
 * @property-read \App\Models\Proveedor|null $proveedor
 * @property-read \App\Models\SatClaveProdServ|null $satClaveProdServ
 * @property-read \App\Models\SatClaveUnidad|null $satClaveUnidad
 * @property-read \App\Models\SatObjetoImp|null $satObjetoImp
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductoSerie> $series
 * @property-read int|null $series_count
 * @property-read \App\Models\User|null $updatedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VentaItem> $ventaItems
 * @property-read int|null $venta_items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Venta> $ventas
 * @property-read int|null $ventas_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto active()
 * @method static \Database\Factories\ProductoFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto kits()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto noKits()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereAlmacenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereCategoriaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereCoberturaGarantia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereCodigoBarras($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereComisionVendedor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereCvaClave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereCvaLastSync($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereDestacado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereDiasGarantia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereExpires($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereFechaVencimiento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereImagen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereManejaSeries($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereMarcaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereMargenGanancia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereNumeroSerie($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereOrigen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto wherePrecioCompra($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto wherePrecioVenta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereProveedorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereRequiereSerie($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereReservado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereSatClaveProdServ($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereSatClaveUnidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereSatObjetoImp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereStockCedis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereStockMinimo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereTipoProducto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereUnidadMedida($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Producto withoutTrashed()
 */
	class Producto extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $producto_id
 * @property numeric|null $precio_compra_anterior
 * @property numeric $precio_compra_nuevo
 * @property numeric|null $precio_venta_anterior
 * @property numeric $precio_venta_nuevo
 * @property string $tipo_cambio
 * @property string|null $notas
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\Producto|null $producto
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoPrecioHistorial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoPrecioHistorial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoPrecioHistorial query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoPrecioHistorial whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoPrecioHistorial whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoPrecioHistorial whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoPrecioHistorial whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoPrecioHistorial wherePrecioCompraAnterior($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoPrecioHistorial wherePrecioCompraNuevo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoPrecioHistorial wherePrecioVentaAnterior($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoPrecioHistorial wherePrecioVentaNuevo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoPrecioHistorial whereProductoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoPrecioHistorial whereTipoCambio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoPrecioHistorial whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoPrecioHistorial whereUserId($value)
 */
	class ProductoPrecioHistorial extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $producto_id
 * @property int|null $compra_id
 * @property int|null $almacen_id
 * @property string $numero_serie
 * @property string $estado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $cita_id
 * @property int|null $venta_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $empresa_id
 * @property-read \App\Models\Almacen|null $almacen
 * @property-read \App\Models\Cita|null $cita
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\Producto|null $producto
 * @property-read \App\Models\Venta|null $venta
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoSerie newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoSerie newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoSerie onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoSerie query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoSerie whereAlmacenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoSerie whereCitaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoSerie whereCompraId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoSerie whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoSerie whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoSerie whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoSerie whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoSerie whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoSerie whereNumeroSerie($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoSerie whereProductoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoSerie whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoSerie whereVentaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoSerie withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductoSerie withoutTrashed()
 */
	class ProductoSerie extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre_razon_social
 * @property string|null $tipo_persona
 * @property string|null $contacto
 * @property string|null $tel_contacto
 * @property string|null $curp
 * @property string $rfc
 * @property string|null $regimen_fiscal
 * @property string|null $uso_cfdi
 * @property string|null $email
 * @property string|null $telefono
 * @property string|null $calle
 * @property string|null $numero_exterior
 * @property string|null $numero_interior
 * @property string|null $colonia
 * @property string|null $codigo_postal
 * @property string|null $municipio
 * @property string|null $estado
 * @property string|null $pais
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $activo
 * @property int|null $empresa_id
 * @property string|null $codigo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Compra> $compras
 * @property-read int|null $compras_count
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrdenCompra> $ordenesCompra
 * @property-read int|null $ordenes_compra_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Producto> $productos
 * @property-read int|null $productos_count
 * @method static \Database\Factories\ProveedorFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereCalle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereCodigoPostal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereColonia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereContacto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereCurp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereMunicipio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereNombreRazonSocial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereNumeroExterior($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereNumeroInterior($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor wherePais($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereRegimenFiscal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereRfc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereTelContacto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereTelefono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereTipoPersona($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor whereUsoCfdi($value)
 */
	class Proveedor extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property int $owner_id
 * @property string $color
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $cliente_id
 * @property-read \App\Models\Cliente|null $cliente
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Compra> $gastos
 * @property-read int|null $gastos_count
 * @property-read float $total_gastos
 * @property-read float $total_general
 * @property-read float $total_productos
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
 * @property-read int|null $members_count
 * @property-read \App\Models\User $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Producto> $productos
 * @property-read int|null $productos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProyectoTarea> $tareas
 * @property-read int|null $tareas_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proyecto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proyecto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proyecto query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proyecto whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proyecto whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proyecto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proyecto whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proyecto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proyecto whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proyecto whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proyecto whereUpdatedAt($value)
 */
	class Proyecto extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $titulo
 * @property string|null $descripcion
 * @property string $estado
 * @property string $prioridad
 * @property int $orden
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $proyecto_id
 * @property-read \App\Models\Proyecto|null $proyecto
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProyectoTarea newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProyectoTarea newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProyectoTarea query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProyectoTarea whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProyectoTarea whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProyectoTarea whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProyectoTarea whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProyectoTarea whereOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProyectoTarea wherePrioridad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProyectoTarea whereProyectoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProyectoTarea whereTitulo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProyectoTarea whereUpdatedAt($value)
 */
	class ProyectoTarea extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cuenta_por_cobrar_id
 * @property string $tipo_recordatorio
 * @property \Illuminate\Support\Carbon $fecha_envio
 * @property \Illuminate\Support\Carbon|null $fecha_proximo_recordatorio
 * @property bool $enviado
 * @property int $numero_intento
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CuentasPorCobrar|null $cuentaPorCobrar
 * @property-read string $tipo_label
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordatorioCobranza newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordatorioCobranza newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordatorioCobranza pendientes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordatorioCobranza porTipo(string $tipo)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordatorioCobranza query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordatorioCobranza whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordatorioCobranza whereCuentaPorCobrarId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordatorioCobranza whereEnviado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordatorioCobranza whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordatorioCobranza whereFechaEnvio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordatorioCobranza whereFechaProximoRecordatorio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordatorioCobranza whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordatorioCobranza whereNumeroIntento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordatorioCobranza whereTipoRecordatorio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordatorioCobranza whereUpdatedAt($value)
 */
	class RecordatorioCobranza extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $anio
 * @property int $dias_correspondientes
 * @property int $dias_disponibles
 * @property int $dias_utilizados
 * @property int $dias_pendientes
 * @property int $dias_acumulados_siguiente
 * @property \Illuminate\Support\Carbon|null $fecha_calculo
 * @property string|null $observaciones
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $empleado
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroVacaciones conDiasPendientes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroVacaciones delAnio($anio)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroVacaciones delEmpleado($empleadoId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroVacaciones newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroVacaciones newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroVacaciones query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroVacaciones whereAnio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroVacaciones whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroVacaciones whereDiasAcumuladosSiguiente($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroVacaciones whereDiasCorrespondientes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroVacaciones whereDiasDisponibles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroVacaciones whereDiasPendientes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroVacaciones whereDiasUtilizados($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroVacaciones whereFechaCalculo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroVacaciones whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroVacaciones whereObservaciones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroVacaciones whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroVacaciones whereUserId($value)
 */
	class RegistroVacaciones extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $empresa_id
 * @property int $user_id
 * @property int|null $cliente_id
 * @property string $rustdesk_id
 * @property string|null $rustdesk_alias
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property int|null $duration_minutes
 * @property string $status
 * @property string $source
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Cliente|null $cliente
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteSupportSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteSupportSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteSupportSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteSupportSession whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteSupportSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteSupportSession whereDurationMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteSupportSession whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteSupportSession whereEndedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteSupportSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteSupportSession whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteSupportSession whereRustdeskAlias($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteSupportSession whereRustdeskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteSupportSession whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteSupportSession whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteSupportSession whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteSupportSession whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteSupportSession whereUserId($value)
 */
	class RemoteSupportSession extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $numero_contrato
 * @property int $cliente_id
 * @property \Illuminate\Support\Carbon $fecha_inicio
 * @property \Illuminate\Support\Carbon $fecha_fin
 * @property \Illuminate\Support\Carbon|null $fecha_firma
 * @property numeric $monto_mensual
 * @property numeric $deposito_garantia
 * @property int $dia_pago
 * @property string $estado
 * @property \Illuminate\Support\Carbon|null $ultimo_pago
 * @property numeric $saldo_pendiente
 * @property int $meses_mora
 * @property string|null $condiciones_especiales
 * @property bool $renovacion_automatica
 * @property int $meses_duracion
 * @property array<array-key, mixed>|null $lugar_instalacion
 * @property \Illuminate\Support\Carbon|null $fecha_instalacion
 * @property \Illuminate\Support\Carbon|null $fecha_retiro
 * @property string|null $notas_instalacion
 * @property string|null $notas_retiro
 * @property string|null $responsable_entrega
 * @property string|null $responsable_recibe
 * @property string|null $observaciones
 * @property array<array-key, mixed>|null $historial_cambios
 * @property string|null $forma_pago
 * @property string|null $referencia_pago
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $empresa_id
 * @property string|null $firma_digital
 * @property \Illuminate\Support\Carbon|null $firmado_at
 * @property string|null $firmado_ip
 * @property string|null $firmado_nombre
 * @property string|null $firma_hash
 * @property string|null $ine_frontal
 * @property string|null $ine_trasera
 * @property string|null $comprobante_domicilio
 * @property string|null $solicitud_renta
 * @property-read \App\Models\Cliente|null $cliente
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cobranza> $cobranzas
 * @property-read int|null $cobranzas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CuentasPorCobrar> $cuentasPorCobrar
 * @property-read int|null $cuentas_por_cobrar_count
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Equipo> $equipos
 * @property-read int|null $equipos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta proximoVencimiento()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta vencidas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereComprobanteDomicilio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereCondicionesEspeciales($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereDepositoGarantia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereDiaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereFechaFin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereFechaFirma($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereFechaInicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereFechaInstalacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereFechaRetiro($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereFirmaDigital($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereFirmaHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereFirmadoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereFirmadoIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereFirmadoNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereFormaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereHistorialCambios($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereIneFrontal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereIneTrasera($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereLugarInstalacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereMesesDuracion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereMesesMora($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereMontoMensual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereNotasInstalacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereNotasRetiro($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereNumeroContrato($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereObservaciones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereReferenciaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereRenovacionAutomatica($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereResponsableEntrega($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereResponsableRecibe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereSaldoPendiente($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereSolicitudRenta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereUltimoPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Renta withoutTrashed()
 */
	class Renta extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property \Illuminate\Support\Carbon $fecha
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Producto> $productos
 * @property-read int|null $productos_count
 * @property-read \App\Models\Venta|null $venta
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reporte newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reporte newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reporte query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reporte whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reporte whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reporte whereFecha($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reporte whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reporte whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reporte whereUpdatedAt($value)
 */
	class Reporte extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $tecnico_id
 * @property array<array-key, mixed> $herramientas_asignadas
 * @property int $total_herramientas
 * @property numeric $valor_total_herramientas
 * @property \Illuminate\Support\Carbon $ultima_actualizacion
 * @property bool $tiene_herramientas_vencidas
 * @property int $dias_promedio_uso
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $alertas
 * @property-read mixed $cantidad_herramientas_vencidas
 * @property-read mixed $herramientas_activas
 * @property-read mixed $herramientas_vencidas
 * @property-read mixed $promedio_valor_por_herramienta
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Herramienta> $herramientas
 * @property-read int|null $herramientas_count
 * @property-read \App\Models\Tecnico|null $tecnico
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResponsabilidadHerramienta conHerramientasVencidas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResponsabilidadHerramienta conMasHerramientas($cantidad)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResponsabilidadHerramienta conValorMayor($valor)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResponsabilidadHerramienta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResponsabilidadHerramienta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResponsabilidadHerramienta query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResponsabilidadHerramienta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResponsabilidadHerramienta whereDiasPromedioUso($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResponsabilidadHerramienta whereHerramientasAsignadas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResponsabilidadHerramienta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResponsabilidadHerramienta whereTecnicoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResponsabilidadHerramienta whereTieneHerramientasVencidas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResponsabilidadHerramienta whereTotalHerramientas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResponsabilidadHerramienta whereUltimaActualizacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResponsabilidadHerramienta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResponsabilidadHerramienta whereValorTotalHerramientas($value)
 */
	class ResponsabilidadHerramienta extends \Eloquent {}
}

namespace App\Models{
/**
 * Catálogo SAT c_ClaveProdServ
 *
 * Claves de productos y servicios para CFDI 4.0
 * Se puede poblar bajo demanda desde XMLs de compras
 *
 * @property string $clave
 * @property string $descripcion
 * @property bool $incluir_iva_trasladado
 * @property bool $incluir_ieps_trasladado
 * @property string|null $complemento
 * @property string|null $palabras_similares
 * @property \Illuminate\Support\Carbon|null $fecha_inicio_vigencia
 * @property \Illuminate\Support\Carbon|null $fecha_fin_vigencia
 * @property bool $activo
 * @property bool $importado_xml
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveProdServ activos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveProdServ buscar($termino)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveProdServ newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveProdServ newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveProdServ query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveProdServ whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveProdServ whereClave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveProdServ whereComplemento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveProdServ whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveProdServ whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveProdServ whereFechaFinVigencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveProdServ whereFechaInicioVigencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveProdServ whereImportadoXml($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveProdServ whereIncluirIepsTrasladado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveProdServ whereIncluirIvaTrasladado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveProdServ wherePalabrasSimilares($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveProdServ whereUpdatedAt($value)
 */
	class SatClaveProdServ extends \Eloquent {}
}

namespace App\Models{
/**
 * Catálogo SAT c_ClaveUnidad
 *
 * Claves de unidad de medida para CFDI 4.0:
 * - H87: Pieza
 * - E48: Unidad de servicio
 * - ACT: Actividad
 * - KGM: Kilogramo
 * - LTR: Litro
 * - MTR: Metro
 * - etc.
 *
 * @property string $clave
 * @property string $nombre
 * @property string|null $descripcion
 * @property string|null $simbolo
 * @property \Illuminate\Support\Carbon|null $fecha_inicio_vigencia
 * @property \Illuminate\Support\Carbon|null $fecha_fin_vigencia
 * @property bool $activo
 * @property bool $uso_comun
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveUnidad activos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveUnidad buscar($termino)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveUnidad comunes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveUnidad newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveUnidad newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveUnidad query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveUnidad whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveUnidad whereClave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveUnidad whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveUnidad whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveUnidad whereFechaFinVigencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveUnidad whereFechaInicioVigencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveUnidad whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveUnidad whereSimbolo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveUnidad whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatClaveUnidad whereUsoComun($value)
 */
	class SatClaveUnidad extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $clave_estado
 * @property string|null $municipio
 * @property string|null $localidad
 * @property bool $estimulo_frontera
 * @property string|null $huso_horario_verano
 * @property string|null $huso_horario_invierno
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SatEstado|null $estado
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatCodigoPostal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatCodigoPostal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatCodigoPostal query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatCodigoPostal whereClaveEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatCodigoPostal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatCodigoPostal whereEstimuloFrontera($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatCodigoPostal whereHusoHorarioInvierno($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatCodigoPostal whereHusoHorarioVerano($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatCodigoPostal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatCodigoPostal whereLocalidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatCodigoPostal whereMunicipio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatCodigoPostal whereUpdatedAt($value)
 */
	class SatCodigoPostal extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $sat_descarga_masiva_id
 * @property string $uuid
 * @property string $direccion
 * @property array<array-key, mixed> $xml_data
 * @property string $xml_content
 * @property bool $importado
 * @property \Illuminate\Support\Carbon|null $fecha_emision
 * @property string|null $rfc_emisor
 * @property string|null $nombre_emisor
 * @property numeric $total
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $rfc_receptor
 * @property string|null $nombre_receptor
 * @property string|null $tipo_comprobante
 * @property int|null $empresa_id
 * @property-read \App\Models\SatDescargaMasiva|null $descargaMasiva
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle whereDireccion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle whereFechaEmision($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle whereImportado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle whereNombreEmisor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle whereNombreReceptor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle whereRfcEmisor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle whereRfcReceptor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle whereSatDescargaMasivaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle whereTipoComprobante($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle whereXmlContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaDetalle whereXmlData($value)
 */
	class SatDescargaDetalle extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $direccion
 * @property \Illuminate\Support\Carbon $fecha_inicio
 * @property \Illuminate\Support\Carbon $fecha_fin
 * @property string $status
 * @property string|null $request_id
 * @property array<array-key, mixed>|null $paquetes
 * @property int $total_cfdis
 * @property int $inserted_cfdis
 * @property int $duplicate_cfdis
 * @property int $error_cfdis
 * @property string|null $last_error
 * @property array<array-key, mixed>|null $errors
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property \Illuminate\Support\Carbon|null $last_checked_at
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \App\Models\User|null $creador
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SatDescargaDetalle> $detalles
 * @property-read int|null $detalles_count
 * @property-read string $mensaje_amigable
 * @property-read string|null $tiempo_para_reintento
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereDireccion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereDuplicateCfdis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereErrorCfdis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereErrors($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereFechaFin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereFechaInicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereFinishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereInsertedCfdis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereLastCheckedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereLastError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva wherePaquetes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereTotalCfdis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatDescargaMasiva whereUpdatedAt($value)
 */
	class SatDescargaMasiva extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $clave
 * @property string $nombre
 * @property string|null $nombre_corto
 * @property bool $activo
 * @property string|null $vigencia_inicio
 * @property string|null $vigencia_fin
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cliente> $clientes
 * @property-read int|null $clientes_count
 * @method static \Database\Factories\SatEstadoFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatEstado newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatEstado newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatEstado query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatEstado whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatEstado whereClave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatEstado whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatEstado whereNombreCorto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatEstado whereVigenciaFin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatEstado whereVigenciaInicio($value)
 */
	class SatEstado extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $clave
 * @property string $descripcion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatExportacion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatExportacion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatExportacion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatExportacion whereClave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatExportacion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatExportacion whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatExportacion whereUpdatedAt($value)
 */
	class SatExportacion extends \Eloquent {}
}

namespace App\Models{
/**
 * Catálogo SAT c_FormaPago
 *
 * Formas de pago para CFDI 4.0:
 * - 01: Efectivo
 * - 02: Cheque nominativo
 * - 03: Transferencia electrónica de fondos
 * - 04: Tarjeta de crédito
 * - 28: Tarjeta de débito
 * - 99: Por definir
 * - etc.
 *
 * @property string $clave
 * @property string $descripcion
 * @property bool $bancarizado
 * @property string|null $patron_cuenta_bancaria
 * @property int $orden
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatFormaPago activos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatFormaPago newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatFormaPago newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatFormaPago ordenado()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatFormaPago query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatFormaPago whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatFormaPago whereBancarizado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatFormaPago whereClave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatFormaPago whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatFormaPago whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatFormaPago whereOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatFormaPago wherePatronCuentaBancaria($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatFormaPago whereUpdatedAt($value)
 */
	class SatFormaPago extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $clave
 * @property string $descripcion
 * @property bool $retencion
 * @property bool $traslado
 * @property string $local_o_federal
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatImpuesto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatImpuesto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatImpuesto query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatImpuesto whereClave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatImpuesto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatImpuesto whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatImpuesto whereLocalOFederal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatImpuesto whereRetencion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatImpuesto whereTraslado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatImpuesto whereUpdatedAt($value)
 */
	class SatImpuesto extends \Eloquent {}
}

namespace App\Models{
/**
 * Catálogo SAT c_MetodoPago
 *
 * Métodos de pago para CFDI 4.0:
 * - PUE: Pago en una sola exhibición
 * - PPD: Pago en parcialidades o diferido
 * - PIP: Pago inicial y parcialidades (CFDI 4.0)
 *
 * @property string $clave
 * @property string $descripcion
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMetodoPago activos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMetodoPago newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMetodoPago newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMetodoPago query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMetodoPago whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMetodoPago whereClave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMetodoPago whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMetodoPago whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMetodoPago whereUpdatedAt($value)
 */
	class SatMetodoPago extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $clave
 * @property string $descripcion
 * @property int $decimales
 * @property float $variacion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMoneda newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMoneda newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMoneda query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMoneda whereClave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMoneda whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMoneda whereDecimales($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMoneda whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMoneda whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMoneda whereVariacion($value)
 */
	class SatMoneda extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $clave
 * @property string $descripcion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMotivoCancelacion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMotivoCancelacion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMotivoCancelacion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMotivoCancelacion whereClave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMotivoCancelacion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMotivoCancelacion whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatMotivoCancelacion whereUpdatedAt($value)
 */
	class SatMotivoCancelacion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $clave
 * @property string $descripcion
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatObjetoImp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatObjetoImp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatObjetoImp query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatObjetoImp whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatObjetoImp whereClave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatObjetoImp whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatObjetoImp whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatObjetoImp whereUpdatedAt($value)
 */
	class SatObjetoImp extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $clave
 * @property string $descripcion
 * @property bool $persona_fisica
 * @property bool $persona_moral
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cliente> $clientes
 * @property-read int|null $clientes_count
 * @method static \Database\Factories\SatRegimenFiscalFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatRegimenFiscal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatRegimenFiscal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatRegimenFiscal query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatRegimenFiscal whereClave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatRegimenFiscal whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatRegimenFiscal wherePersonaFisica($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatRegimenFiscal wherePersonaMoral($value)
 */
	class SatRegimenFiscal extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $rango_o_fijo
 * @property numeric|null $valor_minimo
 * @property numeric|null $valor_maximo
 * @property string $impuesto
 * @property string $factor
 * @property bool $traslado
 * @property bool $retencion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTasaOCuota newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTasaOCuota newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTasaOCuota query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTasaOCuota whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTasaOCuota whereFactor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTasaOCuota whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTasaOCuota whereImpuesto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTasaOCuota whereRangoOFijo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTasaOCuota whereRetencion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTasaOCuota whereTraslado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTasaOCuota whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTasaOCuota whereValorMaximo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTasaOCuota whereValorMinimo($value)
 */
	class SatTasaOCuota extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $clave
 * @property string $descripcion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTipoComprobante newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTipoComprobante newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTipoComprobante query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTipoComprobante whereClave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTipoComprobante whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTipoComprobante whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatTipoComprobante whereUpdatedAt($value)
 */
	class SatTipoComprobante extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $clave
 * @property string $descripcion
 * @property string|null $regimen_fiscal_receptor
 * @property bool $activo
 * @property bool $persona_fisica
 * @property bool $persona_moral
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cliente> $clientes
 * @property-read int|null $clientes_count
 * @method static \Database\Factories\SatUsoCfdiFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatUsoCfdi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatUsoCfdi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatUsoCfdi query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatUsoCfdi whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatUsoCfdi whereClave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatUsoCfdi whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatUsoCfdi wherePersonaFisica($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatUsoCfdi wherePersonaMoral($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SatUsoCfdi whereRegimenFiscalReceptor($value)
 */
	class SatUsoCfdi extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $empresa_id
 * @property string $slug
 * @property string $titulo_h1
 * @property string|null $meta_description
 * @property string|null $hero_image_url
 * @property string|null $hero_title
 * @property string|null $hero_description
 * @property string|null $service_category
 * @property string|null $location
 * @property array<array-key, mixed>|null $features
 * @property array<array-key, mixed>|null $content_blocks
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Empresa $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage whereContentBlocks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage whereFeatures($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage whereHeroDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage whereHeroImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage whereHeroTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage whereMetaDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage whereServiceCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage whereTituloH1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoLandingPage withoutTrashed()
 */
	class SeoLandingPage extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property string $codigo
 * @property int $categoria_id
 * @property numeric $precio
 * @property int $duracion
 * @property string $estado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property numeric $margen_ganancia
 * @property bool $es_instalacion
 * @property numeric $comision_vendedor
 * @property string|null $sat_clave_prod_serv
 * @property string|null $sat_clave_unidad
 * @property string $sat_objeto_imp
 * @property int|null $empresa_id
 * @property-read \App\Models\Categoria|null $categoria
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cita> $citas
 * @property-read int|null $citas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cotizacion> $cotizaciones
 * @property-read int|null $cotizaciones_count
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read mixed $ganancia
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pedido> $pedidos
 * @property-read int|null $pedidos_count
 * @property-read \App\Models\SatClaveProdServ|null $satClaveProdServ
 * @property-read \App\Models\SatClaveUnidad|null $satClaveUnidad
 * @property-read \App\Models\SatObjetoImp|null $satObjetoImp
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Venta> $ventas
 * @property-read int|null $ventas_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio active()
 * @method static \Database\Factories\ServicioFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio whereCategoriaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio whereComisionVendedor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio whereDuracion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio whereEsInstalacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio whereMargenGanancia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio wherePrecio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio whereSatClaveProdServ($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio whereSatClaveUnidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio whereSatObjetoImp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servicio whereUpdatedAt($value)
 */
	class Servicio extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property bool $personal_team
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TeamInvitation> $teamInvitations
 * @property-read int|null $team_invitations_count
 * @property-read \App\Models\Membership|null $membership
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\TeamFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team wherePersonalTeam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereUserId($value)
 */
	class Team extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $team_id
 * @property string $email
 * @property string|null $role
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Team|null $team
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamInvitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamInvitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamInvitation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamInvitation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamInvitation whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamInvitation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamInvitation whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamInvitation whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamInvitation whereUpdatedAt($value)
 */
	class TeamInvitation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string $apellido
 * @property string $email
 * @property string|null $telefono
 * @property string|null $direccion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $activo
 * @property int|null $user_id
 * @property numeric $margen_venta_productos
 * @property numeric $margen_venta_servicios
 * @property numeric $comision_instalacion
 * @property-read \App\Models\User|null $user
 * @method static \Database\Factories\TecnicoFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tecnico newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tecnico newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tecnico query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tecnico whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tecnico whereApellido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tecnico whereComisionInstalacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tecnico whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tecnico whereDireccion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tecnico whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tecnico whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tecnico whereMargenVentaProductos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tecnico whereMargenVentaServicios($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tecnico whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tecnico whereTelefono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tecnico whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tecnico whereUserId($value)
 */
	class Tecnico extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $folio
 * @property string|null $scheduled_at
 * @property string|null $completed_at
 * @property int $cliente_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string $estado
 * @property int|null $user_id
 * @property int|null $asignado_id
 * @property string|null $titulo
 * @property string|null $descripcion
 * @property string|null $telefono_contacto
 * @property string|null $email_contacto
 * @property string|null $nombre_contacto
 * @property int|null $empresa_id
 * @property string|null $folio_manual Folio físico o externo para referencia
 * @property array<array-key, mixed>|null $archivos Rutas de archivos adjuntos (imágenes WebP)
 * @property string $tipo_servicio garantia, costo
 * @property string|null $prioridad
 * @property string|null $attachments
 * @property string|null $numero
 * @property \Illuminate\Support\Carbon|null $resuelto_at
 * @property \Illuminate\Support\Carbon|null $fecha_limite
 * @property \Illuminate\Support\Carbon|null $primera_respuesta_at
 * @property int|null $categoria_id
 * @property int|null $poliza_id
 * @property numeric|null $horas_trabajadas Horas trabajadas en el ticket
 * @property string $origen
 * @property \Illuminate\Support\Carbon|null $servicio_inicio_at
 * @property \Illuminate\Support\Carbon|null $servicio_fin_at
 * @property \Illuminate\Support\Carbon|null $cerrado_at
 * @property int|null $producto_id
 * @property int|null $venta_id
 * @property string|null $notas_internas
 * @property \Illuminate\Support\Carbon|null $consumo_registrado_at
 * @property-read \App\Models\User|null $asignado
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \OwenIt\Auditing\Models\Audit> $audits
 * @property-read int|null $audits_count
 * @property-read \App\Models\TicketCategory|null $categoria
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cita> $citas
 * @property-read int|null $citas_count
 * @property-read \App\Models\Cliente $cliente
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TicketComment> $comentarios
 * @property-read int|null $comentarios_count
 * @property-read \App\Models\User|null $creador
 * @property-read \App\Models\EmpresaConfiguracion|null $empresa
 * @property-read float|null $duracion_servicio
 * @property-read bool $is_vip
 * @property-read string $sla_status
 * @property-read string $tiempo_abierto
 * @property-read \App\Models\PolizaServicio|null $poliza
 * @property-read \App\Models\Producto|null $producto
 * @property-read \App\Models\Venta|null $venta
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket abiertos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket asignadoA($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket cerrados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket sinAsignar()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket vencidos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereArchivos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereAsignadoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereAttachments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereCategoriaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereCerradoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereConsumoRegistradoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereEmailContacto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereFechaLimite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereFolio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereFolioManual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereHorasTrabajadas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereNombreContacto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereNotasInternas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereNumero($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereOrigen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket wherePolizaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket wherePrimeraRespuestaAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket wherePrioridad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereProductoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereResueltoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereScheduledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereServicioFinAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereServicioInicioAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereTelefonoContacto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereTipoServicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereTitulo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereVentaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket withoutTrashed()
 */
	class Ticket extends \Eloquent implements \OwenIt\Auditing\Contracts\Auditable {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $empresa_id
 * @property string $nombre
 * @property string|null $descripcion
 * @property string $color
 * @property string $icono
 * @property int $sla_horas
 * @property int $orden
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $consume_poliza Determina si los tickets de esta categoría descuentan folios de la póliza
 * @property int|null $servicio_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\KnowledgeBaseArticle> $articulos
 * @property-read int|null $articulos_count
 * @property-read \App\Models\Empresa $empresa
 * @property-read \App\Models\Servicio|null $servicio
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 * @property-read int|null $tickets_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketCategory activas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketCategory ordenadas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketCategory whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketCategory whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketCategory whereConsumePoliza($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketCategory whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketCategory whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketCategory whereIcono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketCategory whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketCategory whereOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketCategory whereServicioId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketCategory whereSlaHoras($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketCategory whereUpdatedAt($value)
 */
	class TicketCategory extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $ticket_id
 * @property int|null $user_id
 * @property string $contenido
 * @property bool $es_interno
 * @property string $tipo
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Ticket $ticket
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketComment internos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketComment publicos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketComment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketComment respuestas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketComment whereContenido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketComment whereEsInterno($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketComment whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketComment whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketComment whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketComment whereUserId($value)
 */
	class TicketComment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $producto_id
 * @property int $almacen_origen_id
 * @property int $almacen_destino_id
 * @property int|null $cantidad
 * @property string|null $observaciones
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $estado
 * @property int|null $usuario_autoriza
 * @property int|null $usuario_envia
 * @property int|null $usuario_recibe
 * @property \Illuminate\Support\Carbon|null $fecha_envio
 * @property \Illuminate\Support\Carbon|null $fecha_recepcion
 * @property string|null $referencia
 * @property numeric|null $costo_transporte
 * @property int|null $empresa_id
 * @property string|null $folio
 * @property int $cantidad_total
 * @property-read \App\Models\Almacen|null $almacenDestino
 * @property-read \App\Models\Almacen|null $almacenOrigen
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read int $productos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TraspasoItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\Producto|null $producto
 * @property-read \App\Models\User|null $usuarioAutoriza
 * @property-read \App\Models\User|null $usuarioEnvia
 * @property-read \App\Models\User|null $usuarioRecibe
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereAlmacenDestinoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereAlmacenOrigenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereCantidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereCantidadTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereCostoTransporte($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereFechaEnvio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereFechaRecepcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereFolio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereObservaciones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereProductoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereReferencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereUsuarioAutoriza($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereUsuarioEnvia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traspaso whereUsuarioRecibe($value)
 */
	class Traspaso extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cuenta_origen_id
 * @property int $cuenta_destino_id
 * @property numeric $monto
 * @property \Illuminate\Support\Carbon $fecha
 * @property string|null $referencia
 * @property string|null $notas
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $empresa_id
 * @property-read \App\Models\CuentaBancaria|null $destino
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\CuentaBancaria|null $origen
 * @property-read \App\Models\User|null $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoBancario newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoBancario newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoBancario onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoBancario query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoBancario whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoBancario whereCuentaDestinoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoBancario whereCuentaOrigenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoBancario whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoBancario whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoBancario whereFecha($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoBancario whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoBancario whereMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoBancario whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoBancario whereReferencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoBancario whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoBancario whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoBancario withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoBancario withoutTrashed()
 */
	class TraspasoBancario extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $traspaso_id
 * @property int $producto_id
 * @property int $cantidad
 * @property array<array-key, mixed>|null $series_ids
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $empresa_id
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\Producto|null $producto
 * @property-read \App\Models\Traspaso|null $traspaso
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoItem whereCantidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoItem whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoItem whereProductoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoItem whereSeriesIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoItem whereTraspasoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TraspasoItem whereUpdatedAt($value)
 */
	class TraspasoItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string|null $abreviatura
 * @property string|null $descripcion
 * @property string $estado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $empresa_id
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Producto> $productos
 * @property-read int|null $productos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida activas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida whereAbreviatura($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida withoutTrashed()
 */
	class UnidadMedida extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property string|null $profile_photo_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property bool $activo
 * @property string|null $telefono
 * @property \Illuminate\Support\Carbon|null $fecha_nacimiento
 * @property string|null $curp
 * @property string|null $rfc
 * @property string|null $direccion
 * @property string|null $nss
 * @property string|null $puesto
 * @property string|null $departamento
 * @property \Illuminate\Support\Carbon|null $fecha_contratacion
 * @property numeric|null $salario
 * @property string|null $tipo_contrato
 * @property string|null $numero_empleado
 * @property string|null $contacto_emergencia_nombre
 * @property string|null $contacto_emergencia_telefono
 * @property string|null $contacto_emergencia_parentesco
 * @property string|null $banco
 * @property string|null $numero_cuenta
 * @property string|null $clabe_interbancaria
 * @property string|null $observaciones
 * @property bool $es_empleado
 * @property int|null $almacen_venta_id
 * @property int|null $almacen_compra_id
 * @property bool $es_tecnico
 * @property bool $es_vendedor
 * @property numeric|null $margen_venta_productos
 * @property numeric|null $margen_venta_servicios
 * @property numeric|null $comision_instalacion
 * @property numeric|null $salario_base
 * @property string|null $tipo_jornada
 * @property int|null $horas_jornada
 * @property string|null $hora_entrada
 * @property string|null $hora_salida
 * @property bool $trabaja_sabado
 * @property string|null $hora_entrada_sabado
 * @property string|null $hora_salida_sabado
 * @property string|null $frecuencia_pago
 * @property string|null $contrato_adjunto
 * @property int|null $empresa_id
 * @property string|null $microsoft_token
 * @property string|null $microsoft_refresh_token
 * @property string|null $microsoft_token_expires_at
 * @property numeric $costo_hora_interno
 * @property string|null $checkin_token
 * @property string|null $face_reference_path
 * @property \Illuminate\Support\Carbon|null $face_enrolled_at
 * @property \Illuminate\Support\Carbon|null $face_last_verified_at
 * @property string|null $face_provider
 * @property array<array-key, mixed>|null $face_descriptor
 * @property string|null $ine
 * @property string|null $imss
 * @property array<array-key, mixed>|null $dias_trabajo
 * @property array<array-key, mixed>|null $dias_descanso
 * @property string|null $rustdesk_id
 * @property string|null $rustdesk_alias
 * @property string|null $username
 * @property-read \App\Models\Almacen|null $almacen_compra
 * @property-read \App\Models\Almacen|null $almacen_venta
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AsignacionHerramienta> $asignacionesHerramientas
 * @property-read int|null $asignaciones_herramientas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cita> $citasAsignadas
 * @property-read int|null $citas_asignadas_count
 * @property-read \App\Models\Team|null $currentTeam
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read mixed $antiguedad
 * @property-read mixed $dias_vacaciones_correspondientes
 * @property-read mixed $dias_vacaciones_disponibles
 * @property-read mixed $edad
 * @property-read mixed $ganancia_total
 * @property-read bool $has_microsoft_token
 * @property-read mixed $nombre_completo
 * @property-read string $profile_photo_url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Herramienta> $herramientas
 * @property-read int|null $herramientas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HistorialHerramienta> $historialHerramientas
 * @property-read int|null $historial_herramientas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Proyecto> $joinedProjects
 * @property-read int|null $joined_projects_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Nomina> $nominas
 * @property-read int|null $nominas_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Proyecto> $ownedProjects
 * @property-read int|null $owned_projects_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Team> $ownedTeams
 * @property-read int|null $owned_teams_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Prestamo> $prestamos
 * @property-read int|null $prestamos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RegistroVacaciones> $registroVacaciones
 * @property-read int|null $registro_vacaciones_count
 * @property-read \App\Models\RegistroVacaciones|null $registroVacacionesActual
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \App\Models\Membership|null $membership
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Team> $teams
 * @property-read int|null $teams_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $ticketsAsignados
 * @property-read int|null $tickets_asignados_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $ticketsReportados
 * @property-read int|null $tickets_reportados_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Vacacion> $vacaciones
 * @property-read int|null $vacaciones_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Vacacion> $vacacionesAprobadas
 * @property-read int|null $vacaciones_aprobadas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Venta> $ventas
 * @property-read int|null $ventas_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User activos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User empleados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User empleadosActivos()
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User filter(array $filters)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User tecnicos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User tecnicosActivos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User vendedores()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User vendedoresActivos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAlmacenCompraId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAlmacenVentaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBanco($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCheckinToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereClabeInterbancaria($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereComisionInstalacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereContactoEmergenciaNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereContactoEmergenciaParentesco($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereContactoEmergenciaTelefono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereContratoAdjunto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCostoHoraInterno($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCurp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCurrentTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDepartamento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDiasDescanso($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDiasTrabajo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDireccion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEsEmpleado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEsTecnico($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEsVendedor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFaceDescriptor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFaceEnrolledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFaceLastVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFaceProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFaceReferencePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFechaContratacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFechaNacimiento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFrecuenciaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereHoraEntrada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereHoraEntradaSabado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereHoraSalida($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereHoraSalidaSabado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereHorasJornada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereImss($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIne($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMargenVentaProductos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMargenVentaServicios($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMicrosoftRefreshToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMicrosoftToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMicrosoftTokenExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereNss($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereNumeroCuenta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereNumeroEmpleado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereObservaciones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProfilePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePuesto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRfc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRustdeskAlias($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRustdeskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSalario($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSalarioBase($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTelefono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTipoContrato($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTipoJornada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTrabajaSabado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property string|null $message
 * @property array<array-key, mixed>|null $data
 * @property string|null $action_url
 * @property string|null $icon
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read bool $read
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification byType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification read()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification unread()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereActionUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification withoutTrashed()
 */
	class UserNotification extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $fecha_inicio
 * @property \Illuminate\Support\Carbon $fecha_fin
 * @property int $dias_solicitados
 * @property int $dias_pendientes
 * @property int $dias_aprobados
 * @property int $dias_rechazados
 * @property string|null $motivo
 * @property string $estado
 * @property string|null $observaciones
 * @property int|null $aprobador_id
 * @property \Illuminate\Support\Carbon|null $fecha_aprobacion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $folio
 * @property-read \App\Models\User|null $aprobador
 * @property-read \App\Models\User|null $empleado
 * @property-read mixed $dias_totales
 * @property-read mixed $estado_color
 * @property-read mixed $estado_label
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion aprobadas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion delEmpleado($empleadoId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion pendientes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion rechazadas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion whereAprobadorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion whereDiasAprobados($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion whereDiasPendientes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion whereDiasRechazados($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion whereDiasSolicitados($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion whereFechaAprobacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion whereFechaFin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion whereFechaInicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion whereFolio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion whereMotivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion whereObservaciones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vacacion whereUserId($value)
 */
	class Vacacion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cliente_id
 * @property int|null $factura_id
 * @property string $numero_venta
 * @property numeric $subtotal
 * @property numeric $descuento_general
 * @property numeric $iva
 * @property numeric $total
 * @property \Illuminate\Support\Carbon $fecha
 * @property \App\Enums\EstadoVenta $estado
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property bool $pagado
 * @property string|null $metodo_pago
 * @property \Illuminate\Support\Carbon|null $fecha_pago
 * @property string|null $notas_pago
 * @property int|null $pagado_por
 * @property string|null $vendedor_type
 * @property int|null $vendedor_id
 * @property int|null $cotizacion_id
 * @property int|null $pedido_id
 * @property int $almacen_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property numeric $isr
 * @property int|null $cuenta_bancaria_id
 * @property numeric $retencion_iva
 * @property numeric $retencion_isr
 * @property string|null $forma_pago_sat
 * @property string|null $metodo_pago_sat
 * @property int|null $empresa_id
 * @property string|null $folio
 * @property int|null $cita_id
 * @property-read \App\Models\Almacen|null $almacen
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cfdi> $cfdis
 * @property-read int|null $cfdis_count
 * @property-read \App\Models\Cita|null $cita
 * @property-read \App\Models\Cliente|null $cliente
 * @property-read \App\Models\Cotizacion|null $cotizacion
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\CuentaBancaria|null $cuentaBancaria
 * @property-read \App\Models\CuentasPorCobrar|null $cuentaPorCobrar
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\EntregaDinero|null $entregaDinero
 * @property-read mixed $cfdi_actual
 * @property-read mixed $ganancia_total
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VentaItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\User|null $pagadoPor
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Producto> $productos
 * @property-read int|null $productos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VentaItemSerie> $series
 * @property-read int|null $series_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Servicio> $servicios
 * @property-read int|null $servicios_count
 * @property-read \App\Models\User|null $updatedBy
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $vendedor
 * @method static \Database\Factories\VentaFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereAlmacenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereCitaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereCotizacionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereCuentaBancariaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereDescuentoGeneral($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereFacturaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereFecha($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereFechaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereFolio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereFormaPagoSat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereIsr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereIva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereMetodoPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereMetodoPagoSat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereNotas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereNotasPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereNumeroVenta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta wherePagado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta wherePagadoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta wherePedidoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereRetencionIsr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereRetencionIva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereVendedorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta whereVendedorType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venta withoutTrashed()
 */
	class Venta extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $venta_id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $status_before
 * @property string|null $status_after
 * @property array<array-key, mixed>|null $changes
 * @property string|null $notes
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Venta|null $venta
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaAuditLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaAuditLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaAuditLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaAuditLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaAuditLog whereChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaAuditLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaAuditLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaAuditLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaAuditLog whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaAuditLog whereStatusAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaAuditLog whereStatusBefore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaAuditLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaAuditLog whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaAuditLog whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaAuditLog whereVentaId($value)
 */
	class VentaAuditLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $venta_id
 * @property string $ventable_type
 * @property int $ventable_id
 * @property int $cantidad
 * @property numeric $precio
 * @property numeric $descuento
 * @property numeric $subtotal
 * @property numeric $descuento_monto
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property numeric|null $costo_unitario
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $price_list_id
 * @property int|null $empresa_id
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read mixed $serial_numbers
 * @property-read \App\Models\PriceList|null $priceList
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VentaItemSerie> $series
 * @property-read int|null $series_count
 * @property-read \App\Models\Venta|null $venta
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $ventable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem whereCantidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem whereCostoUnitario($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem whereDescuento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem whereDescuentoMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem wherePrecio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem wherePriceListId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem whereVentaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem whereVentableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem whereVentableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItem withoutTrashed()
 */
	class VentaItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $venta_item_id
 * @property int $producto_serie_id
 * @property string $numero_serie
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $empresa_id
 * @property-read \App\Models\Almacen|null $almacen
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\ProductoSerie|null $productoSerie
 * @property-read \App\Models\VentaItem|null $ventaItem
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItemSerie newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItemSerie newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItemSerie onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItemSerie query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItemSerie whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItemSerie whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItemSerie whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItemSerie whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItemSerie whereNumeroSerie($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItemSerie whereProductoSerieId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItemSerie whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItemSerie whereVentaItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItemSerie withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VentaItemSerie withoutTrashed()
 */
	class VentaItemSerie extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $empresa_id
 * @property string $to
 * @property string|null $template_name
 * @property array<array-key, mixed>|null $template_params
 * @property string|null $message_id
 * @property string|null $status
 * @property array<array-key, mixed>|null $response
 * @property string|null $error_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Empresa|null $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage byEmpresa($empresaId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage byStatus($status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage recent($days = 7)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereErrorCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereTemplateName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereTemplateParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereUpdatedAt($value)
 */
	class WhatsAppMessage extends \Eloquent {}
}

