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
    protected $signature = 'app:bot-manager {action} {--query=} {--name=} {--phone=} {--date=} {--desc=} {--tipo=} {--calle=} {--colonia=} {--cp=}';
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
        $tipo = $this->option('tipo') ?? 'Soporte Técnico';
        $calle = $this->option('calle');
        $colonia = $this->option('colonia');
        $cp = $this->option('cp');

        if (!$name || !$phone || !$dateStr) {
            $this->error("Faltan datos críticos: --name, --phone y --date son obligatorios.");
            return;
        }

        try {
            $fechaHora = Carbon::parse($dateStr);
        } catch (\Exception $e) {
            $this->error("Formato de fecha inválido. Use AAAA-MM-DD HH:MM (Ej: 2026-02-15 14:30)");
            return;
        }

        // 1. ANALIZAR SI ESTÁ OCUPADA LA FECHA (Validación de conflicto)
        // Buscamos citas que no estén canceladas y que caigan en el mismo bloque horario (rango de 1 hora)
        $conflicto = Cita::where('estado', '!=', Cita::ESTADO_CANCELADO)
            ->where(function ($query) use ($fechaHora) {
                $query->whereBetween('fecha_hora', [
                    $fechaHora->copy()->subMinutes(59),
                    $fechaHora->copy()->addMinutes(59)
                ]);
            })->first();

        if ($conflicto) {
            $horaConflicto = $conflicto->fecha_hora->format('H:i');
            $this->error("LO SENTIMOS: Ya existe una cita programada cerca de esa hora ({$horaConflicto}). Por favor, elija otro horario.");
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
            'fecha_hora' => $fechaHora,
            'tipo_servicio' => $tipo,
            'descripcion' => $desc ?? "Cita agendada por Vircom Bot - {$tipo}",
            'problema_reportado' => $desc,
            'direccion_calle' => $calle,
            'direccion_colonia' => $colonia,
            'direccion_cp' => $cp,
            'estado' => Cita::ESTADO_PENDIENTE,
            'prioridad' => Cita::PRIORIDAD_MEDIA
        ]);

        $this->info("¡CITA AGENDADA CON ÉXITO! Folio: {$cita->folio}");
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
            $saldo = number_format((float) $cliente->saldo_pendiente, 2);
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
            $precio = number_format((float) $producto->precio_publico, 2);
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
            $vence = $pago->fecha_vencimiento ? $pago->fecha_vencimiento->format('d/m/Y') : 'N/A';
            $monto = number_format((float) $pago->monto_pendiente, 2);
            $this->line("- {$cliente}: \${$monto} (Vence: {$vence})");
        }
    }
}
