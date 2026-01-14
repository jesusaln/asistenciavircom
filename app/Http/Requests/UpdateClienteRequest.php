<?php

namespace App\Http\Requests;

use App\Models\Cliente;
use App\Models\SatRegimenFiscal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use App\Support\EmpresaResolver;
use App\Http\Requests\Traits\HasClienteValidation;

class UpdateClienteRequest extends FormRequest
{
    use HasClienteValidation;
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeClienteData();
    }

    public function rules(): array
    {
        $clienteId = $this->input('cliente_id');

        return $this->getCommonRules(
            $this->boolean('requiere_factura'),
            $clienteId
        );
    }

    public function messages(): array
    {
        return [
            'nombre_razon_social.required' => 'El nombre o razón social es obligatorio.',
            'nombre_razon_social.regex' => 'El nombre/razón social contiene caracteres no válidos.',

            'tipo_persona.required' => 'El tipo de persona es obligatorio.',
            'tipo_persona.in' => 'El tipo de persona debe ser física o moral.',

            'rfc.required' => 'El RFC es obligatorio.',
            'rfc.regex' => 'El RFC no tiene un formato válido.',
            'rfc.rfc_unique' => 'El RFC ya está registrado.',

            'regimen_fiscal.required' => 'El régimen fiscal es obligatorio.',
            'regimen_fiscal.exists' => 'El régimen fiscal no existe en el catálogo.',
            'regimen_fiscal.regimen_valid' => 'El régimen fiscal no es válido para el tipo de persona seleccionado.',

            'uso_cfdi.required' => 'El uso de CFDI es obligatorio.',
            'uso_cfdi.exists' => 'El uso de CFDI seleccionado no es válido.',

            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe tener un formato válido.',
            'email.unique' => 'El email ya está registrado.',

            'telefono.regex' => 'El teléfono solo debe contener números, espacios, paréntesis, guiones y el signo +.',

            'calle.required' => 'La calle es obligatoria cuando se agrega información de dirección.',
            'numero_exterior.required' => 'El número exterior es obligatorio cuando se agrega información de dirección.',
            'colonia.required' => 'La colonia es obligatoria cuando se agrega información de dirección.',

            'codigo_postal.required' => 'El código postal es obligatorio cuando se agrega información de dirección.',
            'codigo_postal.size' => 'El código postal debe tener 5 dígitos.',
            'codigo_postal.regex' => 'El código postal debe contener solo números.',

            'municipio.required' => 'El municipio es obligatorio cuando se agrega información de dirección.',

            'estado.min' => 'El estado debe tener al menos 2 caracteres.',

            'notas.max' => 'Las notas no pueden exceder 1000 caracteres.',
            'curp.regex' => 'El CURP no tiene un formato válido.',
        ];
    }


}
