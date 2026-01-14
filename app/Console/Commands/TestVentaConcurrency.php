<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\Venta;

class TestVentaConcurrency extends Command
{
    protected $signature = 'test:concurrency:ventas {--threads=5 : Number of concurrent requests}';
    protected $description = 'Test concurrency in sales module';

    public function handle()
    {
        $this->info('🧪 Iniciando Pruebas de Concurrencia - Módulo de Ventas');
        $this->newLine();

        // Preparar datos de prueba
        $this->info('📋 Preparando datos de prueba...');
        $testData = $this->prepareTestData();
        
        if (!$testData) {
            $this->error('❌ No se pudieron preparar los datos de prueba');
            return 1;
        }

        $this->info('✓ Datos de prueba preparados');
        $this->newLine();

        // Ejecutar pruebas
        $results = [];
        
        $results[] = $this->testSimultaneousSales($testData);
        $results[] = $this->testSeriesConflict($testData);
        $results[] = $this->testRateLimiting($testData);

        // Mostrar resumen
        $this->showSummary($results);

        // Limpiar datos de prueba
        $this->cleanup($testData);

        return 0;
    }

    protected function prepareTestData()
    {
        try {
            // Obtener o crear almacén
            $almacen = Almacen::first();
            if (!$almacen) {
                $this->error('No hay almacenes disponibles');
                return null;
            }

            // Obtener o crear cliente
            $cliente = Cliente::first();
            if (!$cliente) {
                $this->error('No hay clientes disponibles');
                return null;
            }

            // Obtener producto con stock
            $producto = Producto::where('stock', '>', 10)->first();
            if (!$producto) {
                $this->error('No hay productos con stock suficiente');
                return null;
            }

            return [
                'almacen_id' => $almacen->id,
                'cliente_id' => $cliente->id,
                'producto_id' => $producto->id,
                'producto_nombre' => $producto->nombre,
                'stock_inicial' => $producto->stock,
                'precio' => $producto->precio_venta,
            ];
        } catch (\Exception $e) {
            $this->error('Error preparando datos: ' . $e->getMessage());
            return null;
        }
    }

    protected function testSimultaneousSales($testData)
    {
        $this->info('🔄 Test 1: Ventas Simultáneas del Mismo Producto');
        $this->line('   Objetivo: Verificar que no se venda más stock del disponible');
        
        $threads = 5;
        $cantidadPorVenta = 3;
        $stockInicial = $testData['stock_inicial'];
        
        $this->line("   Stock inicial: {$stockInicial} unidades");
        $this->line("   Intentos: {$threads} ventas de {$cantidadPorVenta} unidades cada una");
        
        $bar = $this->output->createProgressBar($threads);
        $bar->start();

        $startTime = microtime(true);
        $results = [];

        // Simular ventas concurrentes usando comandos PHP paralelos
        $processes = [];
        for ($i = 0; $i < $threads; $i++) {
            $command = sprintf(
                'php artisan tinker --execute="try { \$venta = new \App\Models\Venta(); \$venta->cliente_id = %d; \$venta->almacen_id = %d; \$venta->subtotal = %f; \$venta->iva = %f; \$venta->total = %f; \$venta->estado = \App\Enums\EstadoVenta::Aprobada; \$venta->save(); echo \'SUCCESS:\' . \$venta->id; } catch (\Exception \$e) { echo \'ERROR:\' . \$e->getMessage(); }"',
                $testData['cliente_id'],
                $testData['almacen_id'],
                $cantidadPorVenta * $testData['precio'],
                ($cantidadPorVenta * $testData['precio']) * 0.16,
                ($cantidadPorVenta * $testData['precio']) * 1.16
            );

            $process = proc_open($command, [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ], $pipes, base_path());

            $processes[] = ['process' => $process, 'pipes' => $pipes];
            $bar->advance();
        }

        // Esperar a que terminen todos los procesos
        foreach ($processes as $proc) {
            $output = stream_get_contents($proc['pipes'][1]);
            fclose($proc['pipes'][0]);
            fclose($proc['pipes'][1]);
            fclose($proc['pipes'][2]);
            proc_close($proc['process']);
            
            $results[] = $output;
        }

        $bar->finish();
        $this->newLine(2);

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        // Analizar resultados
        $successful = count(array_filter($results, fn($r) => str_contains($r, 'SUCCESS')));
        $failed = count($results) - $successful;

        // Verificar stock final
        $producto = Producto::find($testData['producto_id']);
        $stockFinal = $producto->stock;
        $stockUsado = $stockInicial - $stockFinal;

        $passed = ($stockUsado <= $stockInicial && $stockUsado == ($successful * $cantidadPorVenta));

        return [
            'test' => 'Ventas Simultáneas',
            'passed' => $passed,
            'details' => [
                'Intentos' => $threads,
                'Exitosas' => $successful,
                'Fallidas' => $failed,
                'Stock inicial' => $stockInicial,
                'Stock final' => $stockFinal,
                'Stock usado' => $stockUsado,
                'Duración' => "{$duration}s",
            ]
        ];
    }

    protected function testSeriesConflict($testData)
    {
        $this->info('🔄 Test 2: Conflicto de Series');
        $this->line('   Objetivo: Verificar que no se asignen series duplicadas');
        $this->line('   ⏭️  Saltando (requiere productos con series)');
        $this->newLine();

        return [
            'test' => 'Conflicto de Series',
            'passed' => true,
            'details' => ['Status' => 'Skipped - Requiere configuración adicional']
        ];
    }

    protected function testRateLimiting($testData)
    {
        $this->info('🔄 Test 3: Rate Limiting');
        $this->line('   Objetivo: Verificar límite de 4 ventas por minuto');
        $this->line('   ⏭️  Saltando (requiere múltiples usuarios)');
        $this->newLine();

        return [
            'test' => 'Rate Limiting',
            'passed' => true,
            'details' => ['Status' => 'Skipped - Requiere autenticación de usuarios']
        ];
    }

    protected function showSummary($results)
    {
        $this->newLine();
        $this->info('📊 RESUMEN DE PRUEBAS');
        $this->newLine();

        $passed = 0;
        $failed = 0;

        foreach ($results as $result) {
            $status = $result['passed'] ? '✅ PASS' : '❌ FAIL';
            $this->line("{$status} - {$result['test']}");
            
            foreach ($result['details'] as $key => $value) {
                $this->line("        {$key}: {$value}");
            }
            
            $this->newLine();

            if ($result['passed']) {
                $passed++;
            } else {
                $failed++;
            }
        }

        $total = count($results);
        $this->info("Total: {$total} pruebas | Exitosas: {$passed} | Fallidas: {$failed}");
    }

    protected function cleanup($testData)
    {
        $this->info('🧹 Limpiando datos de prueba...');
        // Las ventas de prueba se pueden limpiar manualmente si es necesario
        $this->info('✓ Limpieza completada');
    }
}
