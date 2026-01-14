<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CVAService;

class CVAProbarPedido extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cva:test-pedido 
                            {clave : Clave del producto CVA (ej. HD-3235)}
                            {cantidad=1 : Cantidad a pedir}
                            {--test : Forzar modo de prueba explícito}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea un pedido de prueba en CVA para verificar integración';

    /**
     * Execute the console command.
     */
    public function handle(CVAService $cvaService)
    {
        $clave = $this->argument('clave');
        $cantidad = (int) $this->argument('cantidad');
        $isTest = true; // Siempre true para este comando de seguridad

        $this->info("Iniciando prueba de pedido para: {$clave} (x{$cantidad})");

        // 1. Verificar producto primero
        $this->comment('Verificando detalles del producto...');
        $producto = $cvaService->getProductDetails($clave);

        if (!$producto) {
            $this->error("No se encontró el producto con clave {$clave} o hubo error de conexión.");
            return 1;
        }

        $this->info("Producto encontrado: {$producto['descripcion']}");
        $this->info("Precio: {$producto['precio']} {$producto['moneda']}");
        $this->info("Stock GDL: " . ($producto['inventario'][0]['disponible'] ?? 'N/A'));

        if (!$this->confirm('¿Deseas intentar crear un PEDIDO DE PRUEBA (test=1) con este producto?', true)) {
            return 0;
        }

        // 2. Armar payload de prueba
        $orderData = [
            'test' => 1, // MODO PRUEBA
            'num_oc' => 'TEST-' . time(),
            'observaciones' => 'Pedido de prueba generado desde CLI Laravel',
            // Usamos defaults de config para sucursal, o 1 si falla
            'productos' => [
                [
                    'clave' => $clave,
                    'cantidad' => $cantidad
                ]
            ]
        ];

        // 3. Enviar pedido
        $this->comment('Enviando solicitud de pedido...');
        $resultado = $cvaService->createOrder($orderData);

        if ($resultado['success']) {
            $this->info('¡Pedido de prueba creado con éxito!');
            $this->table(['Campo', 'Valor'], [
                ['Pedido ID', $resultado['data']['pedido'] ?? 'N/A'],
                ['Total', $resultado['data']['total'] ?? 'N/A'],
                ['Moneda', $resultado['data']['moneda'] ?? 'N/A'],
                ['Email Agente', $resultado['data']['email_agente'] ?? 'N/A'],
            ]);
        } else {
            $this->error('Error al crear el pedido:');
            $this->error($resultado['error']);
            if (isset($resultado['details'])) {
                $this->line(json_encode($resultado['details'], JSON_PRETTY_PRINT));
            }
        }

        return 0;
    }
}
