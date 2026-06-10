<?php

/**
 * Traducciones personalizadas para el sistema
 * Resuelve Error #88: Falta de Esquema de Traducción
 */

return [
    // Mensajes de validación personalizados
    'required' => 'El campo :attribute es obligatorio.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
    ],
    'max' => [
        'string' => 'El campo :attribute no puede tener más de :max caracteres.',
        'numeric' => 'El campo :attribute no puede ser mayor a :max.',
    ],
    'unique' => 'El campo :attribute ya ha sido registrado.',
    'exists' => 'El campo :attribute no existe en el sistema.',
    'numeric' => 'El campo :attribute debe ser un número.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'date' => 'El campo :attribute debe ser una fecha válida.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',

    // Atributos personalizados
    'attributes' => [
        'nombre' => 'nombre',
        'nombre_razon_social' => 'razón social',
        'email' => 'correo electrónico',
        'telefono' => 'teléfono',
        'rfc' => 'RFC',
        'codigo_postal' => 'código postal',
        'calle' => 'calle',
        'numero_exterior' => 'número exterior',
        'numero_interior' => 'número interior',
        'colonia' => 'colonia',
        'ciudad' => 'ciudad',
        'estado' => 'estado',
        'pais' => 'país',
        'precio' => 'precio',
        'cantidad' => 'cantidad',
        'total' => 'total',
        'iva' => 'IVA',
        'subtotal' => 'subtotal',
        'descuento' => 'descuento',
        'empresa_id' => 'empresa',
        'cliente_id' => 'cliente',
        'producto_id' => 'producto',
        'almacen_id' => 'almacén',
        'vendedor_id' => 'vendedor',
    ],

    // Mensajes de éxito
    'created' => ':attribute creado exitosamente.',
    'updated' => ':attribute actualizado exitosamente.',
    'deleted' => ':attribute eliminado exitosamente.',
    'saved' => ':attribute guardado correctamente.',

    // Mensajes de error
    'error' => 'Ha ocurrido un error. Por favor, intenta de nuevo.',
    'not_found' => ':attribute no encontrado.',
    'unauthorized' => 'No tienes permiso para realizar esta acción.',
    'validation_failed' => 'Los datos proporcionados no son válidos.',
];
