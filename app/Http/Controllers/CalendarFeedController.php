<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\PolizaServicio;
use App\Models\EmpresaConfiguracion;
use App\Models\CuentasPorCobrar;
use App\Models\Cobranza;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CalendarFeedController extends Controller
{
    public function index(Request $request)
    {
        $token = $request->get('token');
        $config = EmpresaConfiguracion::withoutGlobalScopes()->first();

        if (!$config) {
            \Illuminate\Support\Facades\Log::error('CalendarFeed: No se encontró configuración de empresa.');
            return response('Error: Configuración no encontrada', 500);
        }

        if (!$token || $token !== $config->blog_robot_token) {
            \Illuminate\Support\Facades\Log::warning('CalendarFeed: Token inválido o no proporcionado. Recibido: ' . ($token ?? 'ninguno'));
            return response('No autorizado', 401);
        }

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//Asistencia Vircom//NONSGML v1.0//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "X-WR-CALNAME:Asistencia Vircom CRM\r\n";
        $ical .= "X-WR-TIMEZONE:America/Hermosillo\r\n";

        // 1. Citas de Servicio
        $citas = Cita::withoutGlobalScopes()->with('cliente')
            ->where('fecha_hora', '>=', now()->subMonths(3))
            ->where('estado', '!=', 'cancelado')
            ->get();

        foreach ($citas as $cita) {
            $start = Carbon::parse($cita->fecha_hora);
            $end = (clone $start)->addHours(2); // Estimación 2h si no hay duración

            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:cita-" . $cita->id . "@asistenciavircom.com\r\n";
            $ical .= "DTSTAMP:" . $this->formatDate($cita->created_at) . "\r\n";
            $ical .= "DTSTART:" . $this->formatDate($start) . "\r\n";
            $ical .= "DTEND:" . $this->formatDate($end) . "\r\n";
            $ical .= "SUMMARY:🔧 Cita: " . $cita->cliente->nombre_razon_social . "\r\n";
            $ical .= "DESCRIPTION:Folio: " . $cita->folio . "\\nMotivo: " . $cita->motivo . "\\nEstado: " . $cita->estado . "\r\n";
            $ical .= "LOCATION:" . ($cita->direccion_completa ?? 'Oficina') . "\r\n";
            $ical .= "END:VEVENT\r\n";
        }

        // 2. Vencimiento de Pólizas
        $polizas = PolizaServicio::withoutGlobalScopes()->with('cliente')
            ->where('fecha_fin', '>=', now()->subMonths(1))
            ->get();

        foreach ($polizas as $poliza) {
            $date = Carbon::parse($poliza->fecha_fin);

            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:poliza-" . $poliza->id . "@asistenciavircom.com\r\n";
            $ical .= "DTSTAMP:" . $this->formatDate($poliza->created_at) . "\r\n";
            $ical .= "DTSTART;VALUE=DATE:" . $date->format('Ymd') . "\r\n";
            $ical .= "SUMMARY:💰 VENCE PÓLIZA: " . $poliza->cliente->nombre_razon_social . "\r\n";
            $ical .= "DESCRIPTION:Póliza #" . $poliza->id . "\\nDías para vencer: " . $poliza->dias_para_vencer . "\r\n";
            $ical .= "TRANSP:TRANSPARENT\r\n";
            $ical .= "END:VEVENT\r\n";
        }

        // 3. Pagos de Renta (POS)
        $rentas = \App\Models\Renta::withoutGlobalScopes()->with('cliente')
            ->whereIn('estado', ['activo', 'moroso', 'vencido'])
            ->get();

        foreach ($rentas as $renta) {
            $diaPago = (int) ($renta->dia_pago ?? 1);

            // Generar recordatorios para los próximos 3 meses
            for ($m = 0; $m <= 3; $m++) {
                // Calculamos el mes objetivo
                $targetDate = now()->addMonths($m);

                // Ajustar al día de pago del cliente
                try {
                    // Si el mes no tiene suficientes días (ej. día 31 en febrero), usar el último día del mes
                    $daysInMonth = $targetDate->daysInMonth;
                    $actualDay = min($diaPago, $daysInMonth);
                    $paymentDate = $targetDate->copy()->setDay($actualDay);

                    // Solo mostrar si es hoy o en el futuro
                    if ($paymentDate->isAfter(now()->subDay())) {
                        $ical .= "BEGIN:VEVENT\r\n";
                        $ical .= "UID:renta-pago-" . $renta->id . "-" . $paymentDate->format('Ym') . "@asistenciavircom.com\r\n";
                        $ical .= "DTSTAMP:" . $this->formatDate($renta->created_at) . "\r\n";
                        $ical .= "DTSTART;VALUE=DATE:" . $paymentDate->format('Ymd') . "\r\n";

                        $prefix = $renta->estado === 'activo' ? '💳 PAGO RENTA' : '⚠️ COBRAR RENTA (' . strtoupper($renta->estado) . ')';

                        $ical .= "SUMMARY:" . $prefix . ": " . ($renta->cliente->nombre_razon_social ?? 'Cliente') . "\r\n";
                        $ical .= "DESCRIPTION:Contrato: " . $renta->numero_contrato . "\\nMonto: $" . number_format((float) ($renta->monto_mensual ?? 0), 2) . "\\nEstado actual: " . $renta->estado . "\r\n";
                        $ical .= "TRANSP:TRANSPARENT\r\n";
                        $ical .= "END:VEVENT\r\n";
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Vencimiento del contrato de renta
            if ($renta->fecha_fin) {
                $vencimiento = Carbon::parse($renta->fecha_fin);
                $ical .= "BEGIN:VEVENT\r\n";
                $ical .= "UID:renta-fin-" . $renta->id . "@asistenciavircom.com\r\n";
                $ical .= "DTSTAMP:" . $this->formatDate($renta->created_at) . "\r\n";
                $ical .= "DTSTART;VALUE=DATE:" . $vencimiento->format('Ymd') . "\r\n";
                $ical .= "SUMMARY:🚩 TERMINA CONTRATO RENTA: " . ($renta->cliente->nombre_razon_social ?? 'Cliente') . "\r\n";
                $ical .= "DESCRIPTION:Contrato: " . $renta->numero_contrato . "\r\n";
                $ical .= "TRANSP:TRANSPARENT\r\n";
                $ical .= "END:VEVENT\r\n";
            }
        }

        // 4. Cuentas por Cobrar (Pendientes y Vencidas)
        $cuentas = CuentasPorCobrar::withoutGlobalScopes()->with('cliente')
            ->whereIn('estado', ['pendiente', 'parcial', 'vencido'])
            ->get();

        foreach ($cuentas as $cxxc) {
            if (!$cxxc->fecha_vencimiento) {
                continue;
            }

            $vencimiento = Carbon::parse($cxxc->fecha_vencimiento);
            // Si está vencida, MOSTRARLA HOY para que el usuario la vea inmediatamente
            $isOverdue = $cxxc->estaVencida() || ($vencimiento->isPast() && $cxxc->estado !== 'pagado');
            $displayDate = $isOverdue ? now() : $vencimiento;

            $prefix = $isOverdue ? '🚨 CXC VENCIDA' : '💸 CXC PENDIENTE';
            $clienteNombre = $cxxc->cliente ? $cxxc->cliente->nombre_razon_social : 'Cliente Desconocido';

            // Unique ID combinando fecha para evitar cacheo de posiciones antiguas si cambia
            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:cxxc-" . $cxxc->id . "-" . $displayDate->format('Ymd') . "@asistenciavircom.com\r\n";
            $ical .= "DTSTAMP:" . $this->formatDate($cxxc->created_at) . "\r\n";
            $ical .= "DTSTART;VALUE=DATE:" . $displayDate->format('Ymd') . "\r\n";
            $ical .= "SUMMARY:" . $prefix . ": " . $clienteNombre . "\r\n";
            $ical .= "DESCRIPTION:Monto Total: $" . number_format($cxxc->monto_total ?? 0, 2) . "\\nPendiente: $" . number_format($cxxc->monto_pendiente ?? 0, 2) . "\\nVencimiento Original: " . $vencimiento->format('d/m/Y') . "\\nNotas: " . $cxxc->notas . "\r\n";
            $ical .= "TRANSP:TRANSPARENT\r\n";
            $ical .= "END:VEVENT\r\n";
        }

        // 5. Cobranzas Programadas (Rentas / Otros)
        $cobranzas = Cobranza::withoutGlobalScopes()->with('renta.cliente')
            ->where('estado', 'pendiente')
            ->get();

        foreach ($cobranzas as $cobranza) {
            if (!$cobranza->fecha_cobro) {
                continue;
            }

            $fechaCobro = Carbon::parse($cobranza->fecha_cobro);
            // Si ya pasó y sigue pendiente, mostrar HOY
            $isOverdue = $fechaCobro->isPast();
            $displayDate = $isOverdue ? now() : $fechaCobro;

            $clienteNombre = $cobranza->renta && $cobranza->renta->cliente ? $cobranza->renta->cliente->nombre_razon_social : 'Sin Cliente';

            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:cobranza-" . $cobranza->id . "-" . $displayDate->format('Ymd') . "@asistenciavircom.com\r\n";
            $ical .= "DTSTAMP:" . $this->formatDate($cobranza->created_at) . "\r\n";
            $ical .= "DTSTART;VALUE=DATE:" . $displayDate->format('Ymd') . "\r\n";
            $ical .= "SUMMARY:🗓️ COBRANZA: " . $clienteNombre . "\r\n";
            $ical .= "DESCRIPTION:Monto: $" . number_format($cobranza->monto_cobrado ?? 0, 2) . "\\nConcepto: " . $cobranza->concepto . "\\nFecha Original: " . $fechaCobro->format('d/m/Y') . "\r\n";
            $ical .= "TRANSP:TRANSPARENT\r\n";
            $ical .= "END:VEVENT\r\n";
        }

        $ical .= "END:VCALENDAR";

        return response($ical)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="asistenciavircom.ics"');
    }

    private function formatDate($date)
    {
        return $date->setTimezone('UTC')->format('Ymd\THis\Z');
    }
}
