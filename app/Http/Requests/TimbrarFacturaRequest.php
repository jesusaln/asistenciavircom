<?php
// app/Http/Requests/TimbrarFacturaRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimbrarFacturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id'        => 'required|exists:clientes,id',
            'use'               => 'nullable|size:3',            // G01/G03 etc
            'payment_form'      => 'nullable|size:2',            // 01,03,28...

            'items'             => 'required|array|min:1',
            'items.*.cantidad'  => 'required|numeric|min:0.0001',
            'items.*.descripcion' => 'required|string|max:1000',
            'items.*.sat_clave_prodserv' => 'required|string|size:8',
            'items.*.sat_clave_unidad'   => 'nullable|string|max:5',
            'items.*.precio'    => 'required|numeric|min:0',
            'items.*.precio_con_iva' => 'nullable|boolean',
            'items.*.iva_tasa'  => 'nullable|numeric|min:0|max:1',
        ];
    }
}
