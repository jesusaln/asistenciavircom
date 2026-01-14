<?php

namespace App\Http\Requests\Traits;

use App\Models\Cliente;
use App\Models\SatRegimenFiscal;
use App\Support\EmpresaResolver;
use Illuminate\Validation\Rule;

trait HasClienteValidation
{
    /**
     * Normaliza los datos de entrada comunes para clientes.
     */
    protected function normalizeClienteData(): void
    {
        $toUpper = fn($v) => is_string($v) ? mb_strtoupper(trim($v), 'UTF-8') : $v;

        // Obtener ID del cliente si es update (route parameter)
        // Esto asume que el request usa 'cliente' como parámetro de ruta para edit
        $routeCliente = $this->route('cliente');
        $clienteId = is_object($routeCliente) ? ($routeCliente->id ?? null)
            : (is_numeric($routeCliente) ? (int) $routeCliente : null);

        $dataToMerge = [];
        if ($clienteId) {
            $dataToMerge['cliente_id'] = $clienteId;
        }

        // Campos a normalizar a mayúsculas
        $fieldsToUpper = [
            'nombre_razon_social',
            'rfc',
            'regimen_fiscal',
            'uso_cfdi',
            'calle',
            'numero_exterior',
            'numero_interior',
            'colonia',
            'municipio',
            'estado',
            'pais',
            'curp'
        ];

        foreach ($fieldsToUpper as $field) {
            if ($this->has($field)) {
                $val = $this->input($field);
                if (is_string($val)) {
                    $dataToMerge[$field] = $val ? $toUpper($val) : $val;
                } elseif (!array_key_exists($field, $dataToMerge)) {
                    $dataToMerge[$field] = $val;
                }
            }
        }

        // Soportar select con objeto/array (p. ej. { value, label }) para regimen fiscal
        if ($this->has('regimen_fiscal')) {
            $regimen = $this->input('regimen_fiscal');
            if (is_object($regimen)) {
                $regimen = (array) $regimen;
            }
            if (is_array($regimen) && array_is_list($regimen) && count($regimen) > 0) {
                $regimen = $regimen[0];
            }
            if (is_object($regimen)) {
                $regimen = (array) $regimen;
            }
            if (is_array($regimen)) {
                if (array_key_exists('value', $regimen)) {
                    $regimen = $regimen['value'];
                } elseif (array_key_exists('clave', $regimen)) {
                    $regimen = $regimen['clave'];
                } elseif (count($regimen) === 1) {
                    $regimen = reset($regimen);
                }
            }
            if (is_string($regimen)) {
                $regimen = trim($regimen);
                // Si llega con formato "601 - ..." o "601 — ...", extraer clave
                if (preg_match('/^([0-9]{3})\\s*[-—]/', $regimen, $match)) {
                    $regimen = $match[1];
                } elseif (strlen($regimen) > 3) {
                    // Si llega como descripción, buscar clave por descripción exacta
                    $clave = SatRegimenFiscal::whereRaw('LOWER(descripcion) = ?', [mb_strtolower($regimen, 'UTF-8')])
                        ->value('clave');
                    if ($clave) {
                        $regimen = $clave;
                    }
                }
            } else {
                $regimen = null;
            }
            $dataToMerge['regimen_fiscal'] = $regimen;
        }

        // Casos especiales
        if ($this->has('tipo_persona')) {
            $dataToMerge['tipo_persona'] = $this->input('tipo_persona') ? strtolower(trim($this->input('tipo_persona'))) : null;
        }

        if ($this->has('codigo_postal')) {
            $cp = $this->input('codigo_postal');
            $dataToMerge['codigo_postal'] = $cp ? preg_replace('/\D/', '', $cp) : null;
        }

        // Normalizar crédito para evitar NULL en columnas NOT NULL
        if ($this->has('credito_activo')) {
            $dataToMerge['credito_activo'] = $this->boolean('credito_activo');
        }
        if ($this->has('limite_credito') || $this->has('credito_activo')) {
            $limite = $this->input('limite_credito');
            $dataToMerge['limite_credito'] = (is_null($limite) || $limite === '')
                ? 0
                : (float) $limite;
        }
        if ($this->has('dias_credito')) {
            $dias = $this->input('dias_credito');
            $dataToMerge['dias_credito'] = (is_null($dias) || $dias === '')
                ? 0
                : (int) $dias;
        }

        // Default País MX
        if ($this->has('pais') || !$this->has('pais') && $this->method() === 'POST') {
            // Si viene vacío o no viene en POST, default MX
            $paisVal = $dataToMerge['pais'] ?? $this->input('pais');
            if (empty($paisVal)) {
                $dataToMerge['pais'] = 'MX';
            }
        }

        $this->merge($dataToMerge);
    }

