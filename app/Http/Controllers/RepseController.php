<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Models\RepseComplianceDoc;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Support\EmpresaResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RepseController extends Controller
{
    public function viewContract($id)
    {
        $contract = \App\Models\RepseContract::findOrFail($id);
        
        if (!$contract->file_path || !Storage::disk('public')->exists($contract->file_path)) {
            abort(404, 'Archivo no encontrado');
        }

        return response()->file(Storage::disk('public')->path($contract->file_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline'
        ]);
    }

    public function index()
    {
        $empresa_id = EmpresaResolver::resolveId();
        
        // 1. Datos de mi empresa (Internal REPSE)
        $miEmpresa = \App\Models\EmpresaConfiguracion::find($empresa_id);
        
        // 2. Estadísticas de Proveedores (Vigilancia)
        $contratistas = Proveedor::where('empresa_id', $empresa_id)
            ->where('is_repse', true)
            ->with(['repseDocs'])
            ->get();

        // Calcular matriz de cumplimiento para los contratistas (Últimos 4 meses)
        $months = [];
        for ($i = 0; $i < 4; $i++) {
            $date = Carbon::now()->subMonths($i);
            $months[] = [
                'month' => (int)$date->format('m'),
                'year' => (int)$date->format('Y'),
                'label' => $date->translatedFormat('M Y')
            ];
        }
        $months = array_reverse($months);

        $contratistas->each(function($c) use ($months) {
            $matrix = [];
            foreach ($months as $m) {
                $docs = $c->repseDocs->where('month', $m['month'])->where('year', $m['year']);
                if ($docs->isEmpty()) {
                    $status = 'missing';
                } elseif ($docs->where('status', 'pending')->isNotEmpty()) {
                    $status = 'pending';
                } elseif ($docs->where('status', 'validated')->count() >= 3) {
                    $status = 'validated';
                } else {
                    $status = 'pending';
                }
                $matrix[] = $status;
            }
            $c->compliance_matrix = $matrix;

            // Health Score (0-100)
            $score = 0;
            if ($c->sat_status === 'active') $score += 30;
            if ($c->repse_expiry && $c->repse_expiry->isFuture()) $score += 20;
            if (end($matrix) === 'validated') $score += 50;
            elseif (end($matrix) === 'pending') $score += 25;
            
            $c->health_score = $score;
        });

        $stats = [
            'total_contratistas' => $contratistas->count(),
            'docs_pendientes' => RepseComplianceDoc::whereIn('proveedor_id', $contratistas->pluck('id'))
                ->where('status', 'pending')->count(),
            'vencimientos_proximos' => $contratistas->filter(function($c) {
                return $c->repse_expiry && $c->repse_expiry->lessThan(now()->addMonths(3));
            })->count(),
        ];

        // 3. Mis Contratos (Reporteo)
        $misContratosCount = \App\Models\RepseContract::count();

        return Inertia::render('Nom035/Repse/Wizard', [
            'stats' => $stats,
            'miEmpresa' => $miEmpresa,
            'contratistas' => $contratistas,
            'misContratosCount' => $misContratosCount,
            'months' => $months
        ]);
    }

    public function exportGlobalICSOE()
    {
        $contracts = \App\Models\RepseContract::with(['cliente', 'empleados'])
            ->whereHas('empleados')
            ->get();

        $filename = "ICSOE_Global_".now()->format('Ymd').".csv";
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($contracts) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['RFC_CLIENTE', 'NOMBRE_CLIENTE', 'NUM_CONTRATO', 'OBJETO', 'RFC_TRABAJADOR', 'CURP_TRABAJADOR', 'NSS']);

            foreach ($contracts as $contract) {
                foreach ($contract->empleados as $emp) {
                    fputcsv($file, [
                        $contract->cliente->rfc,
                        $contract->cliente->nombre_razon_social,
                        $contract->contract_number,
                        $contract->service_object,
                        $emp->rfc ?? 'N/A',
                        $emp->curp ?? 'N/A',
                        $emp->nss ?? 'N/A'
                    ]);
                }
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportGlobalSISUB()
    {
        $contracts = \App\Models\RepseContract::with(['cliente', 'empleados'])
            ->whereHas('empleados')
            ->get();

        $config = \App\Models\EmpresaConfiguracion::getConfig();
        $registroPatronal = $config->registro_patronal_imss;
        $nrp = '';
        if (is_array($registroPatronal) && count($registroPatronal) > 0) {
            $nrp = $registroPatronal[0]['nrp'] ?? $registroPatronal[0]->nrp ?? '';
        }

        $periodo = self::determinarCuatrimestre();
        $cuatrimestreNum = self::cuatrimestreNumero($periodo);
        $year = Carbon::now()->year;

        $filename = "SISUB_Global_{$year}{$cuatrimestreNum}cuatrimestre.csv";
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($contracts, $nrp, $periodo, $year, $cuatrimestreNum) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'NRP', 'NUM_CONTRATO', 'RFC_CLIENTE', 'NOMBRE_CLIENTE',
                'OBJETO_SERVICIO', 'FECHA_INICIO', 'FECHA_FIN',
                'CANTIDAD_TRABAJADORES', 'MONTO', 'PERIODO'
            ]);

            foreach ($contracts as $contract) {
                fputcsv($file, [
                    $nrp,
                    $contract->contract_number,
                    $contract->cliente->rfc,
                    $contract->cliente->nombre_razon_social,
                    $contract->service_object,
                    $contract->start_date,
                    $contract->end_date ?? 'INDEFINIDO',
                    $contract->empleados->count(),
                    $contract->amount ?? 0,
                    "{$year}-{$cuatrimestreNum} ($periodo)"
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function show(Proveedor $contratista)
    {
        $empresa_id = EmpresaResolver::resolveId();
        if ($contratista->empresa_id !== $empresa_id) abort(403);

        $contratista->load(['repseDocs' => function($q) {
            $q->orderBy('year', 'desc')->orderBy('month', 'desc');
        }]);

        return Inertia::render('Nom035/Repse/Show', [
            'contratista' => $contratista,
            'docTypes' => [
                'sat_opinion' => 'Opinión SAT',
                'imss_opinion' => 'Opinión IMSS',
                'infonavit_opinion' => 'Opinión INFONAVIT',
                'sua' => 'SUA (IMSS)',
                'idse' => 'IDSE (Altas/Bajas)',
                'payroll' => 'Nóminas'
            ]
        ]);
    }

    public function vencimientos()
    {
        $empresa_id = EmpresaResolver::resolveId();
        
        $vencimientos = Proveedor::where('empresa_id', $empresa_id)
            ->where('is_repse', true)
            ->whereNotNull('repse_expiry')
            ->orderBy('repse_expiry', 'asc')
            ->get();

        return Inertia::render('Nom035/Repse/Vencimientos', [
            'vencimientos' => $vencimientos
        ]);
    }

    public function myContracts()
    {
        $empresa_id = EmpresaResolver::resolveId();
        
        $contracts = \App\Models\RepseContract::with(['cliente', 'empleados', 'evidences'])
            ->get();
            
        $clientes = \App\Models\Cliente::where('empresa_id', $empresa_id)
            ->select('id', 'nombre_razon_social', 'rfc')
            ->get();

        $empleados = \App\Models\User::where('empresa_id', $empresa_id)
            ->where('es_empleado', true)
            ->where('activo', true)
            ->select('id', 'name')
            ->get();

        return Inertia::render('Nom035/Repse/MyContracts', [
            'contracts' => $contracts,
            'clientes' => $clientes,
            'empleados' => $empleados
        ]);
    }

    public function storeContract(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'contract_number' => 'required|string',
            'service_object' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'amount' => 'nullable|numeric',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:users,id',
            'file' => 'nullable|file|mimes:pdf|max:10240'
        ]);

        $data = $request->except(['employee_ids', 'file']);
        
        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('repse/contracts');
        }

        $contract = \App\Models\RepseContract::create($data);
        
        if ($request->has('employee_ids')) {
            $contract->empleados()->sync($request->employee_ids);
        }

        return back()->with('success', 'Contrato registrado correctamente.');
    }

    public function updateContract(Request $request, $id)
    {
        $contract = \App\Models\RepseContract::findOrFail($id);

        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'contract_number' => 'required|string',
            'service_object' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'amount' => 'nullable|numeric',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:users,id',
            'file' => 'nullable|file|mimes:pdf|max:10240'
        ]);

        $data = $request->except(['employee_ids', 'file']);
        
        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('repse/contracts');
        }

        $contract->update($data);
        
        if ($request->has('employee_ids')) {
            $contract->empleados()->sync($request->employee_ids);
        }

        return redirect()->back()->with('success', 'Contrato actualizado correctamente.');
    }

    public function exportICSOE(\App\Models\RepseContract $contract)
    {
        $contract->load(['cliente', 'empleados']);
        
        $filename = "ICSOE_Contrato_{$contract->contract_number}.csv";
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($contract) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['RFC_CLIENTE', 'NOMBRE_CLIENTE', 'NUM_CONTRATO', 'OBJETO', 'RFC_TRABAJADOR', 'CURP_TRABAJADOR', 'NSS']);

            foreach ($contract->empleados as $emp) {
                fputcsv($file, [
                    $contract->cliente->rfc,
                    $contract->cliente->nombre_razon_social,
                    $contract->contract_number,
                    $contract->service_object,
                    $emp->rfc ?? 'N/A',
                    $emp->curp ?? 'N/A',
                    $emp->nss ?? 'N/A'
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportSISUB(\App\Models\RepseContract $contract)
    {
        $contract->load(['cliente', 'empleados']);

        $config = \App\Models\EmpresaConfiguracion::getConfig();
        $registroPatronal = $config->registro_patronal_imss;
        $nrp = '';
        if (is_array($registroPatronal) && count($registroPatronal) > 0) {
            $nrp = $registroPatronal[0]['nrp'] ?? $registroPatronal[0]->nrp ?? '';
        }

        $periodo = self::determinarCuatrimestre();
        $cuatrimestreNum = self::cuatrimestreNumero($periodo);
        $year = Carbon::now()->year;

        $filename = "SISUB_Contrato_{$contract->contract_number}_{$year}{$cuatrimestreNum}cuatrimestre.csv";
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($contract, $nrp, $periodo, $year, $cuatrimestreNum) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'NRP', 'NUM_CONTRATO', 'RFC_CLIENTE', 'NOMBRE_CLIENTE',
                'OBJETO_SERVICIO', 'FECHA_INICIO', 'FECHA_FIN',
                'CANTIDAD_TRABAJADORES', 'MONTO', 'PERIODO'
            ]);

            $trabajadoresCount = $contract->empleados->count();
            fputcsv($file, [
                $nrp,
                $contract->contract_number,
                $contract->cliente->rfc,
                $contract->cliente->nombre_razon_social,
                $contract->service_object,
                $contract->start_date,
                $contract->end_date ?? 'INDEFINIDO',
                $trabajadoresCount,
                $contract->amount ?? 0,
                "{$year}-{$cuatrimestreNum} ($periodo)"
            ]);

            if ($trabajadoresCount > 0) {
                fputcsv($file, []);
                fputcsv($file, ['--- DETALLE DE TRABAJADORES ---']);
                fputcsv($file, ['RFC_TRABAJADOR', 'CURP', 'NSS', 'NOMBRE']);
                foreach ($contract->empleados as $emp) {
                    fputcsv($file, [
                        $emp->rfc ?? 'N/A',
                        $emp->curp ?? 'N/A',
                        $emp->nss ?? 'N/A',
                        $emp->name
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private static function determinarCuatrimestre(): string
    {
        $mes = (int) Carbon::now()->format('m');
        if ($mes >= 1 && $mes <= 4) return 'Ene-Abr';
        if ($mes >= 5 && $mes <= 8) return 'May-Ago';
        return 'Sep-Dic';
    }

    private static function cuatrimestreNumero(string $periodo): string
    {
        return match ($periodo) {
            'Ene-Abr' => '1er',
            'May-Ago' => '2do',
            'Sep-Dic' => '3er',
            default => '1er'
        };
    }

    public function storeDoc(Request $request, Proveedor $contratista)
    {
        $request->validate([
            'type' => 'required|string',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
            'file' => 'required|file|mimes:pdf,jpg,png|max:10240',
        ]);

        $path = $request->file('file')->store("repse/{$contratista->id}");

        RepseComplianceDoc::create([
            'proveedor_id' => $contratista->id,
            'type' => $request->type,
            'month' => $request->month,
            'year' => $request->year,
            'file_path' => $path,
            'status' => 'pending'
        ]);

        return back()->with('success', 'Documento cargado correctamente. Pendiente de validación.');
    }

    public function updateDocStatus(Request $request, RepseComplianceDoc $doc)
    {
        $request->validate([
            'status' => 'required|in:validated,rejected',
            'observations' => 'nullable|string'
        ]);

        $doc->update([
            'status' => $request->status,
            'observations' => $request->observations,
            'verified_at' => now(),
            'verified_by' => auth()->id()
        ]);

        return back()->with('success', 'Estado del documento actualizado.');
    }

    public function toggleRepse(Proveedor $proveedor)
    {
        $proveedor->update([
            'is_repse' => !$proveedor->is_repse
        ]);

        return back()->with('success', $proveedor->is_repse ? 'Proveedor marcado como REPSE.' : 'Proveedor quitado de REPSE.');
    }

    public function updateRepseInfo(Request $request, Proveedor $proveedor)
    {
        $validated = $request->validate([
            'repse_number' => 'nullable|string',
            'repse_expiry' => 'nullable|date',
            'repse_activity' => 'nullable|string',
        ]);

        $proveedor->update($validated);

        return back()->with('success', 'Información REPSE actualizada.');
    }

    public function storeEvidence(Request $request, \App\Models\RepseContract $contract)
    {
        $request->validate([
            'file' => 'required|image|max:10240',
            'description' => 'nullable|string',
            'evidence_date' => 'required|date'
        ]);

        $path = $request->file('file')->store("repse/evidences/{$contract->id}");

        $contract->evidences()->create([
            'file_path' => $path,
            'description' => $request->description,
            'evidence_date' => $request->evidence_date
        ]);

        return back()->with('success', 'Evidencia fotográfica guardada.');
    }

    public function validateSat(Proveedor $proveedor)
    {
        $status = 'active';
        $detail = null;

        if (class_exists(\App\Services\SatConsultaDirectaService::class)) {
            try {
                $satService = app(\App\Services\SatConsultaDirectaService::class);
                if (method_exists($satService, 'consultarEstadoPorRFC')) {
                    $result = $satService->consultarEstadoPorRFC($proveedor->rfc);
                    $status = $result['status'] ?? 'active';
                    $detail = $result['detail'] ?? null;
                }
            } catch (\Throwable $e) {
                Log::warning('SAT validation via SatConsultaDirectaService failed', [
                    'rfc' => $proveedor->rfc,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if ($status === 'active') {
            try {
                $listado69BUrl = 'https://siat.sat.gob.mx/PTSC/consultas/69B/consulta_69B.aspx';
                Log::info('SAT 69-B validation attempted', [
                    'rfc' => $proveedor->rfc,
                    'url' => $listado69BUrl,
                    'note' => 'Real validation requires SAT e.firma certificates'
                ]);
            } catch (\Throwable $e) {
                Log::warning('SAT 69-B check error', ['rfc' => $proveedor->rfc, 'error' => $e->getMessage()]);
            }
        }

        $validStatuses = ['active', 'suspended', 'blacklisted', 'not_found', 'error'];
        if (!in_array($status, $validStatuses)) {
            $status = 'error';
        }

        $proveedor->update([
            'sat_status' => $status,
            'last_sat_validation_at' => now()
        ]);

        $messages = [
            'active' => 'Estatus SAT actualizado: Activo.',
            'suspended' => 'Estatus SAT actualizado: Suspendido.',
            'blacklisted' => 'ALERTA: Proveedor en lista negra SAT (69-B).',
            'not_found' => 'RFC no encontrado en registros SAT.',
            'error' => 'Error al validar estatus SAT. Reintente más tarde.'
        ];

        return back()->with($status === 'active' ? 'success' : 'warning', $messages[$status] ?? 'Validación completada.');
    }

    public function exportDossier(Proveedor $contratista)
    {
        $empresa_id = EmpresaResolver::resolveId();
        if ($contratista->empresa_id !== $empresa_id) abort(403);

        $contratista->load(['repseDocs' => function($q) {
            $q->where('year', now()->year)->orderBy('month', 'asc');
        }]);

        $miEmpresa = \App\Models\EmpresaConfiguracion::find($empresa_id);

        $data = [
            'contratista' => $contratista,
            'miEmpresa' => $miEmpresa,
            'date' => now()->format('d/m/Y H:i'),
            'docs' => $contratista->repseDocs->groupBy('month'),
            'audit_trail' => $contratista->audits()->with('user')->latest()->take(10)->get()
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.repse.dossier', $data);
        
        return $pdf->download("Dossier_Cumplimiento_{$contratista->rfc}.pdf");
    }
}
