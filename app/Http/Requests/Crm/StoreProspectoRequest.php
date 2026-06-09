<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\CrmProspecto;

class StoreProspectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Datos básicos
            'nombre' => 'required|string|max:255',
            'telefono' => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'email' => 'nullable|email|max:255',
            'empresa' => 'nullable|string|max:255',
            'origen' => 'required|in:' . implode(',', array_keys(CrmProspecto::ORIGENES)),
            'vendedor_id' => 'nullable|exists:users,id',
            'prioridad' => 'nullable|in:alta,media,baja',
            'valor_estimado' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:2000',
            'cliente_id' => 'nullable|exists:clientes,id',
            // Lista de precios
            'price_list_id' => 'nullable|exists:price_lists,id',
            // Campos fiscales
            'requiere_factura' => 'boolean',
            'tipo_persona' => 'nullable|in:fisica,moral',
            'rfc' => ['nullable', 'string', 'max:13', 'regex:/^([A-ZÑ&]{3,4})\d{6}([A-Z\d]{3})$/i'],
            'curp' => ['nullable', 'string', 'max:18', 'regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z\d]{2}$/i'],
            'regimen_fiscal' => 'nullable|string|max:10',
            'uso_cfdi' => 'nullable|string|max:10',
            'domicilio_fiscal_cp' => ['nullable', 'string', 'regex:/^[0-9]{5}$/'],
            // Dirección
            'calle' => 'nullable|string|max:255',
            'numero_exterior' => 'nullable|string|max:50',
            'numero_interior' => 'nullable|string|max:50',
            'codigo_postal' => ['nullable', 'string', 'regex:/^[0-9]{5}$/'],
            'colonia' => 'nullable|string|max:255',
            'municipio' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:3',
            'pais' => 'nullable|string|max:3',
        ];
    }

    public function messages(): array
    {
        return [
            'telefono.regex' => 'El teléfono debe tener exactamente 10 dígitos',
            'rfc.regex' => 'El RFC no tiene un formato válido',
            'curp.regex' => 'La CURP no tiene un formato válido',
            'codigo_postal.regex' => 'El código postal debe tener exactamente 5 dígitos',
            'domicilio_fiscal_cp.regex' => 'El código postal fiscal debe tener exactamente 5 dígitos',
        ];
    }
}
