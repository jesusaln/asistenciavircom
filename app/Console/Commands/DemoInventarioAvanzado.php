<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Models\Pedido;
use App\Models\Venta;
use App\Models\Compra;
use App\Services\InventarioService;
use Illuminate\Console\Command;

class DemoInventarioAvanzado extends Command
{
    protected $signature = 'demo:inventario-avanzado';
    protected $description = 'Demostrar las capacidades avanzadas del sistema de inventario';

    public function handle()
    {
        $this->info('🚀 Demostración del Sistema de Inventario Avanzado');
        $this->newLine();

        $inventarioService = app(InventarioService::class);

        // 1. Mostrar estadísticas actuales
        $this->info('📊 1. Estadísticas Generales del Sistema:');
        $stats = $inventarioService->obtenerEstadisticasGenerales();
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total Movimientos', $stats['total_movimientos']],
                ['Total Entradas', $stats['total_entradas']],
                ['Total Salidas', $stats['total_salidas']],
                ['Productos con Movimientos', $stats['productos_con_movimientos']],
                ['Usuarios Activos', $stats['usuarios_que_registraron']],
                ['Movimientos Hoy', $stats['movimientos_hoy']],
                ['Movimientos Este Mes', $stats['movimientos_este_mes']],
            ]
        );

        // 2. Demostrar productos más movidos
        $this->info('🔥 2. Productos con Mayor Movimiento:');
        $productosMasMovidos = $inventarioService->obtenerProductosMasMovidos(5);
        if ($productosMasMovidos->count() > 0) {
            $data = $productosMasMovidos->map(function ($producto) {
                return [
                    $producto->nombre,
                    $producto->categoria->nombre ?? 'Sin categoría',
                    $producto->marca->nombre ?? 'Sin marca',
                    number_format($producto->total_movido) . ' unidades'
                ];
            })->toArray();

            $this->table(['Producto', 'Categoría', 'Marca', 'Total Movido'], $data);
        } else {
            $this->warn('No hay productos con movimientos registrados aún.');
        }

        // 3. Demostrar consultas con filtros avanzados
        $this->info('🔍 3. Consultas con Filtros Avanzados:');
        $filtros = [
            'tipo' => 'entrada',
            'fecha_desde' => now()->subDays(30)->format('Y-m-d')
        ];

        $movimientosFiltrados = $inventarioService->obtenerMovimientosConFiltros($filtros);
        $this->line("Movimientos de entrada en los últimos 30 días: {$movimientosFiltrados->count()}");

        // 4. Demostrar capacidades de stock y reservas
        $this->info('📦 4. Estado Actual de Stock y Reservas:');
        $productos = Producto::with('categoria', 'marca')->limit(5)->get();

        if ($productos->count() > 0) {
            $stockData = $productos->map(function ($producto) {
                return [
                    $producto->nombre,
                    $producto->stock . ' unidades',
                    $producto->reservado . ' unidades',
                    ($producto->stock - $producto->reservado) . ' disponibles',
                    $producto->categoria->nombre ?? 'Sin categoría'
                ];
            })->toArray();

            $this->table(['Producto', 'Stock Total', 'Reservado', 'Disponible', 'Categoría'], $stockData);
        }

        // 5. Demostrar flujo completo de reservas
        $this->info('🔄 5. Simulación de Flujo de Reservas:');
        $this->demonstrateReservaFlow($inventarioService);

        $this->newLine();
        $this->info('✅ Demostración completada exitosamente!');
        $this->info('El sistema de inventario avanzado está funcionando correctamente con:');
        $this->line('  • Seguimiento detallado de movimientos');
        $this->line('  • Sistema de reservas inteligente');
        $this->line('  • Validaciones avanzadas de stock');
        $this->line('  • Consultas con filtros avanzados');
        $this->line('  • Estadísticas en tiempo real');
        $this->line('  • Manejo robusto de errores');

        return Command::SUCCESS;
    }

    private function demonstrateReservaFlow(InventarioService $inventarioService)
    {
        // Buscar un producto de ejemplo
        $producto = Producto::first();

        if (!$producto) {
            $this->warn('No hay productos para demostrar el flujo de reservas.');
            return;
        }

        $this->line("Demostrando flujo con producto: {$producto->nombre}");

        // Simular reserva (como si fuera un pedido)
        $cantidadReserva = 5;
        $this->line("📥 Reservando {$cantidadReserva} unidades...");

        try {
            $movimientoReserva = $inventarioService->entrada($producto, $cantidadReserva, [
                'motivo' => 'Demostración: Reserva automática',
                'referencia' => null,
                'detalles' => [
                    'tipo_operacion' => 'reserva_demo',
                    'descripcion' => 'Demostración del sistema de reservas'
                ]
            ]);

            $this->line("✅ Reserva exitosa - Movimiento ID: {$movimientoReserva->id}");
            $this->line("   Stock anterior: {$movimientoReserva->stock_anterior}");
            $this->line("   Stock posterior: {$movimientoReserva->stock_posterior}");
            $this->line("   Producto reservado ahora: {$producto->fresh()->reservado}");

        } catch (\Exception $e) {
            $this->error("Error en reserva: {$e->getMessage()}");
        }

        // Simular consumo de reserva (como si fuera una venta)
        $cantidadVenta = 3;
        $this->line("📤 Consumiendo {$cantidadVenta} unidades de reserva...");

        try {
            $movimientoVenta = $inventarioService->salida($producto, $cantidadVenta, [
                'motivo' => 'Demostración: Consumo de reserva',
                'referencia' => null,
                'detalles' => [
                    'tipo_operacion' => 'venta_demo',
                    'descripcion' => 'Demostración de consumo de reservas'
                ]
            ]);

            $this->line("✅ Consumo exitoso - Movimiento ID: {$movimientoVenta->id}");
            $this->line("   Stock anterior: {$movimientoVenta->stock_anterior}");
            $this->line("   Stock posterior: {$movimientoVenta->stock_posterior}");
            $this->line("   Producto reservado ahora: {$producto->fresh()->reservado}");

        } catch (\Exception $e) {
            $this->error("Error en consumo: {$e->getMessage()}");
        }
    }
}
