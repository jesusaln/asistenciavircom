<?php

namespace App\Console\Commands;

use App\Models\Cita;
use App\Models\Empresa;
use App\Support\EmpresaResolver;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RecordatorioCitaWhatsApp extends Command
{
    protected $signature = 'citas:recordatorio-whatsapp';
    protected $description = 'Envía recordatorio por WhatsApp de citas programadas para mañana';

    public function handle()
    {
        $this->info('Enviando recordatorios de citas por WhatsApp...');

        $manana = Carbon::today()->addDay();
        $citas = Cita::with(['cliente', 'tecnico'])
            ->whereDate('fecha_hora', $manana)
            ->whereIn('estado', ['programado', 'pendiente', 'confirmado'])
            ->get();

        if ($citas->isEmpty()) {
            $this->info('No hay citas para mañana.');
            return 0;
        }

        $enviados = 0;
        $emailFallback = 0;

        foreach ($citas as $cita) {
            try {
                $cliente = $cita->cliente;
                if (!$cliente) continue;

                $empresa = Empresa::find($cita->empresa_id);
                EmpresaResolver::setContext($cita->empresa_id);
                $nombreEmpresa = $empresa->nombre_razon_social ?? 'Climas del Desierto';

                $fecha = $cita->fecha_hora->format('d/m/Y');
                $hora = $cita->fecha_hora->format('H:i');
                $servicio = $cita->tipo_servicio ?? 'Servicio';
                $tecnico = $cita->tecnico->name ?? 'Por asignar';
                $direccion = $cita->direccion_calle
                    ? "{$cita->direccion_calle}, Col. {$cita->direccion_colonia}, CP {$cita->direccion_cp}"
                    : 'Por definir';

                // Intentar WhatsApp primero
                $enviadoWa = false;
                if ($cliente->telefono) {
                    try {
                        $cleanPhone = preg_replace('/\D+/', '', $cliente->telefono);
                        if (strlen($cleanPhone) >= 10) {
                            $waId = \App\Services\WhatsAppService::canonicalWaId($cleanPhone);
                            $whatsapp = \App\Services\WhatsAppService::fromEmpresa($empresa);

                            $mensaje = "📅 *Recordatorio de Cita - {$nombreEmpresa}*\n\n" .
                                       "Hola *{$cliente->nombre_razon_social}*, te recordamos tu cita de mañana:\n\n" .
                                       "• *Servicio:* {$servicio}\n" .
                                       "• *Fecha:* {$fecha}\n" .
                                       "• *Hora:* {$hora}\n" .
                                       "• *Técnico:* {$tecnico}\n" .
                                       "• *Dirección:* {$direccion}\n" .
                                       "• *Folio:* {$cita->folio}\n\n" .
                                       "Si necesitas reagendar o cancelar, responde este mensaje.\n\n" .
                                       "¡Gracias por confiar en {$nombreEmpresa}! 🌵";

                            $whatsapp->sendTextMessage($waId, $mensaje);
                            Log::info("Recordatorio WhatsApp enviado a {$waId} para cita {$cita->id}");
                            $enviadoWa = true;
                            $enviados++;
                        }
                    } catch (\Exception $e) {
                        Log::warning("Error enviando recordatorio WhatsApp cita {$cita->id}: " . $e->getMessage());
                    }
                }

                // Fallback a email si no tiene WhatsApp o falló
                if (!$enviadoWa && $cliente->email) {
                    Mail::send([], [], function ($message) use ($cliente, $fecha, $hora, $servicio, $tecnico, $direccion, $nombreEmpresa, $cita) {
                        $message->to($cliente->email, $cliente->nombre_razon_social)
                            ->subject("📅 Recordatorio: Tienes una cita mañana - {$nombreEmpresa}")
                            ->html(
                                "<h2>¡Hola {$cliente->nombre_razon_social}!</h2>" .
                                "<p>Te recordamos que tienes una cita programada para <strong>mañana</strong>:</p>" .
                                "<ul>" .
                                "<li><strong>Servicio:</strong> {$servicio}</li>" .
                                "<li><strong>Fecha:</strong> {$fecha}</li>" .
                                "<li><strong>Hora:</strong> {$hora}</li>" .
                                "<li><strong>Técnico:</strong> {$tecnico}</li>" .
                                "<li><strong>Dirección:</strong> {$direccion}</li>" .
                                "<li><strong>Folio:</strong> {$cita->folio}</li>" .
                                "</ul>" .
                                "<p>Si necesitas reagendar o cancelar, responde a este correo.</p>" .
                                "<p>¡Gracias por confiar en {$nombreEmpresa}! 🌵</p>"
                            );
                    });
                    Log::info("Recordatorio email enviado a {$cliente->email} para cita {$cita->id} (fallback WhatsApp)");
                    $emailFallback++;
                }

            } catch (\Exception $e) {
                Log::error("Error en recordatorio cita {$cita->id}: " . $e->getMessage());
            }
        }

        $this->info("WhatsApp enviados: {$enviados}");
        $this->info("Email fallback: {$emailFallback}");
        $this->info("Total procesados: " . $citas->count());
        return 0;
    }
}
