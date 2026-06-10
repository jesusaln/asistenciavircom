<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\EmpresaConfiguracion;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FacturacionPublicaController extends Controller
{
    public function index(Request $request)
    {
        $telefono = $request->input('telefono');
        $cliente = null;

        if ($telefono) {
            $digitsOnly = preg_replace('/\D+/', '', $telefono);
            if (strlen($digitsOnly) >= 10) {
                $last10 = substr($digitsOnly, -10);
                $empresaId = EmpresaResolver::resolveId();
                $cliente = Cliente::where('empresa_id', $empresaId)
                    ->where('telefono', 'like', "%{$last10}%")
                    ->first();
            }
        }

        return Inertia::render('Public/Facturacion', [
            'cliente' => $cliente,
            'telefono' => $telefono,
            'empresa' => $this->getPublicEmpresaData(),
        ]);
    }

    public function store(Request $request)
    {
        $telefono = $request->input('telefono');
        if ($telefono) {
            $telefonoLimpio = preg_replace('/\D/', '', $telefono);
            $request->merge(['telefono' => $telefonoLimpio]);
        }

        $rfc = $request->input('rfc');
        if ($rfc) {
            $rfcLimpio = strtoupper(trim($rfc));
            $request->merge(['rfc' => $rfcLimpio]);
        }

        $nombre = $request->input('nombre');
        if ($nombre) {
            $nombreLimpio = mb_strtoupper(trim($nombre), 'UTF-8');
            $request->merge(['nombre' => $nombreLimpio]);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'rfc' => ['required', 'string', 'regex:/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/'],
            'regimen_fiscal' => 'required|string',
            'uso_cfdi' => 'required|string',
            'domicilio_fiscal_cp' => 'required|string|digits:5',
            'email' => 'required|email|max:255',
            'telefono' => 'required|string|digits:10',
            'ticket_folio' => 'required|string|max:50',
            'mensaje' => 'nullable|string|max:1000',
        ], [
            'rfc.regex' => 'El RFC no tiene un formato válido (debe tener 12 caracteres para Persona Moral o 13 caracteres para Persona Física, sin guiones ni espacios).',
            'telefono.digits' => 'El teléfono debe tener exactamente 10 dígitos.',
            'domicilio_fiscal_cp.digits' => 'El código postal debe tener exactamente 5 dígitos.',
        ]);

        $empresaId = EmpresaResolver::resolveId();

        // 1. Validar que la venta exista en la empresa
        $venta = \App\Models\Venta::where('empresa_id', $empresaId)
            ->where('numero_venta', $validated['ticket_folio'])
            ->first();

        if (!$venta) {
            return back()->withErrors([
                'ticket_folio' => 'El folio de ticket ingresado no es válido o no existe en nuestro sistema.'
            ]);
        }

        // 2. Validar que no esté facturada ya
        if ($venta->cfdi_actual) {
            return back()->withErrors([
                'ticket_folio' => 'El ticket con el folio ingresado ya ha sido facturado anteriormente.'
            ]);
        }

        // 3. Validar coincidencia de cliente/teléfono para evitar facturar tickets de terceros (excepto si el cliente es genérico)
        $ventaCliente = $venta->cliente;
        if ($ventaCliente && $ventaCliente->rfc !== 'XAXX010101000' && $ventaCliente->rfc !== 'XEXX010101000') {
            $digitsOnly = preg_replace('/\D+/', '', $validated['telefono']);
            $last10 = substr($digitsOnly, -10);

            // Obtener el teléfono del cliente de la venta
            $ventaClientePhone = preg_replace('/\D+/', '', $ventaCliente->telefono ?? '');
            $ventaClienteLast10 = strlen($ventaClientePhone) >= 10 ? substr($ventaClientePhone, -10) : null;

            // Si el cliente de la venta tiene un teléfono registrado y no coincide con el ingresado, bloquear.
            if ($ventaClienteLast10 && $ventaClienteLast10 !== $last10) {
                return back()->withErrors([
                    'ticket_folio' => 'El folio de ticket ingresado corresponde a otro cliente registrado. Por favor verifica tus datos.'
                ]);
            }
        }

        // 4. Buscar o crear el cliente para guardarlo en la base de datos (Módulo de Clientes)
        $digitsOnly = preg_replace('/\D+/', '', $validated['telefono']);
        $last10 = substr($digitsOnly, -10);

        // Buscar por teléfono
        $cliente = Cliente::where('empresa_id', $empresaId)
            ->where('telefono', 'like', "%{$last10}%")
            ->first();

        // Buscar por RFC (si no es genérico)
        if (!$cliente && $validated['rfc'] !== 'XAXX010101000' && $validated['rfc'] !== 'XEXX010101000') {
            $cliente = Cliente::where('empresa_id', $empresaId)
                ->where('rfc', $validated['rfc'])
                ->first();
        }

        // Si no se encuentra, pero la venta tiene un cliente específico asociado, usar ese
        if (!$cliente && $ventaCliente && $ventaCliente->rfc !== 'XAXX010101000') {
            $cliente = $ventaCliente;
        }

        // Si sigue sin existir, instanciar un nuevo cliente
        if (!$cliente) {
            $cliente = new Cliente();
            $cliente->empresa_id = $empresaId;
        }

        // Asignar lista de precios por defecto para clientes nuevos
        if (!$cliente->exists) {
            $defaultPriceListId = \App\Models\PriceList::where('clave', 'publico_general')->value('id');
            if ($defaultPriceListId) {
                $cliente->price_list_id = $defaultPriceListId;
            }
        }

        // Guardar/Actualizar campos fiscales del cliente
        $cliente->nombre_razon_social = $validated['nombre'];
        $cliente->razon_social = $validated['nombre'];
        $cliente->rfc = $validated['rfc'];
        $cliente->regimen_fiscal = $validated['regimen_fiscal'];
        $cliente->uso_cfdi = $validated['uso_cfdi'];
        $cliente->domicilio_fiscal_cp = $validated['domicilio_fiscal_cp'];
        $cliente->email = $validated['email'];
        $cliente->telefono = $validated['telefono'];
        $cliente->requiere_factura = true;
        $cliente->tipo_persona = (strlen($validated['rfc']) === 12) ? 'moral' : 'fisica';
        $cliente->activo = true;
        $cliente->save();

        // Vincular la venta al cliente si no coincide
        if ($venta->cliente_id !== $cliente->id) {
            $venta->cliente_id = $cliente->id;
            $venta->save();
        }

        // Enviar notificación a la campana del panel administrativo
        try {
            \App\Models\UserNotification::createFacturaSolicitudNotification($cliente, $venta);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error creating UserNotification for factura request: ' . $e->getMessage());
        }

        // Registrar en CRM
        \App\Models\CrmProspecto::create([
            'empresa_id' => $empresaId,
            'nombre' => mb_strtoupper($validated['nombre']),
            'telefono' => $validated['telefono'],
            'email' => $validated['email'],
            'origen' => 'web',
            'etapa' => 'prospecto',
            'prioridad' => 'alta',
            'notas' => "📄 SOLICITUD DE FACTURACIÓN ELECTRÓNICA\n\n" .
                "• RFC: " . mb_strtoupper($validated['rfc']) . "\n" .
                "• Razón Social: " . mb_strtoupper($validated['nombre']) . "\n" .
                "• Régimen Fiscal: " . $validated['regimen_fiscal'] . "\n" .
                "• Uso de CFDI: " . $validated['uso_cfdi'] . "\n" .
                "• CP Domicilio Fiscal: " . $validated['domicilio_fiscal_cp'] . "\n" .
                "• Notas adicionales: " . (!empty($validated['mensaje']) ? $validated['mensaje'] : 'Ninguna'),
        ]);
        return back()->with('success', '¡Solicitud de facturación recibida! Validaremos los datos y te enviaremos la factura por correo a la brevedad.');
    }

    private function getPublicEmpresaData(): ?array
    {
        $empresaId = EmpresaResolver::resolveId();
        $empresaModel = Empresa::find($empresaId);
        $configuracion = EmpresaConfiguracion::getConfig($empresaId);

        return $empresaModel ? array_merge($empresaModel->toArray(), [
            'color_principal' => $configuracion->color_principal,
            'color_secundario' => $configuracion->color_secundario,
            'color_terciario' => $configuracion->color_terciario,
            'logo_url' => $configuracion->logo_url,
            'favicon_url' => $configuracion->favicon_url,
            'nombre_comercial_config' => $configuracion->nombre_empresa,
            'telefono' => $configuracion->telefono,
            'email' => $configuracion->email,
            'direccion' => $configuracion->direccion,
            'whatsapp' => $configuracion->whatsapp,
        ]) : null;
    }
}
