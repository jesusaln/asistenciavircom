# Guía de Gestión de Pedidos CVA

## Comandos Disponibles

Se han creado comandos Artisan para facilitar la gestión y configuración de pedidos a CVA:

### 1. Consultar Información de Envío
Muestra las sucursales y paqueterías disponibles con sus claves ID correspondientes. Útil para configurar la sucursal de origen y paquetería predeterminada.

```bash
php artisan cva:info-envio
```

### 2. Probar Creación de Pedido
Permite simular la creación de un pedido (modo `test=1`) para verificar que la integración funcione, las credenciales sean correctas y el stock sea suficiente.

```bash
php artisan cva:test-pedido CLAVE_PRODUCTO CANTIDAD
# Ejemplo:
php artisan cva:test-pedido HD-3235 1
```

## Configuración

Se han agregado campos en `EmpresaConfiguracion` (tabla `empresa_configuracion`) para definir:

- `cva_codigo_sucursal`: ID de la sucursal de donde saldrán los pedidos por defecto (Default: 1 - Guadalajara).
- `cva_paqueteria_envio`: ID de la paquetería por defecto (Default: 4 - Paquetexpress).

Estos valores se usan automáticamente al crear una orden a menos que se especifiquen otros en el momento de la creación.

## Uso Programático (CVAService)

El servicio `App\Services\CVAService` expone los siguientes métodos para integración:

```php
// Crear Orden
$resultado = $cvaService->createOrder([
    'num_oc' => 'OC-12345',
    'observaciones' => 'Pedido automático',
    'productos' => [
        ['clave' => 'CLAVE1', 'cantidad' => 1]
    ],
    // 'test' => 1 // Para pruebas
]);

// Listar Pedidos
$pedidos = $cvaService->listOrders();

// Detalles de Pedido y Factura
$detalle = $cvaService->getOrderDetails('REFERENCIA_PEDIDO');
```
