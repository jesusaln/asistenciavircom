<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use App\Http\Controllers\Controller;

class ProveedorController extends Controller
{
    /**
     * Muestra una lista de todos los proveedores en formato JSON.
     */
    public function index()
    {
        try {
            // Usar paginación para evitar sobrecarga de memoria en grandes datasets
            $proveedores = Proveedor::simplePaginate(50);
            return response()->json($proveedores);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener los proveedores'], 500);
        }
    }

    /**
     * Almacena un nuevo proveedor en la base de datos.
     */
    public function store(Request $request)
    {
        // Sanitización de datos (Mayúsculas y Trim)
        $fieldsToSanitize = ['nombre_razon_social', 'rfc', 'calle', 'colonia', 'municipio', 'estado', 'pais', 'regimen_fiscal', 'uso_cfdi'];
        $sanitizedData = [];
        foreach ($fieldsToSanitize as $field) {
            if ($request->has($field) && $request->filled($field)) {
                $sanitizedData[$field] = mb_strtoupper(trim($request->input($field)));
            }
        }
        $request->merge($sanitizedData);

        // Validar los datos recibidos
        $validated = $request->validate([
            'nombre_razon_social' => 'required|string|max:255',
            'rfc' => 'nullable|string|max:13',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:proveedores,email',
            'codigo_postal' => 'nullable|string|max:10',
            'municipio' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:255',
            'pais' => 'nullable|string|max:255',
        ]);

        try {
            // Crear el proveedor
            $proveedor = Proveedor::create($validated);

            // Devolver el proveedor creado como respuesta JSON
            return response()->json($proveedor, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al crear el proveedor'], 500);
        }
    }

    /**
     * Muestra un proveedor específico en formato JSON.
     */
    public function show($id)
    {
        try {
            $proveedor = Proveedor::findOrFail($id);
            return response()->json($proveedor);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Proveedor no encontrado'], 404);
        }
    }

    /**
     * Actualiza un proveedor existente en la base de datos.
     */
    public function update(Request $request, $id)
    {
        // Sanitización de datos (Mayúsculas y Trim)
        $fieldsToSanitize = ['nombre_razon_social', 'rfc', 'calle', 'colonia', 'municipio', 'estado', 'pais', 'regimen_fiscal', 'uso_cfdi'];
        $sanitizedData = [];
        foreach ($fieldsToSanitize as $field) {
            if ($request->has($field) && $request->filled($field)) {
                $sanitizedData[$field] = mb_strtoupper(trim($request->input($field)));
            }
        }
        $request->merge($sanitizedData);

        // Validar los datos recibidos
        $validated = $request->validate([
            'nombre_razon_social' => 'required|string|max:255',
            'tipo_persona' => 'required|string|in:fisica,moral',
            'rfc' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    $type = $request->input('tipo_persona'); // Ensure tipo_persona is sent in update if validating RFC logic depends on it
                    // NOTE: If tipo_persona is not passed in update, we might need to fetch it from DB.
                    // For API simplicity, assuming it's sent or we fetch it.
                    // Let's fetch from DB if not present to be safe.
                },
            ],
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:proveedores,email,' . $id,
            'codigo_postal' => 'nullable|string|max:10',
            'municipio' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:255',
            'pais' => 'nullable|string|max:255',
        ]);
        
        // Custom validation logic outside standard array to access DB if needed
        if ($request->has('rfc') && $request->input('rfc')) {
             $proveedor = Proveedor::findOrFail($id);
             $type = $request->input('tipo_persona', $proveedor->tipo_persona);
             $rfc = $request->input('rfc');
             $length = strlen($rfc);
             
             if ($type === 'fisica' && $length !== 13) {
                 return response()->json(['error' => 'El RFC de persona física debe tener exactamente 13 caracteres.'], 422);
             }
             if ($type === 'moral' && $length !== 12) {
                 return response()->json(['error' => 'El RFC de persona moral debe tener exactamente 12 caracteres.'], 422);
             }
        }

        try {
            $proveedor = Proveedor::findOrFail($id);
            $proveedor->update($validated);

            // Devolver el proveedor actualizado como respuesta JSON
            return response()->json($proveedor);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar el proveedor'], 500);
        }
    }

    /**
     * Elimina un proveedor de la base de datos.
     */
    public function destroy($id)
    {
        try {
            $proveedor = Proveedor::findOrFail($id);
            
            // Verificar dependencias antes de eliminar
            // INCLUYENDO soft deletes
            $comprasCount = $proveedor->compras()->withTrashed()->count();
            $ordenesCount = $proveedor->ordenesCompra()->count();
            $productosCount = $proveedor->productos()->count();
            
            if ($comprasCount > 0 || $ordenesCount > 0 || $productosCount > 0) {
                $deps = [];
                if ($comprasCount) $deps[] = "$comprasCount compra(s)";
                if ($ordenesCount) $deps[] = "$ordenesCount orden(es) de compra";
                if ($productosCount) $deps[] = "$productosCount producto(s)";
                
                return response()->json([
                    'error' => 'No se puede eliminar el proveedor. Tiene asociados: ' . implode(', ', $deps) . '.'
                ], 409);
            }

            $proveedor->delete();

            return response()->json(['message' => 'Proveedor eliminado correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar el proveedor'], 500);
        }
    }

    /**
     * Verifica si un correo electrónico ya existe.
     */
    public function checkEmail(Request $request)
    {
        $email = trim((string) $request->query('email', ''));
        $excludeId = $request->query('exclude_id');
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['exists' => false, 'valid' => false]);
        }
        
        $query = Proveedor::where('email', $email);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        $exists = $query->exists();
        return response()->json(['exists' => $exists, 'valid' => true]);
    }
}
