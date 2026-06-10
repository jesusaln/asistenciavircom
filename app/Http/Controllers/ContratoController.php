<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\User;
use App\Models\ContratoPlantilla;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class ContratoController extends Controller
{
    /**
     * Ver el expediente digital de un empleado (Fase 1)
     */
    public function expediente(User $empleado)
    {
        if (!$empleado->es_empleado) abort(404);

        return Inertia::render('Empleados/Expediente', [
            'empleado' => $empleado,
            'contratos' => $empleado->contratos()->orderBy('created_at', 'desc')->get()->map(fn($c) => [
                'id' => $c->id,
                'tipo' => $c->tipo,
                'titulo' => $c->titulo,
                'estado' => $c->estado,
                'estado_color' => $c->estado_color,
                'archivo_path' => $c->archivo_path,
                'created_at' => $c->created_at->format('d M Y'),
                'signed_at' => $c->signed_at ? $c->signed_at->format('d M Y H:i') : null,
            ]),
            'plantillas' => ContratoPlantilla::where('activo', true)->get(),
        ]);
    }

    /**
     * Lista de plantillas de contratos (Fase 2)
     */
    public function indexPlantillas()
    {
        return Inertia::render('Empleados/Plantillas', [
            'plantillas' => ContratoPlantilla::orderBy('nombre')->get(),
        ]);
    }

    /**
     * Guardar una nueva plantilla (Fase 2)
     */
    public function storePlantilla(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|string',
            'vigencia_meses' => 'nullable|integer',
            'contenido' => 'required|string',
        ]);

        ContratoPlantilla::create($validated);

        return back()->with('success', 'Plantilla guardada correctamente.');
    }

    /**
     * Crear plantillas base por defecto (Fase 4 - NOM-035)
     */
    public function crearPlantillasBase()
    {
        $existe = ContratoPlantilla::where('tipo', 'nom_035')->exists();
        if (!$existe) {
            ContratoPlantilla::create([
                'nombre' => 'Política de Prevención de Riesgos Psicosociales',
                'tipo' => 'nom_035',
                'contenido' => "POLÍTICA DE PREVENCIÓN DE RIESGOS PSICOSOCIALES\n\nEn la empresa, nos comprometemos a prevenir los factores de riesgo psicosocial y la violencia laboral, así como a promover un entorno organizacional favorable.\n\nYo, {{nombre}}, con RFC {{rfc}}, declaro haber sido informado sobre las medidas de prevención y los mecanismos para presentar quejas en relación a la NOM-035-STPS-2018.\n\nFirma del empleado: __________________",
                'activo' => true
            ]);
        }
        return back()->with('success', 'Plantillas base generadas.');
    }

    /**
     * Matriz de Cumplimiento (Fase 4)
     */
    public function matrizCumplimiento()
    {
        $empleados = User::empleadosActivos()->get();
        $plantillas = ContratoPlantilla::where('activo', true)->get();

        $data = $empleados->map(function($e) use ($plantillas) {
            $status = [];
            foreach ($plantillas as $p) {
                $status[$p->id] = $e->contratos()
                    ->where('tipo', $p->tipo)
                    ->where('estado', 'firmado')
                    ->exists();
            }

            // MEJORA: Integrar resultados de NOM-035 (Si existen)
            $nom035 = \DB::table('nom035_respondents')
                ->where('empleado_id', $e->id)
                ->where('status', 'completed')
                ->orderBy('completed_at', 'desc')
                ->first();

            return [
                'id' => $e->id,
                'name' => $e->name,
                'puesto' => $e->puesto,
                'nss' => $e->nss,
                'status' => $status,
                'nom035_riesgo' => $nom035->risk_level ?? 'Pendiente',
                'nom035_url' => $nom035 ? route('nom035.resultados', $nom035->id) : null
            ];
        });

        return Inertia::render('Empleados/Cumplimiento', [
            'matriz' => $data,
            'plantillas' => $plantillas
        ]);
    }

    /**
     * Generar documentos de forma masiva (Fase 4)
     */
    public function generarMasivo(Request $request)
    {
        $request->validate([
            'empleado_ids' => 'required|array',
            'plantilla_id' => 'required|exists:contrato_plantillas,id'
        ]);

        $plantilla = ContratoPlantilla::find($request->plantilla_id);
        $empleados = User::whereIn('id', $request->empleado_ids)->get();

        foreach ($empleados as $empleado) {
            // Lógica reutilizada de generación
            $contenido = $plantilla->contenido;
            $variables = [
                '{{nombre}}' => $empleado->name,
                '{{rfc}}' => $empleado->rfc ?? '—',
                '{{nss}}' => $empleado->nss ?? '—',
                '{{puesto}}' => $empleado->puesto ?? '—',
                '{{sueldo}}' => '$' . number_format($empleado->salario_base, 2),
                '{{fecha_contratacion}}' => $empleado->fecha_contratacion ? $empleado->fecha_contratacion->format('d/m/Y') : '—',
            ];
            foreach ($variables as $key => $val) {
                $contenido = str_replace($key, $val, $contenido);
            }

            $expiresAt = null;
            if ($plantilla->vigencia_meses) {
                $expiresAt = now()->addMonths($plantilla->vigencia_meses);
            }

            Contrato::create([
                'user_id' => $empleado->id,
                'tipo' => $plantilla->tipo,
                'titulo' => $plantilla->nombre . " - " . now()->format('d/m/Y'),
                'contenido' => $contenido,
                'estado' => 'pendiente_firma',
                'expires_at' => $expiresAt,
            ]);
        }

        return back()->with('success', count($empleados) . " documentos generados exitosamente.");
    }

    /**
     * Generar un contrato personalizado desde una plantilla (Fase 2)
     */
    public function generarDesdePlantilla(Request $request, User $empleado)
    {
        $request->validate([
            'plantilla_id' => 'required|exists:contrato_plantillas,id'
        ]);

        $plantilla = ContratoPlantilla::find($request->plantilla_id);
        
        // MEJORA: Evitar duplicados innecesarios
        $existe = $empleado->contratos()
            ->where('tipo', $plantilla->tipo)
            ->where('estado', '!=', 'cancelado')
            ->exists();
            
        if ($existe) {
            return back()->withErrors(['error' => 'Ya existe un documento de este tipo para este empleado.']);
        }

        $contenido = $plantilla->contenido;
        $variables = [
            '{{nombre}}' => $empleado->name,
            '{{rfc}}' => $empleado->rfc ?? '—',
            '{{nss}}' => $empleado->nss ?? '—',
            '{{puesto}}' => $empleado->puesto ?? '—',
            '{{sueldo}}' => '$' . number_format($empleado->salario_base, 2),
            '{{fecha_contratacion}}' => $empleado->fecha_contratacion ? $empleado->fecha_contratacion->format('d/m/Y') : '—',
        ];

        foreach ($variables as $key => $val) {
            $contenido = str_replace($key, $val, $contenido);
        }

        // MEJORA: Calcular fecha de vencimiento automática si la plantilla tiene vigencia
        $expiresAt = null;
        if ($plantilla->vigencia_meses) {
            $expiresAt = now()->addMonths($plantilla->vigencia_meses);
        }

        $contrato = Contrato::create([
            'user_id' => $empleado->id,
            'tipo' => $plantilla->tipo,
            'titulo' => $plantilla->nombre . " - " . now()->format('d/m/Y'),
            'contenido' => $contenido,
            'estado' => 'pendiente_firma',
            'expires_at' => $expiresAt,
        ]);

        return back()->with('success', 'Documento generado desde plantilla exitosamente.');
    }

    /**
     * Ver el portal de firma para el empleado (Fase 2)
     */
    public function portalFirma(Contrato $contrato)
    {
        // Validar que el contrato sea del usuario logueado
        if ($contrato->user_id !== auth()->id()) {
            abort(403);
        }

        return Inertia::render('Empleados/PortalFirma', [
            'contrato' => [
                'id' => $contrato->id,
                'titulo' => $contrato->titulo,
                'contenido' => $contrato->contenido,
                'estado' => $contrato->estado,
                'hash_documento' => $contrato->hash_documento,
                'signed_at' => $contrato->signed_at ? $contrato->signed_at->format('d/m/Y H:i') : null,
            ]
        ]);
    }

    /**
     * Acción de firmar/aceptar contrato (Fase 2)
     */
    public function aceptarContrato(Request $request, Contrato $contrato)
    {
        if ($contrato->user_id !== auth()->id()) abort(403);
        if ($contrato->estado === 'firmado') return back()->with('error', 'El documento ya ha sido firmado.');

        // Generar Huella Digital (SHA-256) para cumplimiento NOM-151
        // Mezclamos el contenido con datos del usuario para una huella única
        $dataToHash = $contrato->contenido . $contrato->user_id . now()->toIso8601String();
        $hash = hash('sha256', $dataToHash);

        $contrato->update([
            'estado' => 'firmado',
            'signed_at' => now(),
            'hash_documento' => $hash,
            'metadata' => array_merge($contrato->metadata ?? [], [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metodo' => 'firma_digital_nom151_v1',
                'algoritmo' => 'SHA-256',
                'ts' => now()->timestamp,
            ])
        ]);

        return back()->with('success', 'Documento firmado y sellado con éxito bajo estándares NOM-151.');
    }

    /**
     * Guardar un nuevo contrato o adenda (Fase 1)
     */
    public function store(Request $request, User $empleado)
    {
        $validated = $request->validate([
            'tipo' => 'required|string',
            'titulo' => 'required|string|max:255',
            'contenido' => 'nullable|string',
            'archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        try {
            $contrato = new Contrato([
                'user_id' => $empleado->id,
                'tipo' => $validated['tipo'],
                'titulo' => $validated['titulo'],
                'contenido' => $validated['contenido'] ?? null,
                'estado' => 'borrador',
            ]);

            if ($request->hasFile('archivo')) {
                // CAMBIO: Almacenamiento PRIVADO para seguridad legal
                $path = $request->file('archivo')->store('contratos', 'local');
                $contrato->archivo_path = $path;
            }

            $contrato->save();

            return back()->with('success', 'Documento agregado al expediente exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al guardar contrato: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al guardar el documento.']);
        }
    }

    /**
     * Descarga segura de archivos de contrato (Fase 4 - Seguridad)
     */
    public function verArchivo(Contrato $contrato)
    {
        // Seguridad: Solo el dueño o un admin/super-admin puede ver el archivo
        if (auth()->id() !== $contrato->user_id && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            abort(403, 'No tienes permiso para ver este documento.');
        }

        if (!$contrato->archivo_path || !\Storage::disk('local')->exists($contrato->archivo_path)) {
            abort(404, 'El archivo no existe.');
        }

        return \Storage::disk('local')->response($contrato->archivo_path);
    }
}
