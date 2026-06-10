<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Models\EntregaDinero;
use App\Models\User;
use App\Models\Venta;
use App\Models\Cobranza;
use App\Services\EntregaDineroService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EntregaMiCorteApiController extends Controller
{
    use ApiResponse;

    /**
     * Quien cobró declara que entregó efectivo físico (queda pendiente hasta que un tesorero confirme).
     */
    public function declararEntregaMiCorte(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->can('declarar entrega mi corte')) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'fecha_desde' => 'required|date_format:Y-m-d',
            'fecha_hasta' => 'required|date_format:Y-m-d|after_or_equal:fecha_desde',
            'monto_efectivo' => 'required|numeric|min:0.01',
            'notas' => 'nullable|string|max:500',
        ]);

        try {
            $entrega = DB::transaction(fn () => EntregaDineroService::declararEntregaMiCortePendiente(
                (int) $user->id,
                $validated['fecha_desde'],
                $validated['fecha_hasta'],
                (float) $validated['monto_efectivo'],
                $validated['notas'] ?? null
            ));

            return $this->success([
                'entrega_id' => $entrega->id,
                'estado' => $entrega->estado,
                'total' => (float) $entrega->total,
                'message' => 'Entrega registrada como pendiente de recepción por tesorería.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->serverError('No se pudo registrar la entrega', $e);
        }
    }

    /**
     * Lista entregas pendientes de recepción (tesoreros / admin).
     */
    public function pendientesRecepcion(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->can('confirmar entrega efectivo')) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $rows = EntregaDinero::query()
            ->with(['usuario:id,name,email', 'children.origen', 'origen'])
            ->where('estado', 'pendiente')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $data = $rows->map(function (EntregaDinero $e) {
            $this->attachOrigenInfo($e);
            
            $children = null;
            if (in_array($e->tipo_origen, ['lote', 'declaracion_mi_corte'], true)) {
                $children = $e->children->map(function ($child) {
                    $this->attachOrigenInfo($child);
                    return [
                        'id' => $child->id,
                        'total' => (float) $child->total,
                        'tipo_origen' => $child->tipo_origen,
                        'venta_numero' => $child->venta_numero,
                        'venta_cliente' => $child->venta_cliente,
                        'cobranza_concepto' => $child->cobranza_concepto,
                    ];
                });
            }

            return [
                'id' => $e->id,
                'user_id' => $e->user_id,
                'usuario_nombre' => $e->usuario?->name,
                'fecha_entrega' => $e->fecha_entrega?->format('Y-m-d'),
                'total' => (float) $e->total,
                'monto_efectivo' => (float) $e->monto_efectivo,
                'tipo_origen' => $e->tipo_origen,
                'notas' => $e->notas,
                'created_at' => $e->created_at?->toIso8601String(),
                'venta_numero' => $e->venta_numero,
                'venta_cliente' => $e->venta_cliente,
                'children' => $children,
            ];
        });

        return $this->success(['entregas' => $data]);
    }

    /**
     * Adjunta información del origen (venta o cobranza) a la entrega.
     */
    private function attachOrigenInfo($entrega)
    {
        if ($entrega->tipo_origen === 'venta' && $entrega->id_origen) {
            $venta = $entrega->origen ?? Venta::with('cliente')->find($entrega->id_origen);
            $entrega->venta_numero = $venta ? $venta->numero_venta : null;
            $entrega->venta_cliente = $venta && $venta->cliente ? $venta->cliente->nombre_razon_social : null;
        } elseif ($entrega->tipo_origen === 'cobranza' && $entrega->id_origen) {
            $cobranza = $entrega->origen ?? Cobranza::with('renta.cliente')->find($entrega->id_origen);
            $entrega->cobranza_concepto = $cobranza ? $cobranza->concepto : null;
            $entrega->venta_cliente = $cobranza && $cobranza->renta && $cobranza->renta->cliente ? $cobranza->renta->cliente->nombre_razon_social : null;
        }
    }

    /**
     * Tesorero confirma recepción física del efectivo (opcional cuenta bancaria para depósito automático).
     */
    public function confirmarRecepcion(Request $request, int $id)
    {
        $user = $request->user();
        if (! $user || ! $user->can('confirmar entrega efectivo')) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'cuenta_bancaria_id' => 'nullable|integer|exists:cuentas_bancarias,id',
            'registrar_deposito' => 'nullable|boolean',
            'notas_recibido' => 'nullable|string|max:500',
            'rechazar' => 'nullable|boolean',
            'items_confirmados' => 'nullable|array',
            'items_confirmados.*' => 'integer',
        ]);

        $entrega = EntregaDinero::query()->findOrFail($id);
        if ($entrega->estado !== 'pendiente') {
            return response()->json(['success' => false, 'message' => 'La entrega no está pendiente'], 422);
        }

        $rechazar = !empty($validated['rechazar']);
        $itemsConfirmados = $validated['items_confirmados'] ?? null;

        if (is_array($itemsConfirmados) && in_array($entrega->tipo_origen, ['lote', 'declaracion_mi_corte'], true) && count($itemsConfirmados) === 0) {
            $rechazar = true;
        }

        if ($rechazar) {
            DB::transaction(function () use ($entrega, $user, $validated) {
                $notas = $validated['notas_recibido'] ?? 'Entrega devuelta/rechazada.';
                $entrega->update([
                    'estado' => 'rechazado',
                    'recibido_por' => $user->id,
                    'fecha_recibido' => now(),
                    'notas_recibido' => $notas,
                ]);
                if (in_array($entrega->tipo_origen, ['lote', 'declaracion_mi_corte'], true)) {
                    foreach ($entrega->children as $child) {
                        $child->update([
                            'estado' => 'rechazado',
                            'recibido_por' => $user->id,
                            'fecha_recibido' => now(),
                            'notas_recibido' => $notas,
                            'parent_id' => null,
                        ]);
                    }
                }
            });

            return $this->success([
                'entrega_id' => $entrega->id,
                'estado' => 'rechazado',
            ], 'La entrega ha sido devuelta. Las ventas quedan pendientes para declararse nuevamente.');
        }

        $registrarDeposito = (bool) ($validated['registrar_deposito'] ?? false);
        $cuentaId = isset($validated['cuenta_bancaria_id']) ? (int) $validated['cuenta_bancaria_id'] : null;
        if ($registrarDeposito && ! $cuentaId) {
            return response()->json([
                'success' => false,
                'message' => 'Si deseas registrar depósito en banco, indica cuenta_bancaria_id.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($entrega, $user, $cuentaId, $validated, $registrarDeposito, $itemsConfirmados) {
                if (is_array($itemsConfirmados) && in_array($entrega->tipo_origen, ['lote', 'declaracion_mi_corte'], true)) {
                    $rechazados = $entrega->children()->whereNotIn('id', $itemsConfirmados)->get();
                    $notasRechazo = $validated['notas_recibido'] ?? 'Rechazado en confirmación parcial por falta de efectivo.';
                    foreach ($rechazados as $rechazado) {
                        $rechazado->update([
                            'estado' => 'rechazado',
                            'recibido_por' => $user->id,
                            'fecha_recibido' => now(),
                            'notas_recibido' => $notasRechazo,
                            'parent_id' => null,
                        ]);
                    }

                    $nuevoTotal = $entrega->children()->whereIn('id', $itemsConfirmados)->sum('total');
                    $entrega->update([
                        'total' => $nuevoTotal,
                        'monto_efectivo' => $nuevoTotal,
                    ]);
                }

                EntregaDineroService::marcarComoRecibido(
                    $entrega,
                    (int) $user->id,
                    $cuentaId,
                    $validated['notas_recibido'] ?? null,
                    $registrarDeposito && $cuentaId > 0
                );
            });

            $entrega->refresh();

            return $this->success([
                'entrega_id' => $entrega->id,
                'estado' => $entrega->estado,
                'recibido_por' => $entrega->recibido_por,
                'recibido_por_nombre' => User::query()->whereKey($entrega->recibido_por)->value('name'),
                'fecha_recibido' => $entrega->fecha_recibido?->toIso8601String(),
            ], 'Recepción confirmada con éxito.');
        } catch (\Throwable $e) {
            return $this->serverError('No se pudo confirmar la recepción', $e);
        }
    }

    /**
     * Entrega masiva de ventas/cobranzas (Lote)
     */
    public function entregarLote(Request $request)
    {
        $user = $request->user();
        if (! $user || (! $user->hasAnyRole(['admin', 'ventas', 'super-admin', 'tecnico']) && ! $user->can('declarar entrega mi corte'))) {
             return response()->json(['success' => false, 'message' => 'No tienes permisos para crear lotes de entrega'], 403);
        }

        Log::info('Entregar Lote Request:', ['user_id' => $user->id, 'payload' => $request->all()]);

        try {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.tipo_origen' => 'required|string|in:venta,cobranza,gasto',
                'items.*.id_origen' => 'required|integer',
                'items.*.total' => 'required|numeric',
                'items.*.metodo_pago' => 'nullable|string',
                'notas' => 'nullable|string|max:500',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation Failed for entregarLote:', $e->errors());
            throw $e;
        }

        try {
            DB::beginTransaction();

            $totalLote = array_sum(array_column($validated['items'], 'total'));
            $empresaId = $user->empresa_id;
            if (!$empresaId) {
                throw new \RuntimeException('No se pudo determinar la empresa para registrar la entrega.');
            }

            // 1. Crear el registro Padre (Lote)
            $lote = EntregaDinero::create([
                'user_id' => $user->id,
                'fecha_entrega' => now(),
                'monto_efectivo' => $totalLote, // Asumimos efectivo para el lote principal
                'total' => $totalLote,
                'notas' => 'Lote (Carrito): ' . ($validated['notas'] ?? ''),
                'estado' => 'pendiente',
                'tipo_origen' => 'lote',
                'empresa_id' => $empresaId,
            ]);

            // 2. Crear los registros hijos vinculados al padre
            foreach ($validated['items'] as $item) {
                // Validar que no exista ya una entrega pendiente para este item
                $existe = EntregaDinero::where('tipo_origen', $item['tipo_origen'])
                    ->where('id_origen', $item['id_origen'])
                    ->whereIn('estado', ['pendiente', 'recibido'])
                    ->exists();

                if ($existe) {
                    throw new \Exception("El registro {$item['tipo_origen']} #{$item['id_origen']} ya tiene una entrega asociada.");
                }

                EntregaDinero::create([
                    'user_id' => $user->id,
                    'parent_id' => $lote->id,
                    'fecha_entrega' => now(),
                    'monto_efectivo' => ($item['metodo_pago'] === 'efectivo') ? $item['total'] : 0,
                    'monto_transferencia' => ($item['metodo_pago'] === 'transferencia') ? $item['total'] : 0,
                    'monto_tarjetas' => (str_contains($item['metodo_pago'] ?? '', 'tarjeta')) ? $item['total'] : 0,
                    'total' => $item['total'],
                    'notas' => $validated['notas'] ?? null,
                    'estado' => 'pendiente',
                    'tipo_origen' => $item['tipo_origen'],
                    'id_origen' => $item['id_origen'],
                    'empresa_id' => $empresaId,
                ]);
            }

            DB::commit();

            return $this->success([
                'id' => $lote->id,
                'total' => $lote->total,
                'items_count' => count($validated['items']),
                'message' => 'Lote generado correctamente',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), 422);
        }
    }
}
