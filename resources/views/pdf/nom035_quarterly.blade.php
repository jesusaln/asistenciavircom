<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Seguimiento Trimestral - NOM-035</title>
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #334155; line-height: 1.6; }
        .header { border-bottom: 2px solid #1e40af; padding-bottom: 15px; margin-bottom: 30px; }
        .header h1 { font-size: 18px; color: #1e40af; margin: 0; text-transform: uppercase; }
        .header p { margin: 5px 0 0; color: #64748b; font-weight: bold; }
        
        .section { margin-bottom: 25px; }
        .section-title { background: #f1f5f9; padding: 8px 12px; border-left: 4px solid #1e40af; font-weight: bold; font-size: 11px; text-transform: uppercase; margin-bottom: 15px; }
        
        .grid { display: table; width: 100%; }
        .col { display: table-cell; width: 33.33%; padding: 10px; text-align: center; border: 1px solid #e2e8f0; }
        .stat-val { font-size: 24px; font-weight: bold; color: #1e40af; display: block; }
        .stat-lbl { font-size: 9px; color: #64748b; text-transform: uppercase; margin-top: 5px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f8fafc; text-align: left; padding: 8px; border-bottom: 1px solid #e2e8f0; font-size: 9px; text-transform: uppercase; color: #475569; }
        td { padding: 8px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        
        .status-badge { padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-resolved { background: #dcfce7; color: #166534; }
        
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Evidencia de Seguimiento Trimestral</h1>
        <p>{{ $empresa->nombre_empresa }} | Trimestre Q{{ $quarter }} - {{ $year }}</p>
    </div>

    <div class="section">
        <div class="section-title">Resumen de Indicadores</div>
        <div class="grid">
            <div class="col">
                <span class="stat-val">{{ $newRespondents }}</span>
                <span class="stat-lbl">Nuevos Ingresos Evaluados</span>
            </div>
            <div class="col">
                <span class="stat-val">{{ $complaints->count() }}</span>
                <span class="stat-lbl">Denuncias Registradas</span>
            </div>
            <div class="col">
                <span class="stat-val">{{ $activities->count() }}</span>
                <span class="stat-lbl">Actividades Realizadas</span>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Detalle de Actividades de Prevención (Numeral 8.2)</div>
        @if($activities->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Actividad</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activities as $act)
                    <tr>
                        <td style="white-space: nowrap;">{{ $act->activity_date->format('d/m/Y') }}</td>
                        <td><strong>{{ $act->title }}</strong></td>
                        <td>{{ $act->description }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="font-style: italic; color: #94a3b8;">No se registraron actividades en este periodo.</p>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Seguimiento de Buzón de Denuncias (Numeral 8.1.c)</div>
        @if($complaints->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($complaints as $comp)
                    <tr>
                        <td><strong>{{ $comp->folio }}</strong></td>
                        <td>{{ $comp->created_at->format('d/m/Y') }}</td>
                        <td>{{ strtoupper($comp->type) }}</td>
                        <td>
                            <span class="status-badge {{ $comp->status === 'resolved' ? 'status-resolved' : 'status-pending' }}">
                                {{ $comp->status === 'resolved' ? 'Resuelta' : 'Pendiente' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="font-style: italic; color: #94a3b8;">No se recibieron denuncias en este periodo.</p>
        @endif
    </div>

    <div class="footer">
        Documento generado automáticamente como evidencia de seguimiento NOM-035-STPS-2018 | {{ $fecha }}
    </div>
</body>
</html>
