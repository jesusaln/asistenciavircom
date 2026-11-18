<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoCotizacion;
use App\Http\Controllers\Controller;
use App\Mail\CotizacionPolizaTiMail;
use App\Models\Cotizacion;
use App\Models\Empresa;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PolizaCalculatorController extends Controller
{
    /**
     * Envía por correo la cotización de póliza TI calculada
     */
    public function sendEmail(Request $request)
    {
        $validated = $request->validate([
            'cliente_nombre' => 'required|string|max:255',
            'cliente_email' => 'required|email|max:255',
            'cliente_telefono' => 'nullable|string|max:50',
            'cliente_id' => 'nullable|integer',
            'computadoras' => 'nullable|integer|min:0',
            'precio_unitario_pc' => 'nullable|numeric|min:0',
            'servidores_cantidad' => 'nullable|integer|min:0',
            'tiene_servidor' => 'nullable|boolean',
            'tipo_red' => 'nullable|string|max:50',
            'tiene_contpaqi' => 'nullable|boolean',
            'camaras' => 'nullable|integer|min:0',
            'relojes_checadores' => 'nullable|integer|min:0',
            'controles_acceso' => 'nullable|integer|min:0',
            'tiene_alarma' => 'nullable|boolean',
            'visitas_mensuales' => 'nullable|integer|min:0',
            'sla_tiempo' => 'nullable|string|max:100',
            'subtotal_lista' => 'nullable|numeric|min:0',
            'descuento_comercial_tipo' => 'nullable|string|in:porcentaje,fijo',
            'descuento_comercial_valor' => 'nullable|numeric|min:0',
            'descuento_monto' => 'nullable|numeric|min:0',
            'es_paquete_integral' => 'nullable|boolean',
            'total_mensual' => 'required|numeric|min:0',
            'total_anual' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:1000',
        ]);

        try {
            $empresaId = EmpresaResolver::resolveId();
            $empresa = $empresaId ? Empresa::find($empresaId) : Empresa::first();

            // Si total_anual no viene, calcular con 10% de descuento por pronto pago
            if (empty($validated['total_anual'])) {
                $validated['total_anual'] = round($validated['total_mensual'] * 12 * 0.90, 2);
            }

            Mail::to($validated['cliente_email'])
                ->send(new CotizacionPolizaTiMail($validated, $empresa));

            Log::info("Propuesta de póliza TI enviada por correo a {$validated['cliente_email']} para {$validated['cliente_nombre']}");

            return response()->json([
                'success' => true,
                'message' => "Propuesta enviada exitosamente al correo {$validated['cliente_email']}.",
                'data' => $validated,
            ]);
        } catch (\Throwable $e) {
            Log::error("Error enviando cotización de póliza TI por correo: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar el correo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Guarda la cotización de póliza TI calculada como registro en el ERP
     */
    public function guardarCotizacion(Request $request)
    {
        $validated = $request->validate([
            'cliente_nombre' => 'required|string|max:255',
            'cliente_email' => 'nullable|email|max:255',
            'cliente_telefono' => 'nullable|string|max:50',
            'cliente_id' => 'nullable|integer',
            'computadoras' => 'nullable|integer|min:0',
            'precio_unitario_pc' => 'nullable|numeric|min:0',
            'servidores_cantidad' => 'nullable|integer|min:0',
            'tipo_red' => 'nullable|string|max:50',
            'tiene_contpaqi' => 'nullable|boolean',
            'camaras' => 'nullable|integer|min:0',
            'relojes_checadores' => 'nullable|integer|min:0',
            'controles_acceso' => 'nullable|integer|min:0',
            'tiene_alarma' => 'nullable|boolean',
            'visitas_mensuales' => 'nullable|integer|min:0',
            'sla_tiempo' => 'nullable|string|max:100',
            'subtotal_lista' => 'nullable|numeric|min:0',
            'descuento_monto' => 'nullable|numeric|min:0',
            'descuento_comercial_tipo' => 'nullable|string',
            'descuento_comercial_valor' => 'nullable|numeric',
            'es_paquete_integral' => 'nullable|boolean',
            'total_mensual' => 'required|numeric|min:0',
            'total_anual' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($validated, $request) {
                $subtotal = round((float)($validated['total_mensual'] ?? 0), 2);
                $descuento = round((float)($validated['descuento_monto'] ?? 0), 2);
                $iva = round($subtotal * 0.16, 2);
                $totalConIva = round($subtotal + $iva, 2);

                // Armar resumen detallado de la infraestructura cotizada
                $detalles = [];
                if (!empty($validated['computadoras'])) {
                    $detalles[] = "• {$validated['computadoras']} Computadoras/Laptops (Tarifa: $" . number_format($validated['precio_unitario_pc'] ?? 0, 2) . " c/u)";
                }
                if (!empty($validated['servidores_cantidad'])) {
                    $detalles[] = "• {$validated['servidores_cantidad']} Servidor(es) Dedicado(s) / BD SQL (Monitoreo + Backup)";
                }
                if (!empty($validated['tipo_red']) && $validated['tipo_red'] !== 'ninguna') {
                    $redNom = $validated['tipo_red'] === 'empresarial' ? 'Red Empresarial / Wi-Fi Corporativo' : 'Red Básica PyME';
                    $detalles[] = "• Infraestructura de {$redNom}";
                }
                if (!empty($validated['tiene_contpaqi'])) {
                    $detalles[] = "• Sistemas CONTPAQi / ERP (Soporte a timbrado y SQL)";
                }
                if (!empty($validated['camaras'])) {
                    $detalles[] = "• {$validated['camaras']} Cámaras de Seguridad CCTV + DVR/NVR";
                }
                if (!empty($validated['tiene_alarma'])) {
                    $detalles[] = "• Sistema de Alarma y Sensores de Seguridad";
                }
                if (!empty($validated['relojes_checadores'])) {
                    $detalles[] = "• {$validated['relojes_checadores']} Relojes Checadores Biométricos";
                }
                if (!empty($validated['controles_acceso'])) {
                    $detalles[] = "• {$validated['controles_acceso']} Controles de Acceso (Chapas/Lector)";
                }
                $detalles[] = "• " . ($validated['visitas_mensuales'] ?? 1) . " Visita(s) presencial(es) en sitio al mes";
                $detalles[] = "• SLA Tiempo de respuesta: " . ($validated['sla_tiempo'] ?? 'Menos de 45 minutos');

                if (!empty($validated['es_paquete_integral'])) {
                    $detalles[] = "⭐ PAQUETE SOLUCIÓN INTEGRAL 360° APLICADO (Bonificación 10%)";
                }

                $notasTexto = "COTIZACIÓN DE PÓLIZA TI MENSUAL:\n" . implode("\n", $detalles);
                if (!empty($validated['notas'])) {
                    $notasTexto .= "\n\nObservaciones Adicionales:\n" . $validated['notas'];
                }

                $cotizacion = new Cotizacion();
                $cotizacion->cliente_id = !empty($validated['cliente_id']) ? (int)$validated['cliente_id'] : null;
                $cotizacion->equipo_nombre = "Póliza TI Mensual — " . ($validated['cliente_nombre'] ?? 'Cliente');
                $cotizacion->fecha_cotizacion = now()->toDateString();
                $cotizacion->subtotal = $subtotal;
                $cotizacion->descuento_general = $descuento;
                $cotizacion->descuento_items = 0;
                $cotizacion->iva = $iva;
                $cotizacion->total = $totalConIva;
                $cotizacion->moneda = 'MXN';
                $cotizacion->tipo_cambio = 1.00;
                $cotizacion->notas = $notasTexto;
                $cotizacion->estado = 'pendiente';
                $cotizacion->save();

                Log::info("Cotización de Póliza TI creada exitosamente: #{$cotizacion->numero_cotizacion} para {$validated['cliente_nombre']}");

                return response()->json([
                    'success' => true,
                    'message' => "Cotización #{$cotizacion->numero_cotizacion} generada exitosamente en el ERP.",
                    'cotizacion_id' => $cotizacion->id,
                    'numero_cotizacion' => $cotizacion->numero_cotizacion,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error("Error guardando cotización de póliza TI: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo guardar la cotización: ' . $e->getMessage(),
            ], 500);
        }
    }
}