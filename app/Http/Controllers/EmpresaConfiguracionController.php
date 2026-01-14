<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\EmpresaConfiguracion;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EmpresaConfiguracionController extends Controller
{
    /**
     * Mostrar la configuración de la empresa
     */
    public function index()
    {
        try {
            $configuracion = EmpresaConfiguracion::getConfig();
            $empresaId = EmpresaResolver::resolveId();
            $empresa = $empresaId ? Empresa::find($empresaId) : null;

            return Inertia::render('EmpresaConfiguracion/Index', [
                'configuracion' => $configuracion,
                'empresa' => $empresa ? $empresa->only([
                    'whatsapp_enabled',
                    'whatsapp_business_account_id',
                    'whatsapp_phone_number_id',
                    'whatsapp_sender_phone',
                    // NO enviar tokens encriptados al frontend por seguridad
                    // 'whatsapp_access_token', // REMOVIDO - sensible
                    // 'whatsapp_app_secret', // REMOVIDO - sensible
                    'whatsapp_webhook_verify_token',
                    'whatsapp_default_language',
                    'whatsapp_template_payment_reminder',
                ]) : null,
                'cuentas_bancarias' => \App\Models\CuentaBancaria::activas()->get(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error en EmpresaConfiguracionController@index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Error al cargar la configuración'], 500);
        }
    }

    /**
     * Obtener configuración actual (API)
     */
    public function getConfig()
    {
        try {
            $configuracion = EmpresaConfiguracion::getConfig();

            return response()->json([
                'configuracion' => $configuracion,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en EmpresaConfiguracionController@getConfig', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Error al obtener configuración'], 500);
        }
    }

    /**
     * ZONA DE PELIGRO: Eliminar datos por módulo
     * Solo accesible para super-admin
     */
    public function eliminarModulo(Request $request, string $modulo)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Solo el Super Administrador puede ejecutar esta acción.'
            ], 403);
        }

        $count = 0;

        DB::beginTransaction();
        try {
            switch ($modulo) {
                case 'productos':
                    $count = \App\Models\Producto::count();
                    \App\Models\ProductoSerie::truncate();
                    \App\Models\InventarioMovimiento::truncate();
                    \App\Models\CompraItem::truncate();
                    \App\Models\VentaItem::truncate();
                    \App\Models\Producto::truncate();
                    break;

                case 'compras':
                    $count = \App\Models\Compra::count();
                    \App\Models\CompraItem::truncate();
                    \App\Models\CuentasPorPagar::truncate();
                    \App\Models\Compra::truncate();
                    break;

                case 'ventas':
                    $count = \App\Models\Venta::count();
                    \App\Models\VentaItem::truncate();
                    \App\Models\CuentasPorCobrar::truncate();
                    \App\Models\Venta::truncate();
                    \App\Models\Cotizacion::truncate();
                    \App\Models\Pedido::truncate();
                    break;

                case 'bancos':
                    $count = \App\Models\CuentaBancaria::count();
                    \App\Models\MovimientoBancario::truncate();
                    \App\Models\TraspasoBancario::truncate();
                    \App\Models\CuentaBancaria::truncate();
                    break;

                case 'inventario':
                    $count = \App\Models\InventarioMovimiento::count();
                    \App\Models\ProductoSerie::truncate();
                    \App\Models\InventarioMovimiento::truncate();
                    \App\Models\Traspaso::truncate();
                    break;

                case 'clientes':
                    $count = \App\Models\Cliente::count();
                    \App\Models\Cliente::truncate();
                    break;

                case 'proveedores':
                    $count = \App\Models\Proveedor::count();
                    \App\Models\Proveedor::truncate();
                    break;

                case 'cfdis':
                    $count = \App\Models\Cfdi::count();
                    \App\Models\CfdiConcepto::truncate();
                    \App\Models\SatDescargaDetalle::truncate();
                    \App\Models\SatDescargaMasiva::truncate();
                    \App\Models\Cfdi::truncate();
                    break;

                case 'todo':
                    $count = 0;

                    \App\Models\VentaItem::truncate();
                    \App\Models\CompraItem::truncate();
                    \App\Models\CuentasPorCobrar::truncate();
                    \App\Models\CuentasPorPagar::truncate();
                    $count += \App\Models\Venta::count();
                    \App\Models\Venta::truncate();
                    $count += \App\Models\Compra::count();
                    \App\Models\Compra::truncate();
                    \App\Models\Cotizacion::truncate();
                    \App\Models\Pedido::truncate();

                    \App\Models\ProductoSerie::truncate();
                    \App\Models\InventarioMovimiento::truncate();
                    \App\Models\Traspaso::truncate();

                    $count += \App\Models\Producto::count();
                    \App\Models\Producto::truncate();

                    \App\Models\CfdiConcepto::truncate();
                    \App\Models\SatDescargaDetalle::truncate();
                    \App\Models\SatDescargaMasiva::truncate();
                    $count += \App\Models\Cfdi::count();
                    \App\Models\Cfdi::truncate();

                    \App\Models\MovimientoBancario::truncate();
                    \App\Models\TraspasoBancario::truncate();
                    \App\Models\CuentaBancaria::truncate();

                    $count += \App\Models\Cliente::count();
                    \App\Models\Cliente::truncate();
                    $count += \App\Models\Proveedor::count();
                    \App\Models\Proveedor::truncate();

                    \App\Models\Categoria::truncate();
                    \App\Models\Marca::truncate();
                    break;

                case 'reinicio':
                    $count = 0;

                    \App\Models\VentaItem::truncate();
                    \App\Models\CompraItem::truncate();
                    \App\Models\CuentasPorCobrar::truncate();
                    \App\Models\CuentasPorPagar::truncate();
                    \App\Models\Venta::truncate();
                    \App\Models\Compra::truncate();
                    \App\Models\Cotizacion::truncate();
                    \App\Models\Pedido::truncate();
                    \App\Models\ProductoSerie::truncate();
                    \App\Models\InventarioMovimiento::truncate();
                    \App\Models\Traspaso::truncate();
                    \App\Models\Producto::truncate();
                    \App\Models\CfdiConcepto::truncate();
                    \App\Models\SatDescargaDetalle::truncate();
                    \App\Models\SatDescargaMasiva::truncate();
                    \App\Models\Cfdi::truncate();
                    \App\Models\MovimientoBancario::truncate();
                    \App\Models\TraspasoBancario::truncate();
                    \App\Models\CuentaBancaria::truncate();
                    \App\Models\Cliente::truncate();
                    \App\Models\Proveedor::truncate();
                    \App\Models\Categoria::truncate();
                    \App\Models\Marca::truncate();

                    $count = \App\Models\User::where('id', '!=', auth()->id())->count();
                    \App\Models\User::where('id', '!=', auth()->id())->delete();
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => "Módulo '{$modulo}' no reconocido."
                    ], 400);
            }

            DB::commit();

            Log::warning('DANGER ZONE: Eliminación masiva ejecutada', [
                'modulo' => $modulo,
                'count' => $count,
                'user_id' => auth()->id(),
                'user_email' => auth()->user()->email,
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron los datos de '{$modulo}' correctamente.",
                'count' => $count
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('DANGER ZONE: Error en eliminación masiva', [
                'modulo' => $modulo,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
    }
}
