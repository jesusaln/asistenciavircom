<?php

namespace App\Console\Commands;

use App\Helpers\EnvironmentHelper;
use App\Mail\OrdenCompraEnviada;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\Producto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:test-email-order {--email= : Email destino para la prueba} {--orden= : ID de orden de compra existente}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar envío de correos de orden de compra';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Prevenir ejecución en producción
        EnvironmentHelper::preventDevelopmentAction('el comando de prueba de email');

        $email = $this->option('email') ?: config('mail.test_email', 'test@example.com');
        $ordenId = $this->option('orden');

        $this->info('🧪 Probando envío de correo de orden de compra');
        $this->line("📧 Email destino: {$email}");

        if ($ordenId) {
            // Usar una orden existente
            $orden = OrdenCompra::with(['proveedor', 'productos'])->find($ordenId);

            if (!$orden) {
                $this->error("❌ No se encontró la orden con ID: {$ordenId}");
                return 1;
            }

            $this->line("📋 Usando orden existente: #{$orden->numero_orden} (ID: {$orden->id})");
        } else {
            // Crear una orden de prueba en la base de datos
            $this->line('📋 Creando orden de prueba en la base de datos...');

            $proveedor = Proveedor::first();
            if (!$proveedor) {
                $this->error('❌ No hay proveedores en la base de datos. Crea al menos uno primero.');
                return 1;
            }

            $producto = Producto::first();
            if (!$producto) {
                $this->error('❌ No hay productos en la base de datos. Crea al menos uno primero.');
                return 1;
            }

            // Crear orden de prueba
            $orden = OrdenCompra::create([
                'numero_orden' => 'TEST-' . now()->format('His'),
                'fecha_orden' => now(),
                'fecha_entrega_esperada' => now()->addDays(7),
                'prioridad' => 'media',
                'proveedor_id' => $proveedor->id,
                'direccion_entrega' => 'Dirección de prueba',
                'terminos_pago' => '30_dias',
                'metodo_pago' => 'transferencia',
                'subtotal' => 1000.00,
                'descuento_items' => 0.00,
                'descuento_general' => 0.00,
                'iva' => 160.00,
                'total' => 1160.00,
                'observaciones' => 'Esta es una orden de prueba generada por el comando test:email',
                'estado' => 'enviado_a_proveedor',
            ]);

            // Agregar producto a la orden
            $orden->productos()->attach($producto->id, [
                'cantidad' => 2,
                'precio' => 500.00,
                'descuento' => 0,
                'unidad_medida' => $producto->unidad_medida ?? 'pieza'
            ]);

            // Recargar la orden con relaciones
            $orden->load(['proveedor', 'productos']);

            $this->line("📋 Orden de prueba creada: #{$orden->numero_orden} (ID: {$orden->id})");
        }

        try {
            $this->line('📤 Enviando correo...');

            Mail::to($email)->send(new OrdenCompraEnviada($orden));

            $this->info('✅ Correo enviado exitosamente!');
            $this->line("📧 Enviado a: {$email}");
            $this->line("📋 Orden: #{$orden->numero_orden}");

            if (config('mail.test_mode')) {
                $this->warn('⚠️  Modo de prueba activado - el correo fue enviado al email de prueba');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error al enviar el correo:');
            $this->line($e->getMessage());

            $this->line("\n🔧 Verifica tu configuración de correo en .env:");
            $this->line("   MAIL_MAILER: " . config('mail.default'));
            $this->line("   MAIL_HOST: " . config('mail.mailers.smtp.host'));
            $this->line("   MAIL_PORT: " . config('mail.mailers.smtp.port'));
            $this->line("   MAIL_ENCRYPTION: " . config('mail.mailers.smtp.encryption'));

            return 1;
        }
    }
}
