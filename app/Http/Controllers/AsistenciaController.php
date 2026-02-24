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

        $query = AsistenciaRegistro::with(['user:id,name', 'almacen:id,nombre'])
            ->whereBetween('registrado_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay()
            ]);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $registros = $query->orderByDesc('registrado_at')->paginate(50)->withQueryString();

        return Inertia::render('Asistencia/Logs', [
            'registros' => $registros,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'user_id' => $request->user_id,
                'tipo' => $request->tipo,
            ],
            'users' => User::select('id', 'name')->orderBy('name')->get(),
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
        $user = User::where('checkin_token', $token)->firstOrFail();
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

        return Inertia::render('Asistencia/Checador', [
            'employee' => [
                'id' => $user->id,
                'name' => $user->name,
                'puesto' => $user->puesto,
                'almacen' => $almacen?->nombre,
                'almacen_coords' => $almacen && $almacen->latitud && $almacen->longitud ? [
                    'lat' => (float) $almacen->latitud,
                    'lng' => (float) $almacen->longitud,
                    'radius' => (int) $almacen->geocerca_radio,
                ] : null,
            ],
            'companyName' => $config->nombre_empresa,
            'serverNowIso' => now()->toIso8601String(),
            'suggestedType' => $suggestedType,
            'token' => $token,
            'biometric' => [
                'is_enrolled' => (bool) $user->face_enrolled_at,
                'strict_match' => (bool) config('services.biometrics.strict_match', false),
                'has_face_descriptor' => !empty($user->face_descriptor),
            ],
            'checkTypes' => [
                ['value' => 'entry', 'label' => 'Entrada'],
                ['value' => 'break_start', 'label' => 'Inicio Descanso'],
                ['value' => 'break_end', 'label' => 'Fin Descanso'],
                ['value' => 'exit', 'label' => 'Salida'],
            ],
        ]);
    }

    /**
     * Procesa el registro de asistencia
     */
    public function store(Request $request): RedirectResponse
    {
        $user = null;
        if ($request->has('token')) {
            $user = User::where('checkin_token', $request->token)->firstOrFail();
        } else {
            $user = Auth::user();
        }

        if (!$user) {
            return back()->withErrors(['auth' => 'Sesión no válida.']);
        }

        $validated = $request->validate([
            'tipo' => 'required|in:entry,exit,break_start,break_end',
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180',
            'precision_metros' => 'nullable|integer',
            'selfie' => 'required|image|max:5120',
            'notas' => 'nullable|string|max:500',
            'consentimiento' => 'required|accepted',
            'face_challenge_completed' => 'required|accepted',
            'face_liveness_score' => 'nullable|numeric|between:0,1',
            'face_descriptor' => 'required|string|max:10000',
        ]);

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
        $softGeofenceMargin = (float) config('services.biometrics.geofence_soft_margin_meters', 120);

        if ($almacen && $almacen->latitud && $almacen->longitud && $validated['latitud'] && $validated['longitud']) {
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
        $strictFaceMatch = (bool) config('services.biometrics.strict_match', false);
        $faceLivenessScore = isset($validated['face_liveness_score']) ? (float) $validated['face_liveness_score'] : null;
        $incomingDescriptor = $this->parseFaceDescriptor($validated['face_descriptor']);
        $baseMatchThreshold = (float) config('services.biometrics.local_match_threshold', 0.72);
        $baseLivenessThreshold = (float) config('services.biometrics.local_liveness_threshold', 0.45);
        $nearbyMatchRelax = (float) config('services.biometrics.nearby_match_relax', 0.06);
        $nearbyLivenessRelax = (float) config('services.biometrics.nearby_liveness_relax', 0.10);
        $farMatchPenalty = (float) config('services.biometrics.far_match_penalty', 0.06);
        $farLivenessPenalty = (float) config('services.biometrics.far_liveness_penalty', 0.10);

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

        $livenessPass = $faceLivenessScore !== null && $faceLivenessScore >= $livenessThreshold;

        /** @var FaceVerificationService $faceService */
        $faceService = app(FaceVerificationService::class);

        if ($selfiePath) {
            $selfieAbsolutePath = Storage::disk('public')->path($selfiePath);

            if (!$incomingDescriptor) {
                $faceStatus = 'pending';
                $faceProvider = 'local';
                $faceNotes = 'No se recibió descriptor facial válido.';
            } else {
                if (!$user->face_enrolled_at || empty($user->face_descriptor)) {
                    $faceStatus = 'enrolled';
                    $faceProvider = 'local';
                    $faceNotes = 'Rostro enrolado localmente.';
                    $faceVerified = $livenessPass;
                    $faceMatchScore = 1.0;

                    $user->forceFill([
                        'face_reference_path' => $selfiePath,
                        'face_descriptor' => $incomingDescriptor,
                        'face_enrolled_at' => now(),
                        'face_last_verified_at' => now(),
                        'face_provider' => 'local',
                    ])->save();
                } else {
                    $storedDescriptor = is_array($user->face_descriptor) ? $user->face_descriptor : null;
                    $similarity = $this->cosineSimilarity($storedDescriptor, $incomingDescriptor);
                    $faceMatchScore = $similarity;
                    $faceProvider = 'local';

                    if ($similarity !== null) {
                        $matchPass = $similarity >= $matchThreshold;
                        $faceVerified = $matchPass && $livenessPass;
                        $faceStatus = $faceVerified ? 'verified' : 'rejected';
                        $faceNotes = $faceVerified
                            ? 'Coincidencia facial local aprobada.'
                            : 'Coincidencia/liveness insuficiente en validación local.';
                    } else {
                        $referenceAbsolutePath = Storage::disk('public')->path($user->face_reference_path);
                        $faceResult = $faceService->verify($user, $referenceAbsolutePath, $selfieAbsolutePath);
                        $faceStatus = $faceResult['status'] ?? 'pending';
                        $faceProvider = $faceResult['provider'] ?? 'mock';
                        $faceNotes = $faceResult['message'] ?? 'No se pudo evaluar descriptor local.';
                        $faceMatchScore = $faceResult['match_score'] ?? null;
                        $faceVerified = $faceStatus === 'verified';
                    }

                    if ($faceVerified) {
                        $user->forceFill([
                            'face_last_verified_at' => now(),
                            'face_provider' => $faceProvider ?: $user->face_provider,
                        ])->save();
                    }
                }
            }
        }

        if (!$faceVerified) {
            $esIncidencia = true;
            $motivoIncidencia = trim(($motivoIncidencia ? $motivoIncidencia . ' | ' : '') . ($faceNotes ?: 'Verificación facial no confirmada.'));
            $faceNotes = trim(($faceNotes ?: 'No verificado') . " (umbral match {$matchThreshold}, liveness {$livenessThreshold})");
        }

        if ($strictFaceMatch && !$faceVerified) {
            return back()->withErrors([
                'selfie' => 'No se pudo validar tu identidad facial. Intenta de nuevo con mejor luz y cámara frontal.',
            ]);
        }

        // Dirección
        $direccion = null;
        if ($validated['latitud'] && $validated['longitud']) {
            $direccion = GeocodingService::reverseGeocode($validated['latitud'], $validated['longitud']);
        }

        AsistenciaRegistro::create([
            'empresa_id' => $user->empresa_id,
            'user_id' => $user->id,
            'almacen_id' => $almacen?->id,
            'tipo' => $validated['tipo'],
            'registrado_at' => now(),
            'origen' => $request->has('token') ? 'token_link' : 'web_panel',
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
        if (!is_array($decoded) || count($decoded) < 64) {
            return null;
        }

        $vector = [];
        foreach ($decoded as $value) {
            if (!is_numeric($value)) {
                return null;
            }
            $vector[] = (float) $value;
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
