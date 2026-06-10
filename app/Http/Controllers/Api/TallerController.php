<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TallerOrden;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TallerController extends Controller
{
    /**
     * Listado de órdenes de taller
     */
    public function index(Request $request)
    {
        $query = TallerOrden::with(['cliente', 'recepcionista', 'tecnico'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('folio', 'ILIKE', "%$s%")
                  ->orWhere('equipo_marca', 'ILIKE', "%$s%")
                  ->orWhere('equipo_modelo', 'ILIKE', "%$s%")
                  ->orWhere('nombre_cliente', 'ILIKE', "%$s%")
                  ->orWhereHas('cliente', function($cq) use ($s) {
                      $cq->where('nombre_razon_social', 'ILIKE', "%$s%");
                  });
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($this->getPerPage())
        ]);
    }

    /**
     * Mostrar una orden específica
     */
    public function show($id)
    {
        $orden = TallerOrden::with(['cliente', 'recepcionista', 'tecnico'])->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $orden
        ]);
    }

    /**
     * Crear nueva orden de taller (Recepción)
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'nullable|exists:clientes,id',
            'nombre_cliente' => 'required_if:cliente_id,null|string',
            'equipo_marca' => 'required|string',
            'equipo_modelo' => 'required|string',
            'problema_reportado' => 'required|string',
            'fecha_compromiso' => 'nullable|date',
            'firma_recepcion' => 'required|string', // Base64
        ]);

        try {
            DB::beginTransaction();

            $data = $request->all();
            $data['user_id'] = auth()->id();
            $data['empresa_id'] = auth()->user()->empresa_id ?? 1; // Fallback to 1 if not set
            $data['fecha_recepcion'] = now();
            $data['estado'] = 'recepcionado';

            if ($request->filled('firma_recepcion')) {
                $data['firma_recepcion'] = $this->saveSignature($request->firma_recepcion, 'recepcion');
            }

            $orden = TallerOrden::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Orden de taller creada correctamente',
                'data' => $orden
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la orden: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar diagnóstico/técnico
     */
    public function update(Request $request, $id)
    {
        $orden = TallerOrden::findOrFail($id);
        
        $request->validate([
            'estado' => 'sometimes|string|in:recepcionado,en_revision,reparando,listo,entregado,sin_reparacion,cancelado',
            'tecnico_id' => 'nullable|exists:users,id',
            'costo_final' => 'nullable|numeric',
            'fecha_compromiso' => 'nullable|date',
        ]);

        $orden->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Orden actualizada',
            'data' => $orden
        ]);
    }

    /**
     * Finalizar y Entregar orden
     */
    public function entregar(Request $request, $id)
    {
        $orden = TallerOrden::findOrFail($id);
        
        $request->validate([
            'trabajo_realizado' => 'required|string',
            'costo_final' => 'required|numeric',
            'firma_entrega' => 'required|string', // Base64
        ]);

        try {
            DB::beginTransaction();

            $orden->estado = 'entregado';
            $orden->fecha_entrega = now();
            $orden->trabajo_realizado = $request->trabajo_realizado;
            $orden->costo_final = $request->costo_final;

            if ($request->filled('firma_entrega')) {
                $orden->firma_entrega = $this->saveSignature($request->firma_entrega, 'entrega');
            }

            $orden->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Equipo entregado correctamente',
                'data' => $orden
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la entrega: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar firma desde Base64
     */
    private function saveSignature($base64Image, $prefix)
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                throw new \Exception('Formato de imagen inválido');
            }

            $base64Image = base64_decode($base64Image);

            if ($base64Image === false) {
                throw new \Exception('Decodificación fallida');
            }
        } else {
            throw new \Exception('Data URI inválido');
        }

        $fileName = $prefix . '_' . time() . '_' . Str::random(10) . '.' . $type;
        Storage::disk('public')->put('taller/firmas/' . $fileName, $base64Image);

        return $fileName;
    }
}
