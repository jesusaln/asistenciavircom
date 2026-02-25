<?php

namespace App\Http\Controllers;

use App\Contracts\FaceVerificationService;
use App\Models\User;
use App\Models\Almacen;
use App\Models\AsistenciaRegistro;
use App\Models\EmpresaConfiguracion;
use App\Services\GeocodingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class AsistenciaController extends Controller
{
    /**
     * Muestra la pantalla de marcaje (Checador)
     */
    public function index(Request $request): Response
    {
        // Si es admin, redirigir a los logs por defecto
        if (Auth::user()->is_admin || Auth::user()->hasAnyRole(['admin', 'super-admin'])) {
            return $this->logs($request);
        }
        return $this->renderCheckView(Auth::user());
    }

    /**
     * Muestra la bitácora de registros de asistencia (Vista Admin)
     */
    public function logs(Request $request): Response
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $rangeStart = Carbon::parse($dateFrom)->startOfDay();
        $rangeEnd = Carbon::parse($dateTo)->endOfDay();

        $query = AsistenciaRegistro::with(['user:id,name,profile_photo_path', 'almacen:id,nombre'])
            ->whereBetween('registrado_at', [$rangeStart, $rangeEnd]);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('incidencia')) {
            $query->where('es_incidencia', $request->boolean('incidencia'));
        }

        $registros = $query->orderByDesc('registrado_at')->paginate(50)->withQueryString();

        // Stats for dashboard cards
        $statsQuery = AsistenciaRegistro::whereBetween('registrado_at', [$rangeStart, $rangeEnd]);
        if ($request->filled('user_id')) {
            $statsQuery->where('user_id', $request->user_id);
        }

        $totalRecords = (clone $statsQuery)->count();
        $totalEntries = (clone $statsQuery)->where('tipo', 'entry')->count();
        $totalExits = (clone $statsQuery)->where('tipo', 'exit')->count();
        $totalIncidencias = (clone $statsQuery)->where('es_incidencia', true)->count();
        $totalFaceVerified = (clone $statsQuery)->where('face_verified', true)->count();
        $uniqueEmployees = (clone $statsQuery)->distinct('user_id')->count('user_id');

        return Inertia::render('Asistencia/Logs', [
            'registros' => $registros,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'user_id' => $request->user_id,
                'tipo' => $request->tipo,
                'incidencia' => $request->incidencia,
            ],
            'users' => User::select('id', 'name')->where('es_empleado', true)->orderBy('name')->get(),
            'stats' => [
                'total' => $totalRecords,
                'entries' => $totalEntries,
                'exits' => $totalExits,
                'incidencias' => $totalIncidencias,
                'faceVerified' => $totalFaceVerified,
                'faceVerifiedPct' => $totalRecords > 0 ? round(($totalFaceVerified / $totalRecords) * 100) : 0,
                'uniqueEmployees' => $uniqueEmployees,
            ],
        ]);
    }

    /**
     * Muestra la pantalla de marcaje (Forzada)
     */
    public function checador(Request $request): Response
    {
        return $this->renderCheckView(Auth::user());
    }

    /**
     * Muestra la pantalla de marcaje mediante token (sin login)
     */
    public function showByToken(string $token): Response
    {
        $user = User::withoutGlobalScope('empresa')->where('checkin_token', $token)->firstOrFail();
        return $this->renderCheckView($user, $token);
    }

    /**
     * Prepara los datos para la vista de marcaje
     */
    private function renderCheckView(User $user, ?string $token = null): Response
    {
        $config = EmpresaConfiguracion::getConfig($user->empresa_id);
        $timezone = 'America/Hermosillo'; // Default for the region
        $now = now($timezone);

        // Obtener el último registro para sugerir el siguiente paso
        $lastCheck = AsistenciaRegistro::where('user_id', $user->id)
            ->where('empresa_id', $user->empresa_id)
            ->orderByDesc('registrado_at')
            ->orderByDesc('id')
            ->first();

        $suggestedType = 'entry';
        if ($lastCheck) {
            $suggestedType = match ($lastCheck->tipo) {
                'entry' => 'break_start', // Sugerimos descanso o salida
                'break_start' => 'break_end',
                'break_end' => 'exit',
                'exit' => 'entry',
                default => 'entry',
            };
        }

        // Si el usuario no tiene habilitado descansos, saltar a exit
        $tieneDescansos = true; // Por ahora por defecto, podríamos sacarlo de User meta

        // Almacén asignado para geocerca
        $almacen = $user->almacenVenta ?: Almacen::where('empresa_id', $user->empresa_id)->first();

        // Registros del día para el timeline
        $todayRecords = AsistenciaRegistro::where('user_id', $user->id)
            ->whereDate('registrado_at', $now->toDateString())
            ->orderBy('registrado_at')
            ->get(['id', 'tipo', 'registrado_at', 'es_incidencia', 'face_verified', 'direccion']);

        // Calcular horas trabajadas del día
        $workedMinutes = 0;
        $breakMinutes = 0;
        $entryTime = null;
        $breakStart = null;
        foreach ($todayRecords as $rec) {
            $recTime = Carbon::parse($rec->registrado_at);
            if ($rec->tipo === 'entry') {
                $entryTime = $recTime;
            } elseif ($rec->tipo === 'break_start' && $entryTime) {
                $breakStart = $recTime;
            } elseif ($rec->tipo === 'break_end' && $breakStart) {
                $breakMinutes += $breakStart->diffInMinutes($recTime);
                $breakStart = null;
            } elseif ($rec->tipo === 'exit' && $entryTime) {
                $workedMinutes += $entryTime->diffInMinutes($recTime);
                $entryTime = null;
            }
        }
        // Si aún no ha salido, calcular hasta ahora
        if ($entryTime) {
            $workedMinutes += $entryTime->diffInMinutes($now);
        }
        if ($breakStart) {
            $breakMinutes += $breakStart->diffInMinutes($now);
        }
        $netWorkedMinutes = max(0, $workedMinutes - $breakMinutes);

        return Inertia::render('Asistencia/Checador', [
            'employee' => [
                'id' => $user->id,
                'name' => $user->name,
                'puesto' => $user->puesto,
                'profilePhoto' => $user->profile_photo_url ?? null,
                'almacen' => $almacen?->nombre,
                'almacen_coords' => $almacen && $almacen->latitud && $almacen->longitud ? [
                    'lat' => (float) $almacen->latitud,
                    'lng' => (float) $almacen->longitud,
                    'radius' => (int) $almacen->geocerca_radio,
                ] : null,
            ],
            'companyName' => $config->nombre_empresa,
            'serverNowIso' => $now->toIso8601String(),
            'suggestedType' => $suggestedType,
            'token' => $token,
            'biometric' => [
                'is_enrolled' => (bool) $user->face_enrolled_at,
                'strict_match' => (bool) ($config->biometrics_strict_match ?? config('services.biometrics.strict_match', false)),
                'has_face_descriptor' => !empty($user->face_descriptor),
            ],
            'checkTypes' => [
                ['value' => 'entry', 'label' => 'Entrada'],
                ['value' => 'break_start', 'label' => 'Inicio Descanso'],
                ['value' => 'break_end', 'label' => 'Fin Descanso'],
                ['value' => 'exit', 'label' => 'Salida'],
            ],
            'todayRecords' => $todayRecords,
            'todaySummary' => [
                'workedMinutes' => $netWorkedMinutes,
                'breakMinutes' => $breakMinutes,
                'totalChecks' => $todayRecords->count(),
                'hasIncidence' => $todayRecords->contains('es_incidencia', true),
            ],
        ]);
    }

    /**
     * Procesa el registro de asistencia
     */
    public function store(Request $request): RedirectResponse
    {
        $user = null;
        $tokenMode = $request->filled('token');
        if ($tokenMode) {
            $user = User::withoutGlobalScope('empresa')->where('checkin_token', $request->input('token'))->firstOrFail();
        } else {
            $user = Auth::user();
        }

        if (!$user) {
            return back()->withErrors(['auth' => 'Sesión no válida.']);
        }
        $companyConfig = EmpresaConfiguracion::getConfig($user->empresa_id);
        $biometricProvider = $companyConfig->biometrics_provider ?? config('services.biometrics.provider', 'mock');

        if ($tokenMode && $biometricProvider === 'mock') {
            return back()->withErrors([
                'selfie' => 'El acceso por enlace requiere un proveedor biométrico server-side configurado (modo mock no permitido).',
            ]);
        }

        $validated = $request->validate([
            'tipo' => 'required|in:entry,exit,break_start,break_end',
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180',
            'precision_metros' => 'nullable|integer',
            'selfie' => 'required|image|max:5120',
            'notas' => 'nullable|string|max:500',
            'consentimiento' => 'required|accepted',
            'face_challenge_completed' => 'nullable|boolean',
            'face_liveness_score' => 'nullable|numeric|between:0,1',
            'face_descriptor' => 'nullable|string|max:10000',
            'face_detected_count' => 'nullable|integer|min:0|max:20',
            'face_capture_quality_passed' => 'nullable|boolean',
            'face_quality_brightness' => 'nullable|numeric|between:0,1',
            'face_quality_sharpness' => 'nullable|numeric|between:0,1',
            'face_quality_area_ratio' => 'nullable|numeric|between:0,1',
            'face_quality_center_offset' => 'nullable|numeric|between:0,1',
            'face_quality_message' => 'nullable|string|max:255',
        ]);

        $requiresLocation = (bool) ($companyConfig->biometrics_require_location ?? config('services.biometrics.require_location', true));
        if (
            $requiresLocation
            && (($validated['latitud'] ?? null) === null || ($validated['longitud'] ?? null) === null)
        ) {
            return back()->withErrors([
                'latitud' => 'Debes activar ubicación GPS para registrar asistencia.',
            ]);
        }

        if ($tokenMode && (!$user->face_enrolled_at || empty($user->face_descriptor))) {
            return back()->withErrors([
                'selfie' => 'Este enlace no puede enrolar rostros nuevos. Solicita activación inicial con tu cuenta.',
            ]);
        }

        // Enforzar secuencia de checada durante el día
        $sequenceToday = now('America/Hermosillo')->toDateString();
        $sequenceLastCheck = AsistenciaRegistro::where('user_id', $user->id)
            ->whereDate('registrado_at', $sequenceToday)
            ->orderByDesc('registrado_at')
            ->orderByDesc('id')
            ->first();

        if ($sequenceLastCheck) {
            $allowedTransitions = [
                'entry' => ['break_start', 'exit'],
                'break_start' => ['break_end'],
                'break_end' => ['exit'],
                'exit' => ['entry'],
            ];
            $allowedNext = $allowedTransitions[$sequenceLastCheck->tipo] ?? ['entry'];
            if (!in_array($validated['tipo'], $allowedNext, true)) {
                return back()->withErrors([
                    'tipo' => 'Secuencia inválida. Después de "' . $sequenceLastCheck->tipo . '" solo puedes registrar: ' . implode(', ', $allowedNext) . '.',
                ]);
            }
        } elseif ($validated['tipo'] !== 'entry') {
            return back()->withErrors([
                'tipo' => 'La primera checada del día debe ser "entry".',
            ]);
        }

        // Evitar duplicados rápidos (anti-doble-click)
        $lastCheck = AsistenciaRegistro::where('user_id', $user->id)
            ->orderByDesc('registrado_at')
            ->first();

        if ($lastCheck && $lastCheck->tipo === $validated['tipo'] && now()->diffInMinutes($lastCheck->registrado_at) < 5) {
            return back()->withErrors(['tipo' => 'Ya registraste ' . $validated['tipo'] . ' hace menos de 5 minutos.']);
        }

        // Geocerca
        $esIncidencia = false;
        $motivoIncidencia = null;
        $almacen = $user->almacenVenta ?: Almacen::where('empresa_id', $user->empresa_id)->first();
        $distanceFromOffice = null;
        $baseGeofenceRadius = $almacen?->geocerca_radio ? (float) $almacen->geocerca_radio : null;
        $softGeofenceMargin = (float) ($companyConfig->biometrics_geofence_soft_margin_meters ?? config('services.biometrics.geofence_soft_margin_meters', 120));

        if (
            $almacen
            && $almacen->latitud
            && $almacen->longitud
            && (($validated['latitud'] ?? null) !== null)
            && (($validated['longitud'] ?? null) !== null)
        ) {
            $distancia = $this->calculateDistance(
                (float) $almacen->latitud,
                (float) $almacen->longitud,
                (float) $validated['latitud'],
                (float) $validated['longitud']
            );
            $distanceFromOffice = $distancia;
            $effectiveRadius = ($baseGeofenceRadius ?? 200) + $softGeofenceMargin;

            if ($distancia > $effectiveRadius) {
                $esIncidencia = true;
                $motivoIncidencia = "Fuera de zona: a " . round($distancia) . "m de " . $almacen->nombre;
            } elseif ($distancia > ($baseGeofenceRadius ?? 200)) {
                $motivoIncidencia = "Zona extendida: a " . round($distancia) . "m de " . $almacen->nombre . " (margen permitido).";
            }
        }

        // Selfie (siempre requerida)
        $selfiePath = null;
        if ($request->hasFile('selfie')) {
            $selfiePath = $request->file('selfie')->store('asistencias/selfies', 'public');
        }

        // Biometría facial
        $faceStatus = 'pending';
        $faceVerified = false;
        $faceMatchScore = null;
        $faceLivenessScore = null;
        $faceProvider = null;
        $faceNotes = null;
        $strictFaceMatch = $tokenMode
            ? true
            : (bool) ($companyConfig->biometrics_strict_match ?? config('services.biometrics.strict_match', false));
        $challengeCompleted = (bool) ($validated['face_challenge_completed'] ?? false);
        $faceLivenessScore = isset($validated['face_liveness_score']) ? (float) $validated['face_liveness_score'] : null;
        $trustClientDescriptor = (bool) ($companyConfig->biometrics_trust_client_descriptor ?? config('services.biometrics.trust_client_descriptor', false));
        // En web_panel autenticado, si el proveedor es mock, permitimos fallback local
        // para no dejar todos los registros en "pendiente" mientras se despliega proveedor real.
        if (!$tokenMode && $biometricProvider === 'mock') {
            $trustClientDescriptor = true;
        }
        $incomingDescriptor = $trustClientDescriptor
            ? $this->parseFaceDescriptor($validated['face_descriptor'] ?? null)
            : null;
        $baseMatchThreshold = (float) ($companyConfig->biometrics_local_match_threshold ?? config('services.biometrics.local_match_threshold', 0.72));
        $baseLivenessThreshold = (float) ($companyConfig->biometrics_local_liveness_threshold ?? config('services.biometrics.local_liveness_threshold', 0.45));
        $nearbyMatchRelax = (float) ($companyConfig->biometrics_nearby_match_relax ?? config('services.biometrics.nearby_match_relax', 0.06));
        $nearbyLivenessRelax = (float) ($companyConfig->biometrics_nearby_liveness_relax ?? config('services.biometrics.nearby_liveness_relax', 0.10));
        $farMatchPenalty = (float) ($companyConfig->biometrics_far_match_penalty ?? config('services.biometrics.far_match_penalty', 0.06));
        $farLivenessPenalty = (float) ($companyConfig->biometrics_far_liveness_penalty ?? config('services.biometrics.far_liveness_penalty', 0.10));

        $matchThreshold = $baseMatchThreshold;
        $livenessThreshold = $baseLivenessThreshold;

        if ($distanceFromOffice !== null && $baseGeofenceRadius !== null) {
            if ($distanceFromOffice <= $baseGeofenceRadius) {
                $matchThreshold = max(0.50, $baseMatchThreshold - $nearbyMatchRelax);
                $livenessThreshold = max(0.30, $baseLivenessThreshold - $nearbyLivenessRelax);
            } elseif ($distanceFromOffice > ($baseGeofenceRadius + $softGeofenceMargin)) {
                $matchThreshold = min(0.95, $baseMatchThreshold + $farMatchPenalty);
                $livenessThreshold = min(0.95, $baseLivenessThreshold + $farLivenessPenalty);
            }
        }

        $fallbackLivenessThreshold = max(0.30, $livenessThreshold - 0.15);
        $challengeGatePass = $challengeCompleted || (!$strictFaceMatch && $faceLivenessScore !== null && $faceLivenessScore >= $fallbackLivenessThreshold);
        $challengeMode = $challengeCompleted ? 'challenge' : 'fallback';

        /** @var FaceVerificationService $faceService */
        $faceService = app(FaceVerificationService::class);

        if ($selfiePath) {
            $selfieAbsolutePath = Storage::disk('public')->path($selfiePath);

            if (!$user->face_enrolled_at || empty($user->face_descriptor)) {
                $enrollResult = $faceService->enroll($user, $selfieAbsolutePath);
                $providerStatus = $enrollResult['status'] ?? 'pending';
                $providerAccepted = in_array($providerStatus, ['enrolled', 'verified'], true);
                $localAccepted = $trustClientDescriptor && !empty($incomingDescriptor);

                $faceProvider = $enrollResult['provider'] ?? ($localAccepted ? 'local' : 'mock');
                $faceNotes = $enrollResult['message'] ?? 'Intento de enrolamiento biométrico.';
                $faceMatchScore = $localAccepted ? 1.0 : ($enrollResult['match_score'] ?? null);
                $faceStatus = ($providerAccepted || $localAccepted) ? 'enrolled' : 'pending';
                $faceVerified = ($providerAccepted || $localAccepted) && $challengeGatePass;

                if (!$providerAccepted && !$localAccepted) {
                    $faceNotes = trim(($faceNotes ? $faceNotes . ' | ' : '') . 'Enrolamiento rechazado: no hay descriptor local confiable.');
                }

                if ($providerAccepted || $localAccepted) {
                    $user->forceFill([
                        'face_reference_path' => $selfiePath,
                        'face_descriptor' => $localAccepted ? $incomingDescriptor : $user->face_descriptor,
                        'face_enrolled_at' => now(),
                        'face_last_verified_at' => now(),
                        'face_provider' => $faceProvider ?: 'local',
                    ])->save();
                }
            } else {
                $providerStatus = 'pending';
                $providerNotes = null;
                $providerMatchScore = null;
                $referenceAbsolutePath = $user->face_reference_path
                    ? Storage::disk('public')->path($user->face_reference_path)
                    : null;

                if ($referenceAbsolutePath && file_exists($referenceAbsolutePath)) {
                    $faceResult = $faceService->verify($user, $referenceAbsolutePath, $selfieAbsolutePath);
                    $providerStatus = $faceResult['status'] ?? 'pending';
                    $providerNotes = $faceResult['message'] ?? null;
                    $providerMatchScore = $faceResult['match_score'] ?? null;
                    $faceProvider = $faceResult['provider'] ?? 'mock';
                } else {
                    $faceProvider = 'local';
                    $providerNotes = 'No existe imagen de referencia en servidor.';
                }

                if ($providerStatus === 'verified') {
                    $faceMatchScore = $providerMatchScore;
                    $faceVerified = $challengeGatePass;
                    $faceStatus = $faceVerified ? 'verified' : 'rejected';
                    $faceNotes = $providerNotes ?: 'Coincidencia facial verificada por proveedor.';
                } elseif ($providerStatus === 'rejected') {
                    $faceMatchScore = $providerMatchScore;
                    $faceVerified = false;
                    $faceStatus = 'rejected';
                    $faceNotes = $providerNotes ?: 'Proveedor biométrico rechazó la identidad.';
                } else {
                    $storedDescriptor = is_array($user->face_descriptor) ? $user->face_descriptor : null;
                    $similarity = ($trustClientDescriptor && $incomingDescriptor)
                        ? $this->cosineSimilarity($storedDescriptor, $incomingDescriptor)
                        : null;
                    $faceMatchScore = $similarity ?? $providerMatchScore;

                    if ($similarity !== null) {
                        $matchPass = $similarity >= $matchThreshold;
                        $faceVerified = $matchPass && $challengeGatePass;
                        $faceStatus = $faceVerified ? 'verified' : 'rejected';
                        $faceProvider = 'local';
                        $faceNotes = $faceVerified
                            ? 'Coincidencia facial local aprobada (fallback).'
                            : 'Coincidencia/liveness/reto insuficiente en validación local (fallback).';
                    } else {
                        $faceVerified = false;
                        $faceStatus = 'pending';
                        $faceNotes = trim(($providerNotes ? $providerNotes . ' | ' : '') . 'Sin descriptor local confiable para fallback.');
                    }
                }

                if ($faceVerified) {
                    $user->forceFill([
                        'face_last_verified_at' => now(),
                        'face_provider' => $faceProvider ?: $user->face_provider,
                    ])->save();
                }
            }
        }

        if (!$challengeGatePass) {
            $faceVerified = false;
            $faceStatus = 'rejected';
            $faceNotes = trim(($faceNotes ? $faceNotes . ' | ' : '') . 'Reto no completado y score de liveness insuficiente.');
        }

        if (!$faceVerified) {
            $esIncidencia = true;
            $motivoIncidencia = trim(($motivoIncidencia ? $motivoIncidencia . ' | ' : '') . ($faceNotes ?: 'Verificación facial no confirmada.'));
            $faceNotes = trim(($faceNotes ?: 'No verificado') . " (modo {$challengeMode}, umbral match {$matchThreshold}, liveness {$livenessThreshold}, fallback {$fallbackLivenessThreshold})");
        } else {
            $faceNotes = trim(($faceNotes ?: 'Verificado') . " (modo {$challengeMode}, umbral match {$matchThreshold}, liveness {$livenessThreshold})");
        }

        if ($tokenMode && !$faceVerified) {
            return back()->withErrors([
                'selfie' => 'No se pudo confirmar la identidad para este enlace. Usa tu enlace personal o inicia sesión.',
            ]);
        }

        if ($strictFaceMatch && (!$faceVerified || !$challengeCompleted)) {
            return back()->withErrors([
                'selfie' => 'No se pudo validar tu identidad facial en modo estricto. Completa el reto de movimiento y mejora luz/cámara frontal.',
            ]);
        }

        // Dirección
        $direccion = null;
        if ((($validated['latitud'] ?? null) !== null) && (($validated['longitud'] ?? null) !== null)) {
            $direccion = GeocodingService::reverseGeocode($validated['latitud'], $validated['longitud']);
        }

        AsistenciaRegistro::create([
            'empresa_id' => $user->empresa_id,
            'user_id' => $user->id,
            'almacen_id' => $almacen?->id,
            'tipo' => $validated['tipo'],
            'registrado_at' => now(),
            'origen' => $tokenMode ? 'token_link' : 'web_panel',
            'latitud' => $validated['latitud'],
            'longitud' => $validated['longitud'],
            'precision_metros' => $validated['precision_metros'],
            'direccion' => $direccion,
            'selfie_path' => $selfiePath,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'notas' => $validated['notas'],
            'es_incidencia' => $esIncidencia,
            'motivo_incidencia' => $motivoIncidencia,
            'consentimiento_biometrico' => $request->boolean('consentimiento'),
            'face_verified' => $faceVerified,
            'face_match_score' => $faceMatchScore,
            'face_liveness_score' => $faceLivenessScore,
            'face_verification_status' => $faceStatus,
            'face_provider' => $faceProvider,
            'face_verification_notes' => $faceNotes,
            'face_detected_count' => $validated['face_detected_count'] ?? null,
            'face_capture_quality_passed' => (bool) ($validated['face_capture_quality_passed'] ?? false),
            'face_quality_brightness' => $validated['face_quality_brightness'] ?? null,
            'face_quality_sharpness' => $validated['face_quality_sharpness'] ?? null,
            'face_quality_area_ratio' => $validated['face_quality_area_ratio'] ?? null,
            'face_quality_center_offset' => $validated['face_quality_center_offset'] ?? null,
            'face_quality_message' => $validated['face_quality_message'] ?? null,
        ]);

        $msg = $esIncidencia ? 'Registro guardado con incidencia de ubicación.' : 'Asistencia registrada correctamente.';
        return back()->with('success', $msg);
    }

    /**
     * Cálculo de distancia Haversine
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // metros
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    private function parseFaceDescriptor(?string $descriptorJson): ?array
    {
        if (!$descriptorJson) {
            return null;
        }

        $decoded = json_decode($descriptorJson, true);
        if (!is_array($decoded) || count($decoded) !== 128) {
            return null;
        }

        $vector = [];
        foreach ($decoded as $value) {
            if (!is_numeric($value)) {
                return null;
            }
            $numeric = (float) $value;
            if ($numeric < -1.5 || $numeric > 1.5) {
                return null;
            }
            $vector[] = $numeric;
        }

        return $vector;
    }

    private function cosineSimilarity(?array $a, ?array $b): ?float
    {
        if (!$a || !$b || count($a) !== count($b)) {
            return null;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        $count = count($a);
        for ($i = 0; $i < $count; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA <= 0 || $normB <= 0) {
            return null;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
