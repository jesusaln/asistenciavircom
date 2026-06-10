<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mantenimiento;
use App\Http\Requests\StoreMantenimientoRequest;
use App\Models\Carro;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MantenimientoController extends Controller
{
    /**
     * Almacenar nuevo mantenimiento desde la API
     */
    public function store(\Illuminate\Http\Request $request): JsonResponse
    {
        Log::debug('Mantenimiento API Incoming Request:', $request->all());
        
        $formRequest = new \App\Http\Requests\StoreMantenimientoRequest();
        $validator = \Illuminate\Support\Facades\Validator::make(
            $request->all(), 
            $formRequest->rules(),
            $formRequest->messages()
        );

        if ($validator->fails()) {
            Log::warning('Error de validación detallado:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validated = $this->normalizeMantenimientoPayload($validator->validated());
            $validated['estado'] = $validated['estado'] ?? Mantenimiento::ESTADO_COMPLETADO;

            $mantenimiento = Mantenimiento::create($validated);
            
            // Sincronizar kilometraje si es necesario
            $this->syncCarroKilometrajeIfNeeded($mantenimiento);

            Log::info('Mantenimiento API creado exitosamente', [
                'id' => $mantenimiento->id,
                'carro_id' => $mantenimiento->carro_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento registrado exitosamente',
                'data' => $mantenimiento
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Error de validación en Mantenimiento API:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error API creando mantenimiento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el mantenimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Normalizar datos para asegurar tipos correctos
     */
    private function normalizeMantenimientoPayload(array $data): array
    {
        if (isset($data['fecha'])) {
            $data['fecha'] = Carbon::parse($data['fecha'])->format('Y-m-d');
        }
        if (isset($data['proximo_mantenimiento'])) {
            $data['proximo_mantenimiento'] = Carbon::parse($data['proximo_mantenimiento'])->format('Y-m-d');
        }
        
        // Asegurar que valores numéricos sean correctos
        $data['costo'] = (float)($data['costo'] ?? 0);
        $data['kilometraje_actual'] = (int)($data['kilometraje_actual'] ?? 0);
        $data['dias_anticipacion_alerta'] = (int)($data['dias_anticipacion_alerta'] ?? 15);
        
        return $data;
    }

    /**
     * Sincronizar el kilometraje del vehículo si el mantenimiento es más reciente
     */
    private function syncCarroKilometrajeIfNeeded(Mantenimiento $mantenimiento): void
    {
        $carro = $mantenimiento->carro;
        if (!$carro) return;

        if ($mantenimiento->kilometraje_actual > $carro->kilometraje) {
            $carro->update([
                'kilometraje' => $mantenimiento->kilometraje_actual
            ]);
            Log::info('Kilometraje de vehículo actualizado tras mantenimiento', [
                'carro_id' => $carro->id,
                'nuevo_km' => $mantenimiento->kilometraje_actual
            ]);
        }
    }
}
