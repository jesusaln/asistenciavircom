<?php
// app/Http/Requests/StoreUpdateClienteRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\SatRegimenFiscal;
use App\Models\SatUsoCfdi;

class StoreUpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Puede venir {cliente} como modelo o como id
        $clienteId = $this->route('cliente')?->id ?? $this->route('cliente');

        return [
            'nombre_razon_social' => ['required', 'string', 'max:255'],
            'tipo_persona'        => ['required', Rule::in(['fisica', 'moral'])],

            // RFC con formato oficial + único (ignorando el actual al actualizar)
            'rfc' => [
                'required',
                'string',
                'max:13',
                'regex:/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/',
                Rule::unique('clientes', 'rfc')->ignore($clienteId),
            ],

            // Catálogo de régimen fiscal: existe y compatible con tipo de persona
            'regimen_fiscal' => [
                'required',
                'size:3',
                Rule::exists('sat_regimenes_fiscales', 'clave'),
                function ($attr, $val, $fail) {
                    $tipo = $this->input('tipo_persona');
                    $reg = SatRegimenFiscal::where('clave', $val)->first();
                    if (!$reg) {
                        $fail('El régimen fiscal no existe en el catálogo.');
                        return;
                    }
                    if ($tipo === 'fisica' && !$reg->persona_fisica) {
                        $fail('El régimen fiscal no es válido para persona física.');
                    }
                    if ($tipo === 'moral' && !$reg->persona_moral) {
                        $fail('El régimen fiscal no es válido para persona moral.');
                    }
                },
            ],

            // Uso CFDI: existe y compatible con tipo de persona
            'uso_cfdi' => [
                'required',
                'size:3',
                Rule::exists('sat_usos_cfdi', 'clave'),
                function ($attr, $val, $fail) {
                    $tipo = $this->input('tipo_persona');
                    $uso  = SatUsoCfdi::where('clave', $val)->first();
                    if (!$uso) {
                        $fail('El uso de CFDI no existe en el catálogo.');
                        return;
                    }
                    if ($tipo === 'fisica' && !$uso->persona_fisica) {
                        $fail('El uso de CFDI no es válido para persona física.');
                    }
                    if ($tipo === 'moral' && !$uso->persona_moral) {
                        $fail('El uso de CFDI no es válido para persona moral.');
                    }
                },
            ],

            // Contacto
            'email'    => ['required', 'email:rfc,dns', 'max:255', Rule::unique('clientes', 'email')->ignore($clienteId)],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],

            // Dirección
            'calle'            => ['required', 'string', 'max:255'],
            'numero_exterior'  => ['required', 'string', 'max:20'],
            'numero_interior'  => ['nullable', 'string', 'max:20'],
            'colonia'          => ['required', 'string', 'max:255'],
            'codigo_postal'    => ['required', 'digits:5'],
            'municipio'        => ['required', 'string', 'max:255'],
            'estado'           => ['required', 'size:3', Rule::exists('sat_estados', 'clave')], // p.ej. SON
            'pais'             => ['required', Rule::in(['MX'])],

            // Flags y notas
            'activo'           => ['sometimes', 'boolean'],
            'notas'            => ['nullable', 'string', 'max:1000'],
            'acepta_marketing' => ['sometimes', 'boolean'],

            // --- Defaults de CFDI ---
            // Si se manda, que sea una clave válida de uso (permite que elijas un default distinto al del cliente)
            'cfdi_default_use'      => ['nullable', 'size:3', Rule::exists('sat_usos_cfdi', 'clave')],
            // Formas de pago SAT son 2 dígitos (01, 02, 03, ...)
            // Si tienes catálogo en BD, cambia por Rule::exists('sat_formas_pago','clave')
            'payment_form_default'  => ['nullable', 'size:2', 'regex:/^[0-9]{2}$/'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $tipo = $this->input('tipo_persona');
            $rfc  = strtoupper((string) $this->input('rfc'));

            // Largo de RFC según tipo de persona
            if ($tipo === 'fisica' && strlen($rfc) !== 13) {
                $v->errors()->add('rfc', 'El RFC de persona física debe tener 13 caracteres.');
            }
            if ($tipo === 'moral' && strlen($rfc) !== 12) {
                $v->errors()->add('rfc', 'El RFC de persona moral debe tener 12 caracteres.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'rfc.regex'                 => 'El RFC no tiene un formato válido.',
            'email.email'               => 'El email debe tener un formato válido.',
            'email.unique'              => 'El email ya está registrado.',
            'telefono.regex'            => 'El teléfono solo puede contener números, espacios, paréntesis, guiones y +.',
            'codigo_postal.digits'      => 'El código postal debe tener 5 dígitos.',
            'estado.size'               => 'El estado debe ser una clave SAT de 3 letras (p. ej., SON).',
            'estado.exists'             => 'El estado no existe en el catálogo del SAT.',
            'regimen_fiscal.exists'     => 'El régimen fiscal no existe en el catálogo.',
            'uso_cfdi.exists'           => 'El uso de CFDI no existe en el catálogo.',
            'cfdi_default_use.exists'   => 'El uso de CFDI por defecto no es válido.',
            'payment_form_default.regex' => 'La forma de pago debe ser de 2 dígitos (p. ej., 03).',
            'pais.in'                   => 'El país debe ser MX.',
        ];
    }

    /**
     * Normaliza algunos campos antes de validar (opcional, útil para evitar falsos negativos).
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'rfc'   => strtoupper((string) $this->input('rfc')),
            'curp'  => $this->filled('curp') ? strtoupper((string) $this->input('curp')) : null,
            'email' => strtolower((string) $this->input('email')),
            'estado' => $this->filled('estado') ? strtoupper((string) $this->input('estado')) : null,
            'pais'  => $this->filled('pais') ? strtoupper((string) $this->input('pais')) : 'MX',
        ]);
    }
}
