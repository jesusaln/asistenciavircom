<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\CuentasPorCobrar;
use Carbon\Carbon;

class BotManager extends Command
{
    protected $signature = 'app:bot-manager {action} {--query=} {--name=} {--phone=} {--date=} {--desc=}';
    protected $description = 'Interfaz para que el Bot de WhatsApp interactúe con el sistema AsistenciaVircom';

    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'citas-hoy':
                $this->citasHoy();
                break;
            case 'citas-agendar':
                $this->citasAgendar();
                break;
            case 'clientes-buscar':
                $this->clientesBuscar();
                break;
            case 'productos-buscar':
                $this->productosBuscar();
                break;
            case 'pagos-pendientes':
                $this->pagosPendientes();
                break;
            default:
                $this->error("Acción no reconocida: {$action}");
        }
    }

    private function citasHoy()
    {
        $citas = Cita::hoy()->with('cliente')->get();
        if ($citas->isEmpty()) {
            $this->info("No hay citas programadas para hoy.");
            return;
        }

        $this->info("Citas de Hoy (" . Carbon::today()->format('d/m/Y') . "):");
        foreach ($citas as $cita) {
            $hora = $cita->fecha_hora->format('H:i');
            $cliente = $cita->cliente->nombre_razon_social ?? 'Sin nombre';
            $estado = $cita->estado;
            $this->line("- [{$hora}] {$cliente} ({$estado}): {$cita->descripcion}");
        }
    }

    private function citasAgendar()
    {
        $name = $this->option('name');
        $phone = $this->option('phone');
        $dateStr = $this->option('date'); // Y-m-d H:i
        $desc = $this->option('desc');

        if (!$name || !$phone || !$dateStr) {
            $this->error("Faltan datos: --name, --phone y --date son obligatorios.");
            return;
        }

        // Buscar o crear cliente
        $cliente = Cliente::where('telefono', 'like', "%{$phone}%")->first();
        if (!$cliente) {
            $cliente = Cliente::create([
                'nombre_razon_social' => $name,
                'telefono' => $phone,
                'empresa_id' => 1,
                'activo' => true
            ]);
        }

        $cita = Cita::create([
            'empresa_id' => 1,
            'cliente_id' => $cliente->id,
            'fecha_hora' => Carbon::parse($dateStr),
            'descripcion' => $desc ?? 'Cita agendada por Bot WhatsApp',
            'estado' => Cita::ESTADO_PENDIENTE,
            'prioridad' => Cita::PRIORIDAD_MEDIA
        ]);

        $this->info("Cita agendada con éxito. Folio: {$cita->folio}");
    }

    private function clientesBuscar()
    {
        $query = $this->option('query');
        $clientes = Cliente::buscar($query)->limit(5)->get();

        if ($clientes->isEmpty()) {
            $this->info("No se encontraron clientes con '{$query}'.");
            return;
        }

        foreach ($clientes as $cliente) {
            $saldo = number_format($cliente->saldo_pendiente, 2);
            $this->line("- ID: {$cliente->id} | {$cliente->nombre_razon_social} | Tel: {$cliente->telefono} | Saldo: \${$saldo}");
        }
    }

    private function productosBuscar()
    {
        $query = $this->option('query');
        $productos = Producto::where('nombre', 'like', "%{$query}%")
            ->orWhere('sku', 'like', "%{$query}%")
            ->limit(5)->get();

        if ($productos->isEmpty()) {
            $this->info("No se encontraron productos con '{$query}'.");
            return;
        }

        foreach ($productos as $producto) {
            $precio = number_format($producto->precio_publico, 2);
            $stock = $producto->stock_total ?? 0;
            $this->line("- SKU: {$producto->sku} | {$producto->nombre} | Precio: \${$precio} | Stock: {$stock}");
        }
    }

    private function pagosPendientes()
    {
        $pagos = CuentasPorCobrar::whereIn('estado', ['pendiente', 'parcial', 'vencida'])
            ->with('venta.cliente')
            ->orderBy('fecha_vencimiento')
            ->limit(10)
            ->get();

        if ($pagos->isEmpty()) {
            $this->info("No hay pagos pendientes registrados.");
            return;
        }

        $this->info("Pagos Pendientes Prioritarios:");
        foreach ($pagos as $pago) {
            $cliente = $pago->venta->cliente->nombre_razon_social ?? 'Desconocido';
            $vence = $pago->fecha_vencimiento->format('d/m/Y');
            $monto = number_format($pago->monto_pendiente, 2);
            $this->line("- {$cliente}: \${$monto} (Vence: {$vence})");
        }
    }
}
