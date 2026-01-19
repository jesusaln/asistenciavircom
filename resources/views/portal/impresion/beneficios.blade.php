<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Beneficios y Ahorro - Póliza #{{ $poliza->id }}</title>
    <style>
        body { font-family: sans-serif; color: #1f2937; margin: 0; padding: 20px; font-size: 12px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #e5e7eb; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { max-height: 40px; }
        .title-box { text-align: right; }
        h1 { font-size: 20px; color: #1e3a8a; margin: 0; text-transform: uppercase; }
        .subtitle { color: #6b7280; font-size: 10px; text-transform: uppercase; font-weight: bold; }
        
        .card { background: #f9fafb; border-radius: 8px; padding: 20px; margin-bottom: 20px; border: 1px solid #e5e7eb; }
        .card-title { font-size: 14px; font-weight: bold; color: #374151; margin-bottom: 15px; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; text-transform: uppercase; }
        
        .table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .table th { text-align: left; padding: 8px; background: #eff6ff; color: #1e3a8a; font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #bfdbfe; }
        .table td { padding: 8px; border-bottom: 1px solid #f3f4f6; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .summary-box { background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 12px; padding: 20px; text-align: center; margin-top: 30px; }
        .saving-title { color: #047857; font-size: 12px; text-transform: uppercase; font-weight: bold; letter-spacing: 1px; }
        .saving-amount { color: #059669; font-size: 32px; font-weight: 900; margin: 10px 0; }
        .saving-percent { display: inline-block; background: #059669; color: white; padding: 4px 12px; border-radius: 20px; font-weight: bold; font-size: 12px; }
        
        .footer { text-align: center; color: #9ca3af; font-size: 10px; margin-top: 50px; border-top: 1px solid #e5e7eb; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h2 style="margin:0; color:#111827;">{{ $empresa['nombre_comercial_config'] ?? 'Asistencia Vircom' }}</h2>
            <div style="font-size:10px; color:#6b7280;">Informe de Valor Financiero</div>
        </div>
        <div class="title-box">
            <div class="subtitle">ANÁLISIS DE COSTOS</div>
            <h1>Póliza #{{ $poliza->id }}</h1>
        </div>
    </div>

    <!-- Resumen Cliente -->
    <div style="margin-bottom: 30px;">
        <strong style="font-size: 14px;">{{ $poliza->nombre }}</strong>
        <div style="color: #6b7280;">Cliente: {{ $cliente->nombre_razon_social }}</div>
        <div style="color: #6b7280;">Vigencia: {{ \Carbon\Carbon::parse($poliza->fecha_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($poliza->fecha_fin)->format('d/m/Y') }}</div>
    </div>

    <!-- Servicios Incluidos -->
    <div class="card">
        <div class="card-title">Desglose de Servicios Incluidos</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Servicio / Cobertura</th>
                    <th class="text-right">Precio Mercado (Mensual)</th>
                    <th class="text-right">Cantidad</th>
                    <th class="text-right">Valor Total Real</th>
                </tr>
            </thead>
            <tbody>
                @php $totalValorReal = 0; @endphp
                
                @foreach($poliza->servicios as $servicio)
                    @php 
                        // Verificamos si tiene precio especial > 0, si no, tomamos el del catalogo
                        // Pero para el reporte de "Ahorro", queremos mostrar el PRECIO DE LISTA original 
                        // vs lo que paga en la poliza.
                        // Si el precio_especial es 0, significa que está INCLUIDO.
                        // Si tiene precio especial, es un costo extra que paga el cliente, asi que el valor es ese.
                        // Pero la logica de "Ahorro" es: Valor Mercado - Costo Poliza.
                        
                        // Buscamos el servicio original en el catalogo para saber su precio real
                        $precioLista = $servicio->precio; // Asumimos que el modelo Servicio tiene el precio de lista
                        $subtotalReal = $precioLista * $servicio->pivot->cantidad;
                        $totalValorReal += $subtotalReal;
                    @endphp
                    <tr>
                        <td>
                            <div class="font-bold">{{ $servicio->nombre }}</div>
                            <div style="font-size:9px; color:#6b7280;">{{ $servicio->descripcion }}</div>
                        </td>
                        <td class="text-right">${{ number_format($precioLista, 2) }}</td>
                        <td class="text-right">{{ $servicio->pivot->cantidad }}</td>
                        <td class="text-right font-bold">${{ number_format($subtotalReal, 2) }}</td>
                    </tr>
                @endforeach

                <!-- Valoracion de Tickets/Visitas si aplica -->
                @if($poliza->visitas_sitio_mensuales > 0)
                    @php 
                        $costoVisitaPromedio = 850; // Costo estimado visita tecnica
                        $valorVisitas = $poliza->visitas_sitio_mensuales * $costoVisitaPromedio;
                        $totalValorReal += $valorVisitas;
                    @endphp
                    <tr>
                        <td>
                            <div class="font-bold">Visitas en Sitio Incluidas</div>
                            <div style="font-size:9px; color:#6b7280;">Valor estimado por visita técnica</div>
                        </td>
                        <td class="text-right">${{ number_format($costoVisitaPromedio, 2) }}</td>
                        <td class="text-right">{{ $poliza->visitas_sitio_mensuales }}</td>
                        <td class="text-right font-bold">${{ number_format($valorVisitas, 2) }}</td>
                    </tr>
                @endif
            </tbody>
            <tfoot>
                <tr style="background: #f3f4f6; font-weight: bold;">
                    <td colspan="3" class="text-right">VALOR TOTAL DE MERCADO (MENSUAL)</td>
                    <td class="text-right" style="color: #1e3a8a; font-size: 14px;">${{ number_format($totalValorReal, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Comparativa -->
    <div class="summary-box">
        <div class="saving-title">AHORRO CONFIRMADO CON SU PÓLIZA</div>
        @php
            $costoPoliza = $poliza->monto_mensual;
            $ahorro = max(0, $totalValorReal - $costoPoliza);
            $porcentaje = $totalValorReal > 0 ? ($ahorro / $totalValorReal) * 100 : 0;
        @endphp
        
        <div class="saving-amount">${{ number_format($ahorro, 2) }} MXN / MES</div>
        <div class="saving-percent">AHORRO DEL {{ round($porcentaje) }}%</div>
        
        <div style="margin-top: 15px; font-size: 11px; color: #065f46;">
            Usted paga solo <strong>${{ number_format($costoPoliza, 2) }}</strong> en lugar de ${{ number_format($totalValorReal, 2) }}.
        </div>
        
        <div style="margin-top: 20px; border-top: 1px solid #6ee7b7; padding-top: 10px; font-size: 10px; color: #047857;">
            Además, incluye garantía de respuesta (SLA) de {{ $poliza->sla_horas_respuesta }} horas y portal de autogestión 24/7.
        </div>
    </div>

    <div class="footer">
        Este documento es informativo y refleja el valor estimado de los servicios contratados.<br>
        Generado automáticamente desde Asistencia Vircom Portal.
    </div>
</body>
</html>