    protected function getCommonRules(bool $requiereFactura, ?int $clienteId): array
    {
        $mostrarDireccion = $this->input('mostrar_direccion', false);

        $rules = [
            'nombre_razon_social' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚüÜ\s\.,&\-\']+$/',
            ],
            'notas' => ['nullable', 'string', 'max:1000'],
            'activo' => ['boolean'],

            // Dirección
            'calle' => [$mostrarDireccion ? 'required' : 'nullable', 'string', 'max:255'],
            'numero_exterior' => [$mostrarDireccion ? 'required' : 'nullable', 'string', 'max:20'],
            'numero_interior' => ['nullable', 'string', 'max:20'],
            'colonia' => [$mostrarDireccion ? 'required' : 'nullable', 'string', 'max:255'],
            'codigo_postal' => [$mostrarDireccion ? 'required' : 'nullable', 'string', 'size:5', 'regex:/^[0-9]{5}$/'],
            'municipio' => [$mostrarDireccion ? 'required' : 'nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'min:2', 'max:255'],
            'pais' => ['nullable', 'string', 'max:255'],

            // Extras
            'tipo_identificacion' => ['nullable', 'string', 'max:20'],
            'identificacion' => ['nullable', 'string', 'max:50'],
            'curp' => ['nullable', 'string', 'size:18', 'regex:/^[A-Z][AEIOU][A-Z]{2}[0-9]{6}[HM][A-Z]{5}[A-Z0-9][0-9]$/'],

            'cfdi_default_use' => ['nullable', 'string', 'size:3', Rule::exists('sat_usos_cfdi', 'clave')],
            'payment_form_default' => ['nullable', 'string', 'size:2'],
            'price_list_id' => ['nullable', 'integer', Rule::exists('price_lists', 'id')->where('activa', true)],

            'credito_activo' => ['nullable', 'boolean'],
            'limite_credito' => ['nullable', 'numeric', 'min:0'],
            'dias_credito' => ['nullable', 'integer', 'min:0', 'max:365'],

            'requiere_factura' => ['nullable', 'boolean'],
            'whatsapp_optin' => ['nullable', 'boolean'],
            'domicilio_fiscal_cp' => ['nullable', 'string', 'size:5', 'regex:/^[0-9]{5}$/'],
            'forma_pago_default' => ['nullable', 'string', 'max:2'],

            // Password solo si se envía
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        if ($requiereFactura) {
            $rules = array_merge($rules, [
                'tipo_persona' => ['required', Rule::in(['fisica', 'moral'])],
                'rfc' => array_merge($this->getRfcValidationRules($clienteId), [
                    'required',
                    'string',
                    'max:13',
                    'regex:/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/',
                ]),
                'regimen_fiscal' => array_merge($this->getRegimenFiscalValidationRules(), [
                    'required',
                    'string',
                    'size:3',
                    Rule::exists('sat_regimenes_fiscales', 'clave'),
                ]),
                'uso_cfdi' => ['required', 'string', 'size:3', Rule::exists('sat_usos_cfdi', 'clave')],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('clientes', 'email')
                        ->ignore($clienteId)
                        ->whereNull('deleted_at')
                        ->where('empresa_id', EmpresaResolver::resolveId()),
                ],
                'telefono' => [
                    'required',
                    'string',
                    'size:10',
                    'regex:/^[0-9]{10}$/',
                ],
                'domicilio_fiscal_cp' => ['required', 'string', 'size:5', 'regex:/^[0-9]{5}$/'],
            ]);
        } else {
            $rules = array_merge($rules, [
                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                    Rule::unique('clientes', 'email')
                        ->ignore($clienteId)
                        ->whereNotNull('email')
                        ->whereNull('deleted_at')
                        ->where('empresa_id', EmpresaResolver::resolveId()),
                ],
                'telefono' => [
                    'nullable',
                    'string',
                    'max:20',
                    'regex:/^[0-9+\-\s()]+$/',
                ],
            ]);
        }

        return $rules;
    }

    protected function getRfcValidationRules(?int $clienteId): array
    {
        return [
            function ($attribute, $value, $fail) use ($clienteId) {
                $value = strtoupper(trim($value));
                // Validar unicidad manual compleja
                $exists = Cliente::where('rfc', $value)
                    ->when($clienteId, fn($q) => $q->where('id', '!=', $clienteId))
                    ->whereNull('deleted_at')
                    ->where('empresa_id', EmpresaResolver::resolveId())
                    ->exists();

                if ($exists) {
                    if ($value === 'XAXX010101000' || $value === 'XEXX010101000') {
                        // ALLOW duplicate generic RFCs as per user request
                        return;
                    } else {
                        $fail('El RFC ya está registrado.');
                    }
                }
            },
        ];
    }

    protected function getRegimenFiscalValidationRules(): array
    {
        return [
            function ($attribute, $value, $fail) {
                $tipo = $this->input('tipo_persona');
                $rf = SatRegimenFiscal::query()
                    ->where('clave', strtoupper(trim($value)))
                    ->first();

                if (!$rf)
                    return;

                if ($tipo === 'fisica' && !$rf->persona_fisica) {
                    $fail('El régimen fiscal no es válido para Persona Física.');
                }
                if ($tipo === 'moral' && !$rf->persona_moral) {
                    $fail('El régimen fiscal no es válido para Persona Moral.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_razon_social.required' => 'El nombre o razón social es obligatorio.',
            'nombre_razon_social.regex' => 'El nombre/razón social contiene caracteres no válidos.',
            'tipo_persona.required' => 'El tipo de persona es obligatorio.',
            'rfc.required' => 'El RFC es obligatorio.',
            'rfc.regex' => 'El RFC no tiene un formato válido.',
            'regimen_fiscal.required' => 'El régimen fiscal es obligatorio.',
            'email.required' => 'El email es obligatorio.',
            'email.unique' => 'El email ya está registrado.',
            // ... agregar el resto si es necesario, Laravel tiene defaults buenos, pero mantenemos compatibilidad
            'codigo_postal.size' => 'El código postal debe tener 5 dígitos.',
        ];
    }
}
