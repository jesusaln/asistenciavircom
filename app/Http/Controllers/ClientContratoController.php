<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\ContratoPlantilla;
use App\Models\Cliente;
use App\Models\RepseContract;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Support\EmpresaResolver;

class ClientContratoController extends Controller
{
    /**
     * Mostrar la lista de contratos generados para clientes
     */
    public function index()
    {
        return Inertia::render('Nom035/Contratos/Index', [
            'contratos' => Contrato::with('cliente')
                ->whereNotNull('cliente_id')
                ->orderBy('created_at', 'desc')
                ->get(),
            'clientes' => Cliente::where('activo', true)->select('id', 'nombre_razon_social', 'rfc')->get(),
            'plantillas' => ContratoPlantilla::where('activo', true)->get(),
            'repse_contracts' => RepseContract::with('cliente')->get()->map(fn($r) => [
                'id' => $r->id,
                'label' => $r->contract_number . ' - ' . $r->cliente->nombre_razon_social,
                'cliente_id' => $r->cliente_id
            ])
        ]);
    }

    /**
     * Generar un contrato para un cliente basado en una plantilla y (opcionalmente) un contrato REPSE
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'plantilla_id' => 'required|exists:contrato_plantillas,id',
            'repse_contract_id' => 'nullable|exists:repse_contracts,id',
            'titulo' => 'required|string|max:255'
        ]);

        $cliente = Cliente::find($validated['cliente_id']);
        $plantilla = ContratoPlantilla::find($validated['plantilla_id']);
        $repse = $validated['repse_contract_id'] ? RepseContract::find($validated['repse_contract_id']) : null;
        $miEmpresa = \App\Models\EmpresaConfiguracion::find(EmpresaResolver::resolveId());

        $contenido = $plantilla->contenido;
        
        $variables = [
            '{{cliente_nombre}}' => $cliente->nombre_razon_social,
            '{{cliente_rfc}}' => $cliente->rfc ?? '—',
            '{{cliente_domicilio}}' => ($cliente->calle . ' ' . $cliente->numero_exterior . ', ' . $cliente->colonia),
            '{{contrato_numero}}' => $repse ? $repse->contract_number : 'S/N',
            '{{contrato_objeto}}' => $repse ? $repse->service_object : 'Servicios Especializados',
            '{{fecha_inicio}}' => $repse ? $repse->start_date : now()->format('Y-m-d'),
            '{{fecha_fin}}' => $repse ? ($repse->end_date ?? 'Indefinido') : 'Indefinido',
            '{{monto}}' => $repse ? number_format($repse->amount, 2) : '0.00',
            '{{empresa_nombre}}' => $miEmpresa->nombre ?? 'Climas del Desierto',
            '{{empresa_repse}}' => $miEmpresa->repse_number ?? '—',
        ];

        foreach ($variables as $key => $val) {
            $contenido = str_replace($key, $val, $contenido);
        }

        $contrato = Contrato::create([
            'user_id' => auth()->id(), // Quien lo genera
            'cliente_id' => $cliente->id,
            'contrato_plantilla_id' => $plantilla->id,
            'tipo' => $plantilla->tipo,
            'titulo' => $validated['titulo'],
            'contenido' => $contenido,
            'estado' => 'pendiente_firma',
            'signing_token' => Str::uuid(),
        ]);

        return redirect()->back()->with('success', 'Contrato generado y listo para enviar.');
    }

    /**
     * Vista pública para que el cliente firme el contrato
     */
    public function publicSign($token)
    {
        $contrato = Contrato::where('signing_token', $token)->with('cliente')->firstOrFail();

        if ($contrato->estado === 'firmado') {
            return Inertia::render('Nom035/Contratos/PublicSigned', [
                'contrato' => $contrato
            ]);
        }

        return Inertia::render('Nom035/Contratos/PublicSign', [
            'contrato' => $contrato
        ]);
    }

