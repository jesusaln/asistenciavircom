<?php

namespace App\Http\Requests;

use App\Models\SatEstado;
use App\Models\SatRegimenFiscal;
use App\Models\SatUsoCfdi;
use App\Services\CfdiValidationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\EmpresaResolver;

class ClienteCfdiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Ajustar según permisos de usuario
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $clienteId = $this->route('cliente')?->id ?? null;

        return [
            // Datos básicos
            'nombre_razon_social' => ['required', 'string', 'max:255'],
            'tipo_persona' => ['nullable', Rule::in(['fisica', 'moral'])],
            'tipo_identificacion' => ['nullable', 'string', 'max:50'],
            'identificacion' => ['nullable', 'string', 'max:50'],
            'curp' => ['nullable', 'string', 'size:18', 'regex:/^[A-Z0-9]{18}$/'],

            // Datos fiscales obligatorios
            'rfc' => [
                'required',
                'string',
                // Permitir RFC genéricos XAXX010101000 y XEXX010101000, y formatos estándar PF/PM
                'regex:/^([A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}|XAXX010101000|XEXX010101000)$/',
                Rule::unique('clientes', 'rfc')
                    ->ignore($clienteId)
                    ->where('empresa_id', EmpresaResolver::resolveId()),
                function ($attribute, $value, $fail) {
                    $validationService = app(CfdiValidationService::class);
                    $errores = $validationService->validarRfc($value);
                    if (!empty($errores)) {
                        $fail(implode(', ', $errores));
                    }
                }
            ],

            // Régimen fiscal
            'regimen_fiscal' => [
                'required',
                'string',
                'size:3',
                Rule::exists('sat_regimenes_fiscales', 'clave'),
                function ($attribute, $value, $fail) {
                    $tipoPersona = $this->input('tipo_persona');
                    if ($tipoPersona) {
                        $regimen = SatRegimenFiscal::where('clave', $value)->first();
                        if ($regimen) {
                            $campoTipo = $tipoPersona === 'fisica' ? 'persona_fisica' : 'persona_moral';
                            if (!$regimen->$campoTipo) {
                                $tipoPersonaTexto = $tipoPersona === 'fisica' ? 'persona física' : 'persona moral';
                                $fail("El régimen fiscal {$value} no es válido para {$tipoPersonaTexto}");
                            }
                        }
                    }
                }
            ],

            // Uso CFDI
            'uso_cfdi' => [
                'nullable',
                'string',
                'size:3',
                Rule::exists('sat_usos_cfdi', 'clave')
            ],
            'cfdi_default_use' => [
                'nullable',
                'string',
                'size:3',
                Rule::exists('sat_usos_cfdi', 'clave')
            ],

            // Validación: Al menos uno de uso_cfdi o cfdi_default_use debe estar presente
            function ($attribute, $value, $fail) {
                if (empty($this->input('uso_cfdi')) && empty($this->input('cfdi_default_use'))) {
                    $fail('Debe especificar un uso CFDI');
                }
            },

            // Domicilio fiscal
            'domicilio_fiscal_cp' => [
                'required',
                'string',
                'regex:/^\d{5}$/',
                'digits:5'
            ],

            // Datos de contacto
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('clientes', 'email')
                    ->ignore($clienteId)
                    ->where('empresa_id', EmpresaResolver::resolveId())
            ],
            'telefono' => ['nullable', 'string', 'max:20'],

            // Dirección completa
            'calle' => ['nullable', 'string', 'max:255'],
            'numero_exterior' => ['nullable', 'string', 'max:50'],
            'numero_interior' => ['nullable', 'string', 'max:50'],
            'colonia' => ['nullable', 'string', 'max:255'],
            'codigo_postal' => ['nullable', 'string', 'regex:/^\d{5}$/', 'digits:5'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'estado' => [
                'nullable',
                'string',
                'size:3',
                Rule::exists('sat_estados', 'clave')
            ],
            'pais' => ['nullable', 'string', 'size:2', 'regex:/^[A-Z]{2,3}$/'],

            // Datos para extranjeros (condicionales)
            'residencia_fiscal' => [
                'nullable',
                'string',
                'size:3',
                'required_if:pais,!MX',
                'required_if:rfc,XEXX010101000'
            ],
            'num_reg_id_trib' => [
                'nullable',
                'string',
                'max:40',
                'required_if:pais,!MX',
                'required_if:rfc,XEXX010101000'
            ],

            // Método y forma de pago preferidos
            'payment_form_default' => [
                'nullable',
                'string',
                'size:2',
                'regex:/^\d{2}$/'
            ],

            // Otros datos
            'activo' => ['boolean'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nombre_razon_social.required' => 'El nombre o razón social es obligatorio',
            'rfc.required' => 'El RFC es obligatorio',
            'rfc.regex' => 'El formato del RFC es inválido',
            'rfc.unique' => 'Este RFC ya está registrado',
            'regimen_fiscal.required' => 'El régimen fiscal es obligatorio',
            'regimen_fiscal.exists' => 'El régimen fiscal seleccionado no es válido',
            'uso_cfdi.exists' => 'El uso CFDI seleccionado no es válido',
            'cfdi_default_use.exists' => 'El uso CFDI por defecto seleccionado no es válido',
            'domicilio_fiscal_cp.required' => 'El código postal del domicilio fiscal es obligatorio',
            'domicilio_fiscal_cp.regex' => 'El código postal debe tener 5 dígitos',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El formato del email es inválido',
            'email.unique' => 'Este email ya está registrado',
            'codigo_postal.regex' => 'El código postal debe tener 5 dígitos',
            'estado.exists' => 'El estado seleccionado no es válido',
            'pais.regex' => 'El código de país debe tener 2 o 3 letras mayúsculas',
            'residencia_fiscal.required_if' => 'La residencia fiscal es obligatoria para clientes extranjeros',
            'num_reg_id_trib.required_if' => 'El número de registro fiscal extranjero es obligatorio',
            'curp.regex' => 'El formato de la CURP es inválido',
            'curp.size' => 'La CURP debe tener 18 caracteres',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'nombre_razon_social' => 'nombre o razón social',
            'tipo_persona' => 'tipo de persona',
            'tipo_identificacion' => 'tipo de identificación',
            'identificacion' => 'identificación',
            'regimen_fiscal' => 'régimen fiscal',
            'uso_cfdi' => 'uso CFDI',
            'cfdi_default_use' => 'uso CFDI por defecto',
            'domicilio_fiscal_cp' => 'código postal fiscal',
            'codigo_postal' => 'código postal',
            'residencia_fiscal' => 'residencia fiscal',
            'num_reg_id_trib' => 'número de registro fiscal extranjero',
            'payment_form_default' => 'forma de pago por defecto',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $rfc = $this->input('rfc');
            $pais = $this->input('pais');

            // Reglas específicas para RFC genérico extranjero
            if ($rfc === 'XEXX010101000') {
                $regimen = $this->input('regimen_fiscal');
                $usoCfdi = $this->input('uso_cfdi') ?: $this->input('cfdi_default_use');

                // Forzar régimen 616
                if ($regimen !== '616') {
                    $validator->errors()->add('regimen_fiscal', 'RFC genérico extranjero debe usar régimen fiscal 616');
                }

                // Forzar uso CFDI S01
                if ($usoCfdi !== 'S01') {
                    $validator->errors()->add('uso_cfdi', 'RFC genérico extranjero debe usar uso CFDI S01');
                }
            } else {
                // Validación normal de compatibilidad régimen fiscal - uso CFDI
                $regimen = $this->input('regimen_fiscal');
                $usoCfdi = $this->input('uso_cfdi') ?: $this->input('cfdi_default_use');

                if ($regimen && $usoCfdi) {
                    $validationService = app(CfdiValidationService::class);
                    if (!$validationService->validarCompatibilidadRegimenUso($regimen, $usoCfdi)) {
                        $validator->errors()->add('uso_cfdi', "El uso CFDI {$usoCfdi} no es compatible con el régimen fiscal {$regimen}");
                    }
                }
            }

            // Para clientes extranjeros, validar que tengan residencia fiscal
            if ($pais && $pais !== 'MX') {
                $residenciaFiscal = $this->input('residencia_fiscal');
                if (empty($residenciaFiscal)) {
                    $validator->errors()->add('residencia_fiscal', 'La residencia fiscal es obligatoria para clientes extranjeros');
                }
            }
        });
    }
}
