<?php

namespace App\Http\Controllers;

use App\Models\PlanRenta;
use App\Models\Cliente;
use App\Models\Renta;
use App\Models\Venta;
use App\Models\VentaItem;
use App\Models\SatRegimenFiscal;
use App\Models\SatEstado;
use App\Models\SatUsoCfdi;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;

class ContratacionRentaController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $empresaId = EmpresaResolver::resolveId();
        $empresaModel = \App\Models\Empresa::find($empresaId);
        $configuracion = \App\Models\EmpresaConfiguracion::getConfig($empresaId);

        $empresa = $empresaModel ? array_merge($empresaModel->toArray(), [
            'color_principal' => $configuracion->color_principal,
            'color_secundario' => $configuracion->color_secundario,
            'color_terciario' => $configuracion->color_terciario,
            'logo_url' => $configuracion->logo_url,
            'nombre_comercial_config' => $configuracion->nombre_empresa,
        ]) : null;

        $plan = PlanRenta::where('slug', $slug)
            ->where('activo', true)
            ->firstOrFail();

        $clienteData = null;
        if (Auth::guard('client')->check()) {
            $cliente = Auth::guard('client')->user();
            $clienteData = $cliente->toArray();
        }

        return Inertia::render('Contratacion/Renta', [
            'empresa' => $empresa,
            'plan' => $plan,
            'clienteData' => $clienteData,
            'catalogos' => [
                'regimenes' => SatRegimenFiscal::get(),
                'usosCfdi' => SatUsoCfdi::where('activo', true)->get(),
                'estados' => SatEstado::orderBy('nombre')->get()->map(fn($e) => ['value' => $e->clave, 'label' => $e->nombre]),
                'tiposPersona' => [
                    ['value' => 'fisica', 'label' => 'Persona Física'],
                    ['value' => 'moral', 'label' => 'Persona Moral'],
                ],
                'formasPagos' => [
                    ['value' => '01', 'label' => '01 - Efectivo'],
                    ['value' => '03', 'label' => '03 - Transferencia'],
                    ['value' => '04', 'label' => '04 - Tarjeta Crédito'],
                    ['value' => '28', 'label' => '28 - Tarjeta Débito'],
                    ['value' => '99', 'label' => '99 - Por definir'],
                ]
            ],
            'pasarelas' => app(\App\Services\PaymentService::class)->getAvailableGateways(),
        ]);
    }

    public function procesar(Request $request, string $slug)
    {
        $requiereFactura = $request->boolean('requiere_factura');
        $mismaDireccionFiscal = $request->boolean('misma_direccion_fiscal', true);

        $validated = $request->validate([
            'requiere_factura' => 'boolean',
            'nombre_razon_social' => 'required|string|max:255',
            'razon_social' => $requiereFactura ? 'required|string|max:255' : 'nullable|string|max:255',
            'tipo_persona' => $requiereFactura ? 'required|in:fisica,moral' : 'nullable',
            'rfc' => $requiereFactura ? ['required', 'string', 'regex:/^([A-ZÑ&]{3,4})[0-9]{6}[A-Z0-9]{3}$/i'] : 'nullable|string',
            'regimen_fiscal' => $requiereFactura ? 'required|exists:sat_regimenes_fiscales,clave' : 'nullable',
            'uso_cfdi' => $requiereFactura ? 'required|exists:sat_usos_cfdi,clave' : 'nullable',

            // Dirección fiscal (si es diferente)
            'misma_direccion_fiscal' => 'boolean',
            'domicilio_fiscal_cp' => ($requiereFactura && !$mismaDireccionFiscal) ? 'required|digits:5' : 'nullable',
            'domicilio_fiscal_calle' => ($requiereFactura && !$mismaDireccionFiscal) ? 'required|string|max:255' : 'nullable|string|max:255',
            'domicilio_fiscal_colonia' => ($requiereFactura && !$mismaDireccionFiscal) ? 'required|string|max:255' : 'nullable|string|max:255',
            'domicilio_fiscal_municipio' => ($requiereFactura && !$mismaDireccionFiscal) ? 'required|string|max:255' : 'nullable|string|max:255',
            'domicilio_fiscal_estado' => ($requiereFactura && !$mismaDireccionFiscal) ? 'required|string' : 'nullable|string',

            'email' => 'required|email|max:255',
            'telefono' => 'required|string|max:20',
            'codigo_postal' => 'required|digits:5',
            'calle' => 'required|string|max:255',
            'numero_exterior' => 'required|string|max:20',
            'numero_interior' => 'nullable|string|max:20',
            'colonia' => 'required|string|max:255',
            'municipio' => 'required|string|max:255',
            'estado' => 'required|string|size:3',
            'pais' => 'required|string|max:10',

            // Documentos
            'ine_frontal' => 'required|string',
            'ine_trasera' => 'required|string',
            'comprobante_domicilio' => 'required|string',

            // Firma digital
            'firma' => 'required|string',
            'nombre_firmante' => 'required|string|max:255',
            'acepta_firma' => 'accepted',

            'metodo_pago' => 'required|in:paypal,mercadopago,tarjeta,credito',
            'password' => auth('client')->check() ? 'nullable|confirmed' : 'required|string|min:8|confirmed',
            'aceptar_terminos' => 'accepted',
        ]);

        $plan = PlanRenta::where('slug', $slug)->firstOrFail();
        $empresaId = EmpresaResolver::resolveId() ?? 1;

        DB::beginTransaction();
        try {
            // 1. Crear o Actualizar Cliente
            $clienteData = [
                'nombre_razon_social' => $validated['nombre_razon_social'],
                'razon_social' => $requiereFactura ? $validated['razon_social'] : $validated['nombre_razon_social'],
                'tipo_persona' => $validated['tipo_persona'] ?? ($requiereFactura ? 'fisica' : null),
                'rfc' => strtoupper($requiereFactura ? $validated['rfc'] : 'XAXX010101000'),
                'regimen_fiscal' => $requiereFactura ? $validated['regimen_fiscal'] : '616',
                'uso_cfdi' => $requiereFactura ? $validated['uso_cfdi'] : 'S01',
                'requiere_factura' => $requiereFactura,
                'misma_direccion_fiscal' => $mismaDireccionFiscal,
                'email' => $validated['email'],
                'telefono' => $validated['telefono'],
                // Dirección de servicio/instalación
                'codigo_postal' => $validated['codigo_postal'],
                'calle' => $validated['calle'],
                'numero_exterior' => $validated['numero_exterior'],
                'numero_interior' => $validated['numero_interior'],
                'colonia' => $validated['colonia'],
                'municipio' => $validated['municipio'],
                'estado' => $validated['estado'],
                'pais' => $validated['pais'],
                'empresa_id' => $empresaId,
            ];

            // Domicilio fiscal - usar el de instalación o el proporcionado
            if ($requiereFactura) {
                if ($mismaDireccionFiscal) {
                    // Usar misma dirección
                    $clienteData['domicilio_fiscal_cp'] = $validated['codigo_postal'];
                    $clienteData['domicilio_fiscal_calle'] = $validated['calle'] . ' #' . $validated['numero_exterior'];
                    $clienteData['domicilio_fiscal_colonia'] = $validated['colonia'];
                    $clienteData['domicilio_fiscal_municipio'] = $validated['municipio'];
                    $clienteData['domicilio_fiscal_estado'] = $validated['estado'];
                } else {
                    // Usar dirección fiscal diferente
                    $clienteData['domicilio_fiscal_cp'] = $validated['domicilio_fiscal_cp'];
                    $clienteData['domicilio_fiscal_calle'] = $validated['domicilio_fiscal_calle'] ?? null;
                    $clienteData['domicilio_fiscal_colonia'] = $validated['domicilio_fiscal_colonia'] ?? null;
                    $clienteData['domicilio_fiscal_municipio'] = $validated['domicilio_fiscal_municipio'] ?? null;
                    $clienteData['domicilio_fiscal_estado'] = $validated['domicilio_fiscal_estado'] ?? null;
                }
            }

            if ($request->filled('password')) {
                $clienteData['password'] = Hash::make($validated['password']);
            }

            $cliente = Cliente::updateOrCreate(['email' => $validated['email']], $clienteData);

            // 2. Guardar firma digital como imagen
            $firmaPath = null;
            if (!empty($validated['firma']) && str_starts_with($validated['firma'], 'data:image')) {
                $firmaData = substr($validated['firma'], strpos($validated['firma'], ',') + 1);
                $firmaDecoded = base64_decode($firmaData);
                $firmaPath = 'contratos/firmas/' . $cliente->id . '_' . time() . '.png';
                \Illuminate\Support\Facades\Storage::disk('public')->put($firmaPath, $firmaDecoded);
            }

            // 3. Crear Renta
            $fechaInicio = Carbon::now();
            $fechaFin = $fechaInicio->copy()->addMonths($plan->meses_minimos ?? 1);

            $renta = Renta::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'monto_mensual' => $plan->precio_mensual,
                'deposito_garantia' => $plan->deposito_garantia,
                'dia_pago' => $fechaInicio->day > 28 ? 28 : $fechaInicio->day,
                'estado' => 'pendiente_pago',
                'renovacion_automatica' => true,
                'meses_duracion' => $plan->meses_minimos,
                'condiciones_especiales' => "Plan Rentado: {$plan->nombre}. Meses mínimos: {$plan->meses_minimos}",
                // Documentos y firma
                'ine_frontal' => $validated['ine_frontal'] ?? null,
                'ine_trasera' => $validated['ine_trasera'] ?? null,
                'comprobante_domicilio' => $validated['comprobante_domicilio'] ?? null,
                'firma_digital' => $firmaPath,
                'firmado_nombre' => $validated['nombre_firmante'],
                'firmado_at' => now(),
                'firmado_ip' => $request->ip(),
            ]);

            // 3. Crear Venta (Primer mes + Deposito)
            $subtotal = $plan->precio_mensual + $plan->deposito_garantia;
            $iva = round($subtotal * 0.16, 2);
            $total = $subtotal + $iva;

            $venta = Venta::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'fecha' => now(),
                'estado' => 'pendiente',
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total' => $total,
                'metodo_pago' => $validated['metodo_pago'],
                'origen' => 'web_renta',
                'notas' => 'Contratación Plan Renta: ' . $plan->nombre,
            ]);

            VentaItem::create([
                'venta_id' => $venta->id,
                'ventable_type' => Renta::class,
                'ventable_id' => $renta->id,
                'cantidad' => 1,
                'precio' => $subtotal,
                'subtotal' => $subtotal,
                'iva' => $iva,
                'descripcion' => "Primer Mes y Depósito de Garantía - {$plan->nombre}",
            ]);

            DB::commit();

            Auth::guard('client')->login($cliente);

            return redirect()->back()->with([
                'success' => '¡Registro completado! Procediendo al pago...',
                'created_renta_id' => $renta->id,
                'metodo_pago' => $validated['metodo_pago']
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error contratacion renta: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al procesar la contratación.']);
        }
    }
}
