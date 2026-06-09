<?php

namespace App\Http\Controllers;

use App\Models\PlanPoliza;
use App\Models\Cliente;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use App\Models\Venta;
use App\Models\VentaItem;
use App\Models\SatRegimenFiscal;
use App\Models\SatEstado;
use App\Models\SatUsoCfdi;
use App\Models\Equipo; // Added import

use Illuminate\Support\Facades\DB;
use App\Models\PolizaServicio;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewClientRegisteredNotification;
use Illuminate\Support\Facades\Auth;

class ContratacionPolizaController extends Controller
{
    /**
     * Muestra el formulario de contratación para un plan específico.
     */
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
            'favicon_url' => $configuracion->favicon_url,
            'nombre_comercial_config' => $configuracion->nombre_empresa,
        ]) : null;

        $plan = PlanPoliza::where('slug', $slug)
            ->where('activo', true)
            ->firstOrFail();

        // Pre-calcular precio anual con 15% descuento para mostrarlo correctamente
        if ($plan->precio_mensual > 0) {
            $plan->precio_anual_calculado = $plan->precio_mensual * 12 * 0.85;
            $plan->ahorro_anual_calculado = ($plan->precio_mensual * 12) - $plan->precio_anual_calculado;
        }

        // Si el usuario está autenticado, intentamos pre-cargar sus datos o los de su cliente asociado
        $clienteData = null;

        // 1. Verificar si es un Cliente del Portal autenticado
        if (Auth::guard('client')->check()) {
            $cliente = Auth::guard('client')->user();
            $clienteData = [
                'nombre_comercial' => $cliente->nombre_razon_social,
                'razon_social' => $cliente->nombre_razon_social,
                'tipo_persona' => $cliente->tipo_persona,
                'rfc' => $cliente->rfc ?? '',
                'curp' => $cliente->curp ?? '',
                'regimen_fiscal' => $cliente->regimen_fiscal ?? '',
                'uso_cfdi' => $cliente->uso_cfdi ?? '',
                'domicilio_fiscal_cp' => $cliente->domicilio_fiscal_cp ?? '',
                'codigo_postal' => $cliente->codigo_postal ?? '',
                'calle' => $cliente->calle ?? '',
                'numero_exterior' => $cliente->numero_exterior ?? '',
                'numero_interior' => $cliente->numero_interior ?? '',
                'colonia' => $cliente->colonia ?? '',
                'municipio' => $cliente->municipio ?? '',
                'estado' => $cliente->estado ?? '',
                'pais' => $cliente->pais ?? 'MX',
                'email' => $cliente->email,
                'telefono' => $cliente->telefono,
                'credito_activo' => $cliente->credito_activo,
                'credito_disponible' => $cliente->credito_disponible,
                'estado_credito' => $cliente->estado_credito,
            ];
        }
        // 2. Fallback para Staff
        elseif (auth()->check()) {
            $user = auth()->user();
            $cliente = Cliente::where('email', $user->email)->first();

            if ($cliente) {
                $clienteData = [
                    'nombre_comercial' => $cliente->nombre_razon_social,
                    'razon_social' => $cliente->nombre_razon_social,
                    'tipo_persona' => $cliente->tipo_persona,
                    'rfc' => $cliente->rfc ?? '',
                    'curp' => $cliente->curp ?? '',
                    'regimen_fiscal' => $cliente->regimen_fiscal ?? '',
                    'uso_cfdi' => $cliente->uso_cfdi ?? '',
                    'domicilio_fiscal_cp' => $cliente->domicilio_fiscal_cp ?? '',
                    'codigo_postal' => $cliente->codigo_postal ?? '',
                    'calle' => $cliente->calle ?? '',
                    'numero_exterior' => $cliente->numero_exterior ?? '',
                    'numero_interior' => $cliente->numero_interior ?? '',
                    'colonia' => $cliente->colonia ?? '',
                    'municipio' => $cliente->municipio ?? '',
                    'estado' => $cliente->estado ?? '',
                    'pais' => $cliente->pais ?? 'MX',
                    'email' => $cliente->email,
                    'telefono' => $cliente->telefono,
                ];
            } else {
                $clienteData = [
                    'nombre_comercial' => $user->name,
                    'email' => $user->email,
                ];
            }
        }

        $cicloInicial = $request->query('ciclo', 'mensual');

        return Inertia::render('Contratacion/Show', [
            'empresa' => $empresa,
            'plan' => $plan->append(['icono_display', 'ahorro_anual']),
            'clienteData' => $clienteData,
            'cicloInicial' => $cicloInicial,
            'catalogos' => [
                'regimenes' => SatRegimenFiscal::get(),
                'usosCfdi' => SatUsoCfdi::where('activo', true)->get(),
                'estados' => SatEstado::orderBy('nombre')->get()->map(fn($e) => ['value' => $e->clave, 'label' => $e->nombre]),
                'tiposPersona' => [
                    ['value' => 'fisica', 'label' => 'Persona Física'],
                    ['value' => 'moral', 'label' => 'Persona Moral'],
                ],
                'formasPago' => [
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

    /**
     * Procesa la contratación y el pago.
     */
    public function procesar(Request $request, string $slug)
    {
        $requiereFactura = $request->boolean('requiere_factura');

        $validated = $request->validate([
            'requiere_factura' => 'boolean',
            'nombre_razon_social' => 'required|string|max:255',
            'razon_social' => $requiereFactura ? 'required|string|max:255' : 'nullable|string|max:255',
            'tipo_persona' => $requiereFactura ? 'required|in:fisica,moral' : 'nullable',
            'rfc' => $requiereFactura
                ? ['required', 'string', 'regex:/^([A-ZÑ&]{3,4})[0-9]{6}[A-Z0-9]{3}$/i']
                : 'nullable|string',
            'curp' => 'nullable|string|size:18',
            'regimen_fiscal' => $requiereFactura ? 'required|exists:sat_regimenes_fiscales,clave' : 'nullable',
            'uso_cfdi' => $requiereFactura ? 'required|exists:sat_usos_cfdi,clave' : 'nullable',
            'domicilio_fiscal_cp' => $requiereFactura ? 'required|digits:5' : 'nullable',

            'email' => 'required|email|max:255',
            'telefono' => 'required|string|max:20',
            'whatsapp_optin' => 'nullable|boolean',

            'codigo_postal' => 'required|digits:5',
            'calle' => 'required|string|max:255',
            'numero_exterior' => 'required|string|max:20',
            'numero_interior' => 'nullable|string|max:20',
            'colonia' => 'required|string|max:255',
            'municipio' => 'required|string|max:255',
            'estado' => 'required|string|size:3',
            'pais' => 'required|string|max:10',

            'metodo_pago' => 'required|in:paypal,mercadopago,tarjeta,credito',
            'periodo' => 'required|in:mensual,anual',
            'password' => auth('client')->check() ? 'nullable|confirmed' : 'required|string|min:8|confirmed',
            'aceptar_terminos' => 'accepted',
            'equipos' => 'nullable|array', // Opcional - se manejan en módulo EquiposPoliza
            'equipos.*.marca' => 'nullable|string|max:100',
            'equipos.*.modelo' => 'nullable|string|max:100',
            'equipos.*.serie' => 'nullable|string|max:100',
        ], [
            'rfc.regex' => 'El formato del RFC no es válido.',
            'regimen_fiscal.required' => 'El Régimen Fiscal es obligatorio para facturación.',
            'codigo_postal.digits' => 'El Código Postal debe tener 5 dígitos.',
            'domicilio_fiscal_cp.digits' => 'El CP Fiscal debe tener 5 dígitos.',
            'aceptar_terminos.accepted' => 'Debes aceptar los términos.',
            'password.confirmed' => 'La contraseña no coincide.',
            'equipos.min' => 'Registra al menos un equipo.',
        ]);

        $plan = PlanPoliza::where('slug', $slug)->firstOrFail();

        // Validación extra de cantidad de equipos permitidos por el plan
        if ($plan->max_equipos && count($validated['equipos']) > $plan->max_equipos) {
            return back()->withErrors(['equipos' => "El plan permite máximo {$plan->max_equipos} equipos."]);
        }

        $empresaId = EmpresaResolver::resolveId() ?? 1;

        // Pre-validación de series duplicadas para evitar fallo en transacción
        foreach ($validated['equipos'] as $item) {
            if (!empty($item['serie'])) {
                $serieEnUso = Equipo::where('empresa_id', $empresaId)
                    ->where('numero_serie', $item['serie'])
                    ->exists();

                if ($serieEnUso) {
                    return back()->withErrors(['equipos' => "La serie {$item['serie']} ya se encuentra registrada en el sistema. Contacta a soporte si crees que es un error."]);
                }
            }
        }

        DB::beginTransaction();
        try {
            // 1. Datos para Crear o Actualizar Cliente
            $clienteData = [
                'nombre_razon_social' => $validated['nombre_razon_social'],
                'razon_social' => $requiereFactura ? $validated['razon_social'] : $validated['nombre_razon_social'],
                'tipo_persona' => $validated['tipo_persona'] ?? ($requiereFactura ? 'fisica' : null),
                'rfc' => strtoupper($requiereFactura ? $validated['rfc'] : 'XAXX010101000'),
                'curp' => strtoupper($validated['curp'] ?? null),
                'regimen_fiscal' => $requiereFactura ? $validated['regimen_fiscal'] : '616',
                'uso_cfdi' => $requiereFactura ? $validated['uso_cfdi'] : 'S01',
                'domicilio_fiscal_cp' => $validated['domicilio_fiscal_cp'] ?? $validated['codigo_postal'],

                'email' => $validated['email'],
                'telefono' => $validated['telefono'],
                'whatsapp_optin' => $validated['whatsapp_optin'] ?? false,

                'codigo_postal' => $validated['codigo_postal'],
                'calle' => $validated['calle'],
                'numero_exterior' => $validated['numero_exterior'],
                'numero_interior' => $validated['numero_interior'],
                'colonia' => $validated['colonia'],
                'municipio' => $validated['municipio'],
                'estado' => $validated['estado'],
                'pais' => $validated['pais'],
                'direccion' => "{$validated['calle']} #{$validated['numero_exterior']}, {$validated['colonia']}, {$validated['municipio']}, {$validated['estado']}",

                'origen' => 'web_poliza',
                'empresa_id' => $empresaId,
                'tipo' => 'cliente',
                'requiere_factura' => $requiereFactura,
            ];

            // Solo actualizar contraseña si se proporciona
            if ($request->filled('password')) {
                $clienteData['password'] = Hash::make($validated['password']);
            }

            $cliente = Cliente::updateOrCreate(
                ['email' => $validated['email']],
                $clienteData
            );

            $esServicioUnico = $plan->slug === 'servicio-unico';

            // 2. Calcular vigencias
            $fechaInicio = Carbon::now();
            $esAnual = $validated['periodo'] === 'anual';

            if ($esServicioUnico) {
                // Servicio único: 7 días de garantía/vigencia
                $fechaFin = $fechaInicio->copy()->addDays(7);
            } else {
                $fechaFin = $esAnual ? $fechaInicio->copy()->addYear() : $fechaInicio->copy()->addMonth();
            }

            // Determinar estado inicial - SIEMPRE pendiente hasta que se confirme el pago real
            $estadoInicial = 'pendiente_pago';
            $estadoPago = 'pendiente';

            // 5. Generar Venta (Ingreso Financiero con IVA)
            // Regla de negocio: Forzar descuento de 15% para planes anuales
            if ($esAnual) {
                // $subtotal = $plan->precio_anual > 0 ? $plan->precio_anual : ($plan->precio_mensual * 12 * 0.85);
                // Forzamos el 15% para que coincida con lo mostrado en el frontend, ignorando la BD si es diferente
                $subtotal = $plan->precio_mensual * 12 * 0.85;
            } else {
                $subtotal = $plan->precio_mensual;
            }

            $iva = round($subtotal * 0.16, 2); // 16% IVA México Hardcoded
            $total = $subtotal + $iva;

            // 3. Crear la Póliza
            $poliza = PolizaServicio::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'folio' => null,
                'nombre' => $plan->nombre . ($esServicioUnico ? '' : (' - ' . ($esAnual ? 'Anual' : 'Mensual'))),
                'descripcion' => $esServicioUnico ? "Servicio Único a Demanda." : "Contratación Web. Plan {$validated['periodo']}.",
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'monto_mensual' => $esServicioUnico ? 0 : $plan->precio_mensual, // Servicio único no tiene mensualidad recurrente
                'limite_mensual_tickets' => $plan->tickets_incluidos,
                'horas_incluidas_mensual' => $plan->horas_incluidas,
                'costo_hora_excedente' => $plan->costo_hora_extra,
                'sla_horas_respuesta' => $plan->sla_horas_respuesta,
                'dia_cobro' => $fechaInicio->day > 28 ? 28 : $fechaInicio->day,
                'renovacion_automatica' => !$esServicioUnico, // IMPORTANTE: False si es servicio único
                'notificar_exceso_limite' => true,
                'estado' => $estadoInicial,
                'condiciones_especiales' => "Contratado vía Web. Ciclo: " . ($esAnual ? 'Anual' : 'Mensual'),
                'ultimo_reset_consumo_at' => $fechaInicio,
            ]);

            // Intentar obtener folio de venta
            $folioVenta = null;
            try {
                $folioVenta = app(\App\Services\Folio\FolioService::class)->getNextFolio('venta');
            } catch (\Exception $e) {
            }

            $venta = Venta::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'folio' => $folioVenta,
                'numero_venta' => $folioVenta ?? ('WEB-' . time()), // Usar folio o generar uno temporal
                'fecha' => Carbon::now(),
                'estado' => $estadoPago,
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total' => $total,
                'impuestos' => $iva, // Campo legacy o redundante si existe
                'metodo_pago' => $validated['metodo_pago'],
                'origen' => 'web_poliza',
                'notas' => 'Contratación Póliza: ' . $poliza->nombre,
            ]);

            VentaItem::create([
                'venta_id' => $venta->id,
                'ventable_type' => PolizaServicio::class,
                'ventable_id' => $poliza->id, // Vinculamos la póliza como producto vendido
                'cantidad' => 1,
                'precio' => $subtotal, // Precio unitario sin IVA
                'subtotal' => $subtotal,
                'iva' => $iva, // Si VentaItem tiene columna iva, agregarla
                'descripcion' => $plan->nombre . " ({$validated['periodo']})",
            ]);

            DB::commit();

            // Notificar al Staff sobre nuevo cliente/servicio
            // Buscar usuarios con permiso de ver clientes
            try {
                $staffToNotify = User::permission('view clientes')->get();
                if ($staffToNotify->count() > 0) {
                    Notification::send($staffToNotify, new NewClientRegisteredNotification($cliente));
                }
            } catch (\Exception $e) {
                // Loguear error pero no fallar la transacción de venta
                \Illuminate\Support\Facades\Log::error('Error enviando notificacion staff: ' . $e->getMessage());
            }

            // Auto-login del cliente para mejorar UX
            Auth::guard('client')->login($cliente);

            // Redirigir de vuelta con el ID de la póliza para iniciar el pago en el frontend
            return redirect()->back()->with([
                'success' => '¡Registro completado! Procediendo al pago...',
                'created_poliza_id' => $poliza->id,
                'metodo_pago' => $validated['metodo_pago']
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error contratacion poliza: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al procesar la contratación. Por favor intenta de nuevo.']);
        }
    }

    public function exito(string $slug)
    {
        $empresaId = EmpresaResolver::resolveId();
        $empresa = \App\Models\Empresa::find($empresaId);
        $plan = PlanPoliza::where('slug', $slug)->firstOrFail();
        return Inertia::render('Contratacion/Exito', [
            'empresa' => $empresa,
            'plan' => $plan
        ]);
    }
}
