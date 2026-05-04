<?php

namespace App\Console\Commands;

use App\Models\AlertaStock;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Almacen;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RevisarAlertasStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventario:revisar-alertas {--resolver-antiguas : Resolver alertas antiguas que ya no aplican}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revisa el stock de todos los productos y genera alertas automáticas cuando el stock está bajo, crítico o agotado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando revisión de alertas de stock...');

        $resolverAntiguas = $this->option('resolver-antiguas');

        $inventariosQuery = Inventario::query()
            ->with(['producto:id,nombre,codigo', 'almacen:id,nombre'])
            ->select('id', 'producto_id', 'almacen_id', 'cantidad', 'stock_minimo')
            ->where('cantidad', '>', 0);
        $inventariosCount = (clone $inventariosQuery)->count();

        $this->info("📊 Revisando {$inventariosCount} registros de inventario...");

        $alertasGeneradas = 0;
        $alertasResueltas = 0;

        $inventariosQuery->orderBy('id')->chunkById(200, function ($inventarios) use (&$alertasGeneradas, &$alertasResueltas, $resolverAntiguas) {
            foreach ($inventarios as $inventario) {
                $producto = $inventario->producto;
                $almacen = $inventario->almacen;
                $stockActual = $inventario->cantidad;
                $stockMinimo = $inventario->stock_minimo ?? 0;

                // Determinar tipo de alerta
                $tipoAlerta = $this->determinarTipoAlerta($stockActual, $stockMinimo);

                if ($tipoAlerta) {
                    // Verificar si ya existe una alerta activa para este producto/almacén
                    $alertaExistente = AlertaStock::where('producto_id', $producto->id)
                        ->where('almacen_id', $almacen->id)
                        ->where('leida', false)
                        ->first();

                    if (!$alertaExistente) {
                        // Crear nueva alerta
                        AlertaStock::create([
                            'producto_id' => $producto->id,
                            'almacen_id' => $almacen->id,
                            'tipo' => $tipoAlerta,
                            'stock_actual' => $stockActual,
                            'stock_minimo' => $stockMinimo,
                            'mensaje' => $this->generarMensajeAlerta($tipoAlerta, $producto, $almacen, $stockActual, $stockMinimo),
                        ]);

                        $alertasGeneradas++;
                        $this->line("⚠️  Alerta generada: {$producto->nombre} en {$almacen->nombre} - {$tipoAlerta}");
                    } else {
                        // Actualizar alerta existente si cambió el tipo
                        if ($alertaExistente->tipo !== $tipoAlerta) {
                            $alertaExistente->update([
                                'tipo' => $tipoAlerta,
                                'stock_actual' => $stockActual,
                                'mensaje' => $this->generarMensajeAlerta($tipoAlerta, $producto, $almacen, $stockActual, $stockMinimo),
                            ]);

                            $this->line("🔄 Alerta actualizada: {$producto->nombre} en {$almacen->nombre} - {$tipoAlerta}");
                        }
                    }
                } else {
                    // Si no hay alerta pero existía una, marcarla como resuelta
                    if ($resolverAntiguas) {
                        $alertaAntigua = AlertaStock::where('producto_id', $producto->id)
                            ->where('almacen_id', $almacen->id)
                            ->where('leida', false)
                            ->first();

                        if ($alertaAntigua) {
                            $alertaAntigua->update([
                                'leida' => true,
                                'leida_at' => now(),
                            ]);

                            $alertasResueltas++;
                            $this->line("✅ Alerta resuelta: {$producto->nombre} en {$almacen->nombre}");
                        }
                    }
                }
            }
        });

        // Verificar productos agotados (stock = 0)
        $productosAgotadosQuery = Producto::query()
            ->select('id', 'nombre', 'codigo')
            ->whereDoesntHave('inventarios', function ($query) {
                $query->where('cantidad', '>', 0);
            });

        $almacenesActivos = Almacen::where('estado', 'activo')->select('id', 'nombre')->get();

        $productosAgotadosQuery->orderBy('id')->chunkById(200, function ($productosAgotados) use ($almacenesActivos, &$alertasGeneradas) {
            foreach ($productosAgotados as $producto) {
                // Crear alerta de agotamiento para almacenes activos
                foreach ($almacenesActivos as $almacen) {
                    $alertaExistente = AlertaStock::where('producto_id', $producto->id)
                        ->where('almacen_id', $almacen->id)
                        ->where('tipo', 'agotado')
                        ->where('leida', false)
                        ->first();

                    if (!$alertaExistente) {
                        AlertaStock::create([
                            'producto_id' => $producto->id,
                            'almacen_id' => $almacen->id,
                            'tipo' => 'agotado',
                            'stock_actual' => 0,
                            'stock_minimo' => 0,
                            'mensaje' => "Producto agotado: {$producto->nombre} (Código: {$producto->codigo}) en almacén {$almacen->nombre}",
                        ]);

                        $alertasGeneradas++;
                        $this->line("🚨 Alerta de agotamiento: {$producto->nombre} en {$almacen->nombre}");
                    }
                }
            }
        });

        // Estadísticas finales
        $totalAlertasActivas = AlertaStock::where('leida', false)->count();
        $alertasCriticas = AlertaStock::where('leida', false)->where('tipo', 'critico')->count();
        $alertasAgotadas = AlertaStock::where('leida', false)->where('tipo', 'agotado')->count();

        $this->info("✅ Revisión completada!");
        $this->info("📈 Alertas generadas: {$alertasGeneradas}");
        $this->info("🔧 Alertas resueltas: {$alertasResueltas}");
        $this->info("📊 Total alertas activas: {$totalAlertasActivas}");
        $this->info("🚨 Alertas críticas: {$alertasCriticas}");
        $this->info("💀 Productos agotados: {$alertasAgotadas}");

        Log::info('Revisión de alertas de stock completada', [
            'alertas_generadas' => $alertasGeneradas,
            'alertas_resueltas' => $alertasResueltas,
            'total_activas' => $totalAlertasActivas,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Determina el tipo de alerta basado en el stock actual y mínimo
     */
    private function determinarTipoAlerta(int $stockActual, int $stockMinimo): ?string
    {
        if ($stockActual <= 0) {
            return 'agotado';
        }

        if ($stockMinimo > 0) {
            if ($stockActual <= $stockMinimo * 0.5) { // Menos del 50% del stock mínimo
                return 'critico';
            } elseif ($stockActual <= $stockMinimo) { // Igual o menor al stock mínimo
                return 'bajo';
            }
        }

        return null;
    }

    /**
     * Genera el mensaje descriptivo de la alerta
     */
    private function generarMensajeAlerta(string $tipo, Producto $producto, Almacen $almacen, int $stockActual, int $stockMinimo): string
    {
        $base = "Producto: {$producto->nombre} (Código: {$producto->codigo}) - Almacén: {$almacen->nombre}";

        switch ($tipo) {
            case 'bajo':
                return "{$base} - Stock bajo: {$stockActual} unidades (Mínimo: {$stockMinimo})";
            case 'critico':
                return "{$base} - Stock crítico: {$stockActual} unidades (Mínimo: {$stockMinimo})";
            case 'agotado':
                return "{$base} - Producto agotado";
            default:
                return $base;
        }
    }
}