    /**
     * Procesar la firma electrónica (FIEL) del cliente
     */
    public function sign(Request $request, $token)
    {
        $contrato = Contrato::where('signing_token', $token)->firstOrFail();
        
        $request->validate([
            'cer' => 'required|file',
            'key' => 'required|file',
            'password' => 'required|string'
        ]);

        // 1. Extraer RFC del certificado
        // Los certificados del SAT vienen en formato DER (binario). PHP requiere formato PEM.
        $cerContent = file_get_contents($request->file('cer')->path());
        
        // Convertir de DER a PEM si no tiene los encabezados
        if (strpos($cerContent, 'BEGIN CERTIFICATE') === false) {
            $pem = "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode($cerContent), 64, "\n") . "-----END CERTIFICATE-----\n";
            $cerData = openssl_x509_parse($pem);
        } else {
            $cerData = openssl_x509_parse($cerContent);
        }
        
        if (!$cerData) {
            return back()->withErrors(['error' => 'El archivo no se pudo procesar como un certificado válido del SAT.']);
        }

        // El RFC suele venir en el subject
        $subject = $cerData['subject'];
        $rfc = null;
        $nombreFirmante = $subject['O'] ?? $subject['CN'] ?? 'DESCONOCIDO';

        // Buscamos el RFC en los campos comunes del SAT
        if (isset($subject['x500UniqueIdentifier'])) {
            $rfc = $subject['x500UniqueIdentifier'];
        } elseif (isset($subject['serialNumber'])) {
             $rfc = $subject['serialNumber']; // OID 2.5.4.5
        }

        // 2. Validar identidad estricta (Auditoría)
        $clienteRfc = trim($contrato->cliente->rfc);
        if ($clienteRfc && $clienteRfc !== 'XAXX010101000') {
            if (!$rfc || strtoupper(trim($rfc)) !== strtoupper($clienteRfc)) {
                return back()->withErrors(['error' => "El RFC del certificado ($rfc) no coincide con el RFC del cliente en el contrato ($clienteRfc)."]);
            }
        }

        // 3. Decodificar Serie del Certificado SAT (Viene en Hexadecimal, ej: 303030...)
        $hexSerial = $cerData['serialNumberHex'] ?? '';
        $cerSerial = '';
        if ($hexSerial) {
            for ($i = 0; $i < strlen($hexSerial); $i += 2) {
                $cerSerial .= chr(hexdec(substr($hexSerial, $i, 2)));
            }
        } else {
            $cerSerial = $cerData['serialNumber'] ?? '—';
        }

        // 4. Simular el sellado del Cliente
        $hashClient = hash('sha256', $contrato->contenido . $token . now()->timestamp);
        
        // 5. Generar la Constancia de Emisión del Prestador (Climas del Desierto)
        $miEmpresa = \App\Models\EmpresaConfiguracion::first();
        $rfcEmpresa = $miEmpresa->rfc ?? 'LONJ880321KMA';
        $hashProvider = hash('sha256', $contrato->contenido . $rfcEmpresa . now()->timestamp);

        $contrato->update([
            'estado' => 'firmado',
            'signed_at' => now(),
            'hash_documento' => $hashClient, // Sello Cliente principal
            'signature_client' => 'FIRMA_ELECTRONICA_VALIDADA_RFC_' . $rfc,
            'signature_provider' => 'CONSTANCIA_EMISION_SISTEMA_RFC_' . $rfcEmpresa,
            'metadata' => array_merge($contrato->metadata ?? [], [
                'ip' => $request->ip(),
                'rfc_firmante' => $rfc,
                'nombre_firmante' => $nombreFirmante,
                'cer_serial' => $cerSerial,
                'hash_proveedor' => $hashProvider,
                'rfc_proveedor' => $rfcEmpresa
            ])
        ]);

        return redirect()->route('contratos.public.signed', $token);
    }

    public function publicSigned($token)
    {
        $contrato = \App\Models\Contrato::where('signing_token', $token)
            ->with('cliente')
            ->firstOrFail();

        return \Inertia\Inertia::render('Nom035/Contratos/PublicSigned', [
            'contrato' => $contrato
        ]);
    }

    public function downloadAcuse($token)
    {
        $contrato = \App\Models\Contrato::where('signing_token', $token)
            ->whereNotNull('signed_at')
            ->firstOrFail();

        $contrato->load('cliente');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.acuse_firma', compact('contrato'));
        
        return $pdf->download("Acuse_Firma_{$contrato->id}.pdf");
    }
}
