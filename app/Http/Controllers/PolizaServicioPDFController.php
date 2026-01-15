<?php

namespace App\Http\Controllers;

use App\Models\PolizaServicio;
use App\Support\EmpresaResolver;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PolizaServicioPDFController extends Controller
{
    /**
     * Generar PDF de beneficios de la póliza para el cliente.
     */
    public function beneficios(PolizaServicio $polizaServicio)
    {
        $polizaServicio->load(['cliente', 'servicios', 'equipos']);

        $empresaId = EmpresaResolver::resolveId();
        $empresa = \App\Models\EmpresaConfiguracion::getConfig($empresaId);

        $data = [
            'poliza' => $polizaServicio,
            'empresa' => $empresa,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'beneficios' => $this->getBeneficiosList($polizaServicio),
        ];

        $pdf = Pdf::loadView('pdf.poliza-beneficios', $data);
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Poliza-{$polizaServicio->folio}-Beneficios.pdf");
    }

    /**
     * Generar PDF del contrato completo de la póliza.
     */
    public function contrato(PolizaServicio $polizaServicio)
    {
        $polizaServicio->load(['cliente', 'servicios', 'equipos']);

        $empresaId = EmpresaResolver::resolveId();
        $empresa = \App\Models\EmpresaConfiguracion::getConfig($empresaId);

        $data = [
            'poliza' => $polizaServicio,
            'empresa' => $empresa,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.poliza-contrato', $data);
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Contrato-Poliza-{$polizaServicio->folio}.pdf");
    }

    /**
     * Generar reporte mensual de desempeño de la póliza.
     */
    public function reporteMensual(PolizaServicio $polizaServicio, $mes = null, $anio = null)
    {
        $mes = $mes ?? now()->subMonth()->month;
        $anio = $anio ?? now()->subMonth()->year;

        $polizaServicio->load(['cliente', 'equipos']);

        // Tickets del mes solicitado
        $tickets = $polizaServicio->tickets()
            ->whereMonth('created_at', $mes)
            ->whereYear('created_at', $anio)
            ->with(['categoria', 'asignado'])
            ->get();

        $empresaId = EmpresaResolver::resolveId();
        $empresa = \App\Models\EmpresaConfiguracion::getConfig($empresaId);

        $data = [
            'poliza' => $polizaServicio,
            'empresa' => $empresa,
            'tickets' => $tickets,
            'mes_nombre' => \Carbon\Carbon::createFromDate($anio, $mes, 1)->locale('es')->monthName,
            'anio' => $anio,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'total_horas' => $tickets->sum('horas_trabajadas'),
            'tickets_resueltos' => $tickets->whereIn('estado', ['resuelto', 'cerrado'])->count(),
        ];

        $pdf = Pdf::loadView('pdf.poliza-reporte-mensual', $data);
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Reporte-{$polizaServicio->folio}-{$data['mes_nombre']}-{$anio}.pdf");
    }

    /**
     * Lista de beneficios según la configuración de la póliza.
     */
    protected function getBeneficiosList(PolizaServicio $poliza): array
    {
        $beneficios = [
            [
                'icono' => '🛡️',
                'titulo' => 'Cobertura de Servicio Garantizada',
                'descripcion' => 'Su equipo está protegido bajo nuestra póliza de mantenimiento integral.',
            ],
            [
                'icono' => '⚡',
                'titulo' => 'Atención Prioritaria',
                'descripcion' => 'Sus solicitudes de soporte tienen prioridad sobre clientes sin póliza.',
            ],
        ];

        if ($poliza->sla_horas_respuesta) {
            $beneficios[] = [
                'icono' => '⏰',
                'titulo' => "SLA Garantizado de {$poliza->sla_horas_respuesta} horas",
                'descripcion' => 'Tiempo máximo de respuesta garantizado para atender sus solicitudes.',
            ];
        }

        if ($poliza->horas_incluidas_mensual) {
            $beneficios[] = [
                'icono' => '🕐',
                'titulo' => "{$poliza->horas_incluidas_mensual} Horas de Servicio Incluidas",
                'descripcion' => 'Horas mensuales de soporte técnico sin costo adicional.',
            ];
        }

        if ($poliza->limite_mensual_tickets) {
            $beneficios[] = [
                'icono' => '🎫',
                'titulo' => "Hasta {$poliza->limite_mensual_tickets} Tickets Mensuales",
                'descripcion' => 'Solicitudes de servicio incluidas en su plan mensual.',
            ];
        }

        $beneficios[] = [
            'icono' => '💰',
            'titulo' => 'Precios Preferenciales',
            'descripcion' => 'Descuentos exclusivos en refacciones, consumibles y servicios adicionales.',
        ];

        $beneficios[] = [
            'icono' => '📊',
            'titulo' => 'Reportes de Consumo',
            'descripcion' => 'Acceso a reportes detallados de uso de servicios y horas consumidas.',
        ];

        if ($poliza->renovacion_automatica) {
            $beneficios[] = [
                'icono' => '🔄',
                'titulo' => 'Renovación Automática',
                'descripcion' => 'Su póliza se renueva automáticamente para garantizar continuidad del servicio.',
            ];
        }

        return $beneficios;
    }
}
