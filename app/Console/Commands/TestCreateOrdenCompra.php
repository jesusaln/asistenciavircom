<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Almacen;

class TestCreateOrdenCompra extends Command
{
    protected $signature = 'ordencompra:test-create
        {--producto_id=8}
        {--proveedor_id=1}
        {--almacen_id=2}
        {--precio=905.17}
        {--cantidad=1}
        {--descuento=0}
        {--prioridad=media}
        ';

    protected $description = 'Crea una orden de compra de prueba y adjunta un producto para verificar pivote y secuencias';

    public function handle(): int
    {
        $productoId = (int) $this->option('producto_id');
        $proveedorId = (int) $this->option('proveedor_id');
        $almacenId = (int) $this->option('almacen_id');
        $precio = (float) $this->option('precio');
        $cantidad = (int) $this->option('cantidad');
        $descuento = (float) $this->option('descuento');
        $prioridad = (string) $this->option('prioridad');

        $producto = Producto::find($productoId);
        if (!$producto) {
            $this->error("Producto $productoId no encontrado");
            return self::FAILURE;
        }

        $proveedor = Proveedor::find($proveedorId);
        if (!$proveedor) {
            $this->error("Proveedor $proveedorId no encontrado");
            return self::FAILURE;
        }

        $almacen = Almacen::find($almacenId);
        if (!$almacen) {
            $this->error("Almacén $almacenId no encontrado");
            return self::FAILURE;
        }

        $subtotal = round($precio * $cantidad, 2);
        $iva = round($subtotal * 0.16, 2);
        $total = round($subtotal + $iva - $descuento, 2);

        DB::beginTransaction();
        try {
            $orden = OrdenCompra::create([
                'proveedor_id' => $proveedor->id,
                'almacen_id' => $almacen->id,
                // 'numero_orden' => null => lo genera el modelo
                'fecha_orden' => now(),
                'fecha_entrega_esperada' => now(),
                'prioridad' => $prioridad,
                'terminos_pago' => '30_dias',
                'metodo_pago' => 'transferencia',
                'subtotal' => $subtotal,
                'descuento_items' => 0,
                'descuento_general' => 0,
                'iva' => $iva,
                'total' => $total,
                'observaciones' => null,
                'estado' => 'pendiente',
            ]);

            $orden->productos()->attach($producto->id, [
                'cantidad' => $cantidad,
                'precio' => $precio,
                'descuento' => $descuento,
                'unidad_medida' => $producto->unidad_medida ?? null,
            ]);

            DB::commit();
            $this->info('Orden de compra creada');
            $this->line('ID: ' . $orden->id);
            $this->line('Número: ' . $orden->numero_orden);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

