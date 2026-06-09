<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Margen Mínimo de Venta
    |--------------------------------------------------------------------------
    |
    | Porcentaje mínimo de margen permitido en ventas.
    | Los usuarios con rol 'admin' pueden hacer override de esta validación.
    |
    */
    'margen_minimo' => env('VENTAS_MARGEN_MINIMO', 10),

    /*
    |--------------------------------------------------------------------------
    | Validar Margen en Ventas
    |--------------------------------------------------------------------------
    |
    | Si está en true, se validará el margen mínimo en cada venta.
    | Si está en false, solo se mostrará una advertencia.
    |
    */
    'validar_margen' => env('VENTAS_VALIDAR_MARGEN', true),

    /*
    |--------------------------------------------------------------------------
    | Roles que Pueden Hacer Override de Margen
    |--------------------------------------------------------------------------
    |
    | Roles que pueden vender con margen menor al mínimo.
    |
    */
    'roles_override_margen' => ['admin', 'gerente', 'director'],

    /*
    |--------------------------------------------------------------------------
    | Numeración de Ventas por Almacén
    |--------------------------------------------------------------------------
    |
    | Si está en true, cada almacén tendrá su propia numeración (A1-V0001).
    | Si está en false, la numeración será global (V0001).
    |
    */
    'numeracion_por_almacen' => env('VENTAS_NUMERACION_POR_ALMACEN', false),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Número máximo de ventas que se pueden crear por minuto por usuario.
    |
    */
    'rate_limit_ventas_por_minuto' => env('VENTAS_RATE_LIMIT', 4),

    /*
    |--------------------------------------------------------------------------
    | Sincronización de Secuencias PostgreSQL
    |--------------------------------------------------------------------------
    |
    | Determina si se debe sincronizar la secuencia de IDs de PostgreSQL
    | antes de cada creación de venta. Esto solo es necesario después de
    | importaciones masivas. Deshabilitarlo mejora el performance.
    |
    */
    'sincronizar_secuencia_auto' => env('VENTAS_SINCRONIZAR_SECUENCIA', false),

    /*
    |--------------------------------------------------------------------------
    | Validación de Stock en Tiempo Real
    |--------------------------------------------------------------------------
    |
    | Habilita la validación de stock en tiempo real durante la creación
    | de ventas. Esto previene race conditions pero puede afectar performance
    | en sistemas con alta concurrencia.
    |
    */
    'validar_stock_tiempo_real' => env('VENTAS_VALIDAR_STOCK_REAL_TIME', true),

    /*
    |--------------------------------------------------------------------------
    | Generación Automática de Cuentas por Cobrar
    |--------------------------------------------------------------------------
    |
    | Determina si se debe crear automáticamente una cuenta por cobrar
    | al crear una venta. Esto es útil para el seguimiento de pagos.
    |
    */
    'crear_cuenta_por_cobrar_auto' => env('VENTAS_CREAR_CXC_AUTO', true),

    /*
    |--------------------------------------------------------------------------
    | Validación de Almacén Activo
    |--------------------------------------------------------------------------
    |
    | Valida que el almacén esté activo antes de permitir ventas.
    | Esto previene ventas desde almacenes cerrados o en mantenimiento.
    |
    */
    'validar_almacen_activo' => env('VENTAS_VALIDAR_ALMACEN_ACTIVO', true),

    /*
    |--------------------------------------------------------------------------
    | Validación de Producto Activo
    |--------------------------------------------------------------------------
    |
    | Valida que los productos estén activos antes de permitir su venta.
    | Esto previene ventas de productos descontinuados.
    |
    */
    'validar_producto_activo' => env('VENTAS_VALIDAR_PRODUCTO_ACTIVO', true),

    /*
    |--------------------------------------------------------------------------
    | Configuración de PDF
    |--------------------------------------------------------------------------
    |
    | Configuración para la generación de PDFs de ventas.
    |
    */
    'pdf' => [
        'validar_datos_completos' => env('VENTAS_PDF_VALIDAR_DATOS', true),
        'validar_total_minimo' => env('VENTAS_PDF_VALIDAR_TOTAL', true),
        'validar_cliente' => env('VENTAS_PDF_VALIDAR_CLIENTE', true),
        'validar_items' => env('VENTAS_PDF_VALIDAR_ITEMS', true),
        'validar_almacen' => env('VENTAS_PDF_VALIDAR_ALMACEN', true),
        'incluir_logo' => env('VENTAS_PDF_INCLUIR_LOGO', true),
        'incluir_series' => env('VENTAS_PDF_INCLUIR_SERIES', true),
        'formato_papel' => env('VENTAS_PDF_FORMATO', 'letter'), // letter, a4, legal
        'orientacion' => env('VENTAS_PDF_ORIENTACION', 'portrait'), // portrait, landscape
    ],

    /*
    |--------------------------------------------------------------------------
    | Auditoría de Ventas
    |--------------------------------------------------------------------------
    |
    | Configuración del sistema de auditoría de ventas.
    |
    */
    'auditoria' => [
        'habilitada' => env('VENTAS_AUDITORIA_HABILITADA', true),
        'registrar_intentos_fallidos' => env('VENTAS_AUDITORIA_FALLOS', true),
        'registrar_cambios_estado' => env('VENTAS_AUDITORIA_ESTADOS', true),
        'registrar_cambios_pago' => env('VENTAS_AUDITORIA_PAGOS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Series
    |--------------------------------------------------------------------------
    |
    | Configuración para el manejo de números de serie en productos.
    |
    */
    'series' => [
        'validar_unicidad' => env('VENTAS_SERIES_VALIDAR_UNICIDAD', true),
        'validar_almacen' => env('VENTAS_SERIES_VALIDAR_ALMACEN', true),
        'permitir_venta_sin_series' => env('VENTAS_SERIES_PERMITIR_SIN', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Método de Cálculo de Costo Histórico
    |--------------------------------------------------------------------------
    |
    | Método para calcular el costo histórico de productos:
    | - 'fifo': First In, First Out (recomendado)
    | - 'lifo': Last In, First Out
    | - 'promedio': Costo promedio ponderado
    |
    */
    'metodo_costo_historico' => env('VENTAS_METODO_COSTO', 'fifo'),
];

